# Explicación completa del código — Fase 2

Este documento recorre **todo** lo que se construyó en `backend/`, archivo por archivo, explicando
qué hace cada pieza y por qué está escrita así. La idea es que puedas leerlo una vez, y luego abrir
el código al lado y seguirlo tú mismo — así lo memorizas de verdad en vez de repetirlo de memoria.

No repite las justificaciones de diseño (patrón Observer, SOLID, 4+1) — eso ya está en
`guia_sustentacion.md`. Este documento es sobre **el código en sí**: qué hace cada línea.

---

## 1. Panorama general

El flujo de una operación siempre es el mismo camino:

```
Encargado → GestorPrestamos → Repository → Modelo Eloquent → SQLite (archivo)
                    │
                    └──→ NotificadorAtraso[] → ConsolaNotificador (si hay atraso)
```

`GestorPrestamos` es la **única puerta de entrada** a la lógica de negocio. Nunca se llama a un
modelo o repositorio directamente desde fuera — todo pasa por él. Eso es lo que el diagrama de
clases llama "GestorPrestamos... es el único punto de entrada a la lógica de negocio".

---

## 2. La infraestructura: Docker + SQLite

**`docker/Dockerfile`**: parte de la imagen oficial `php:8.3-cli` (PHP sin servidor web, solo el
intérprete de línea de comandos, que es todo lo que necesitamos). Le instala:
- `pdo_sqlite` — la extensión que permite a PHP hablar con archivos SQLite.
- `zip` — la necesita Composer para instalar paquetes.
- Copia el binario de `composer` desde la imagen oficial de Composer (truco de "multi-stage
  build": usar una imagen solo para copiar un archivo de ella, sin arrastrar todo su contenido).

**`docker-compose.yml`**: define dos servicios.
- `app`: construye la imagen del Dockerfile, monta `./backend` dentro del contenedor en `/app`, y
  lo mantiene vivo con `tail -f /dev/null` (un contenedor sin ese truco se apaga solo al no tener
  ningún proceso principal corriendo). Eso permite ejecutar comandos con
  `docker compose exec app <comando>` cuantas veces se quiera, sin reconstruir nada.
- `db-viewer`: la imagen `coleifer/sqlite-web`, que sirve una interfaz web sencilla para explorar
  el archivo SQLite en `http://localhost:8080`, sin necesidad de login (a diferencia de Adminer,
  que bloquea el acceso sin contraseña por seguridad — por eso se cambió a esta herramienta).

**¿Por qué SQLite?** Laravel 13 lo trae configurado por defecto (`DB_CONNECTION=sqlite` en
`.env`). Es un solo archivo (`backend/database/database.sqlite`), no un proceso de servidor —
cero configuración, cero usuario/contraseña. Al instalar el proyecto, Laravel automáticamente creó
ese archivo y corrió las migraciones iniciales (las de autenticación que trae por defecto, que no
usamos, y luego las tres que agregamos nosotros).

---

## 3. Los Enums — `app/Enums/`

PHP 8.1+ tiene *enums nativos*: un tipo que solo puede valer uno de un conjunto fijo de casos.
Se usaron tres, todos "backed" por `string` (cada caso tiene un valor string asociado, que es lo
que realmente se guarda en la base de datos):

```php
enum EstadoEquipo: string
{
    case DISPONIBLE = 'DISPONIBLE';
    case PRESTADO = 'PRESTADO';
    case DANADO = 'DANADO';
}
```

`RolUsuario` (ESTUDIANTE/DOCENTE) y `EstadoEquipo` (DISPONIBLE/PRESTADO/DANADO) son directos.

**`EstadoPrestamo` solo tiene DOS casos: `ACTIVO` y `DEVUELTO`** — a propósito no existe un tercer
caso `ATRASADO`. Esto es una decisión de diseño importante que hay que saber explicar: un préstamo
atrasado **sigue estando activo** (todavía no se devolvió), el atraso es una condición que se
**calcula** comparando fechas (`Prestamo::estaAtrasado()`), no un estado que se guarda aparte. Si
se guardara "ATRASADO" como estado independiente, habría que recordar actualizarlo constantemente
(cada día que pasa) para que no quede desincronizado — eso es justamente el tipo de dato redundante
que las buenas prácticas de diseño de datos evitan.

---

## 4. Los Modelos Eloquent — `app/Models/`

Eloquent es el ORM (Object-Relational Mapper) de Laravel: cada clase que extiende `Model`
representa una tabla, y cada instancia representa una fila. `Usuario::find(5)` internamente hace
un `SELECT * FROM usuarios WHERE id = 5` y arma un objeto `Usuario` con esos datos — no se escribe
SQL a mano en ningún lado del proyecto.

**`Usuario.php`**
```php
protected $fillable = ['nombre', 'rol', 'habilitado'];
protected $casts = ['rol' => RolUsuario::class, 'habilitado' => 'boolean'];

public function puedeSolicitarPrestamo(): bool
{
    return $this->habilitado;
}
```
`$fillable` le dice a Eloquent qué campos se pueden asignar masivamente (`Usuario::create([...])`)
— es una protección de Laravel contra asignación de campos que no deberían venir de datos externos.
`$casts` convierte automáticamente la columna `rol` (guardada como texto plano en SQLite) al enum
`RolUsuario` cada vez que se lee el modelo, y de vuelta a texto al guardar. `puedeSolicitarPrestamo()`
es la única regla de negocio de esta clase: hoy es literalmente devolver el booleano, pero **vive
en el modelo** (no en `GestorPrestamos`) porque es una pregunta sobre el propio `Usuario` — mañana
podría crecer (ej. "y no debe tener préstamos atrasados") sin que `GestorPrestamos` se entere del
cambio.

**`Equipo.php`** — mismo patrón: `estaDisponible()` compara el enum, `cambiarEstado()` asigna y
guarda (`$this->save()`) en una sola llamada, para que quien use `Equipo` no tenga que acordarse de
llamar `save()` aparte.

**`Prestamo.php`** — el modelo con más lógica:
```php
public function estaAtrasado(): bool
{
    $referencia = $this->fecha_dev_real ?? now();
    return $referencia->greaterThan($this->fecha_dev_esperada);
}
```
El operador `??` (null coalescing) dice: "si `fecha_dev_real` tiene un valor, úsalo; si es null
(todavía no se devolvió), usa `now()` (ahora mismo)". Así, la misma función sirve para dos
preguntas: "¿este préstamo YA devuelto se devolvió tarde?" y "¿este préstamo activo YA está
vencido hoy?" — sin duplicar la lógica de comparación de fechas.

`registrarDevolucion()` (en el modelo, no confundir con el método del mismo nombre en
`GestorPrestamos`) hace el cambio de estado más básico: pone la fecha de devolución real a "ahora"
y el estado a `DEVUELTO`. `belongsTo(Usuario::class)` / `belongsTo(Equipo::class)` son las
relaciones de Eloquent que permiten escribir `$prestamo->equipo->nombre` y que Eloquent arme el
`JOIN`/segunda consulta automáticamente.

---

## 5. Los Repositorios — `app/Repositories/`

```php
class UsuarioRepository
{
    public function buscar(int $id): ?Usuario { return Usuario::find($id); }
    public function guardar(Usuario $usuario): void { $usuario->save(); }
}
```

A primera vista parecen innecesarios — Eloquent ya deja hacer `Usuario::find($id)` directamente.
La razón de que exista esta capa intermedia es **Dependency Inversion (DIP)**: `GestorPrestamos`
nunca escribe `Usuario::find(...)` él mismo; recibe un `UsuarioRepository` por el constructor y le
pregunta a él. Si mañana se quisiera agregar caché, logging de cada consulta, o cambiar de SQLite a
otro motor con una sintaxis distinta, **solo se toca el repositorio**, nunca `GestorPrestamos` ni
sus pruebas. `PrestamoRepository` además tiene `listar()` (todos) y `listarActivos()` (solo los
`estado = ACTIVO`, usado por UC3).

Este es exactamente el mal olor #1 del informe de Fase 2: los tres repositorios son casi
idénticos. Es un costo real y consciente — se aceptó la duplicación ahora a cambio de simplicidad,
y se corrige en Fase 3.

---

## 6. El patrón Observer — `app/Notifiers/`

```php
interface NotificadorAtraso
{
    public function notificar(Prestamo $prestamo): void;
}

class ConsolaNotificador implements NotificadorAtraso
{
    public function notificar(Prestamo $prestamo): void
    {
        echo sprintf("[ATRASO] Prestamo #%d del equipo '%s' vencio el %s.\n", ...);
    }
}
```

`GestorPrestamos` guarda un arreglo privado `$observadores` (vacío al inicio). Con
`agregarObservador($notificador)` se agregan cuantos se quiera. Cuando detecta un préstamo
atrasado, recorre ese arreglo y llama a `notificar()` en cada uno — sin importarle qué hace cada
implementación por dentro. Hoy solo existe `ConsolaNotificador` (imprime por consola), pero
`GestorPrestamos` no sabe eso; solo conoce la interfaz.

**Nota para la sustentación:** Laravel tiene su propio concepto de "Observer" (`php artisan
make:observer`), pero ese es distinto — reacciona a eventos del ciclo de vida de un modelo Eloquent
(`creating`, `updated`, `deleted`). Aquí se implementó el patrón Observer de forma manual porque la
condición que dispara la notificación es una **regla de negocio** (`estaAtrasado()`), no un evento
genérico de framework — mezclar ambos habría acoplado la regla de negocio al ciclo de vida de
Eloquent, que es justo lo que se quiere evitar.

---

## 7. GestorPrestamos — el recorrido completo de cada caso de uso

**`registrarPrestamo(usuarioId, equipoId, dias)`**
1. Busca ambas entidades por id (`$this->usuarios->buscar(...)`, `$this->equipos->buscar(...)`).
2. Si alguna no existe → excepción.
3. Si el usuario no puede solicitar (`puedeSolicitarPrestamo()`) → excepción.
4. Si el equipo no está disponible (`estaDisponible()`) → excepción.
5. Calcula `fechaPrestamo = hoy` y `fechaDevEsperada = hoy + dias` (`Carbon::today()->copy()->addDays($dias)`).
6. Crea el `Prestamo` (estado `ACTIVO`) y lo guarda vía el repositorio.
7. Cambia el equipo a `PRESTADO`.
8. Devuelve el préstamo creado.

Esto es exactamente la Figura 3 del documento de diseño (el `ref` de "Validar Disponibilidad" son
los pasos 1-4; el bloque `alt` son los pasos 5-8 contra el `else` de las excepciones).

**`registrarDevolucion(prestamoId, danado = false)`**
1. Busca el préstamo; si no existe o ya no está `ACTIVO` → excepción (cubre "no existe" y "ya
   devuelto" con una sola condición: `$prestamo->estado !== EstadoPrestamo::ACTIVO`).
2. Llama a `$prestamo->registrarDevolucion()` (el método del modelo — pone fecha real y estado
   `DEVUELTO`).
3. Según `$danado`, cambia el equipo a `DANADO` o `DISPONIBLE`.
4. Pregunta `$prestamo->estaAtrasado()` — como ya se seteó `fecha_dev_real` en el paso 2, esta
   pregunta compara esa fecha real contra la esperada.
5. Si estaba atrasado, notifica a los observadores.

Esto es la Figura 4: el `alt [danado]` es el paso 3, el `opt [estaAtrasado()]` es los pasos 4-5.

**`listarPrestamosActivos()`** — un solo `return`, delega directo al repositorio.

**`verificarAtrasos()`** — recorre todos los préstamos activos y notifica los que estén vencidos;
pensado para poder correrse periódicamente (ej. una tarea programada), aunque en esta entrega no
hay ningún cron real disparándolo — se prueba invocándolo directamente.

---

## 8. Las pruebas — `tests/Feature/`

Tres mecanismos de PHPUnit/Laravel que se repiten en todos los archivos de test:

- **`use RefreshDatabase;`** — antes de cada test, Laravel corre las migraciones desde cero sobre
  la base de datos de pruebas (que por `phpunit.xml` es `:memory:`, un SQLite temporal en RAM, no
  el archivo real). Así cada test arranca con tablas vacías y no le afectan los datos que dejó el
  test anterior.
- **`Mockery::mock(NotificadorAtraso::class)`** — crea un objeto falso que implementa esa interfaz
  sin ninguna implementación real detrás. `shouldReceive('notificar')->once()` significa "este test
  falla si `notificar()` no se llama exactamente una vez"; `shouldNotReceive('notificar')` es lo
  contrario. Así se verifica el patrón Observer sin depender de qué hace `ConsolaNotificador` por
  dentro.
- **`Carbon::setTestNow(Carbon::parse('2026-07-24'))`** — "congela" la fecha/hora actual para ese
  test (todo lo que llame `now()` o `Carbon::today()` en el código de producción recibirá esa fecha
  fija). Es indispensable para probar algo como "¿está atrasado?" de forma determinística — sin
  esto, un test que compare fechas podría pasar hoy y fallar mañana. Siempre se resetea con
  `Carbon::setTestNow()` (sin argumento) al final del test.

Ejemplo completo para poder explicar uno de memoria:
```php
public function test_rechaza_si_el_usuario_no_esta_habilitado(): void
{
    $usuario = Usuario::create(['nombre' => 'Luis', 'rol' => RolUsuario::ESTUDIANTE, 'habilitado' => false]);
    $equipo = Equipo::create(['nombre' => 'Laptop Dell', 'categoria' => 'Laptop', 'estado' => EstadoEquipo::DISPONIBLE]);

    $this->expectException(OperacionInvalidaException::class);

    try {
        $this->gestor->registrarPrestamo($usuario->id, $equipo->id, 7);
    } finally {
        $this->assertSame(0, Prestamo::count());
        $this->assertSame(EstadoEquipo::DISPONIBLE, $equipo->fresh()->estado);
    }
}
```
Crea un usuario deshabilitado y un equipo disponible, espera que la llamada lance la excepción, y
en el `finally` (que corre pase lo que pase) confirma que **no quedó ningún efecto secundario**:
ni se creó un préstamo, ni el equipo cambió de estado. `$equipo->fresh()` vuelve a leer el equipo
desde la base de datos (por si el objeto en memoria quedó desactualizado).

---

## 9. Cómo correr todo (chuleta de comandos)

```bash
docker compose up -d --build      # levantar los contenedores
docker compose exec app php artisan migrate     # crear las tablas
docker compose exec app php artisan test        # correr las 15 pruebas
docker compose exec app php artisan tinker       # consola interactiva de Laravel, para probar código suelto
```

`http://localhost:8080` abre el visor de la base de datos SQLite.

---

## 10. Lo que cuenta el historial de Git

Los commits siguen el orden real de construcción: documento de diseño → infraestructura Docker →
scaffold de Laravel → entidades de dominio → repositorios → Observer → `GestorPrestamos` → un
commit de test por caso de uso. Si te preguntan por un commit específico, ábrelo con
`git show <hash>` y vas a ver exactamente los archivos de esa capa, nada más — cada commit es
autocontenido y corresponde a una sola responsabilidad, que es lo que hace que el historial sea
"profesional" según la rúbrica.
