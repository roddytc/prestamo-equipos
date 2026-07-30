# Guía de sustentación

Preguntas que la docente probablemente hará (dice el enunciado: "el equipo debe ser capaz de
explicar y defender cada decisión de diseño y cada línea de código") y respuestas preparadas.
No las memorices literal — entiéndelas para poder responder con tus propias palabras y aguantar
una repregunta.

---

### 1. ¿Por qué eligieron este dominio y este alcance?

Un sistema de préstamo de equipos tecnológicos tiene reglas simples y verificables (disponibilidad,
habilitación del usuario, atraso), suficientes para mostrar diseño OO real sin la complejidad
adicional de horarios (laboratorio) o multas monetarias (biblioteca). Con 3 casos de uso y 8
clases se cubre el mínimo pedido sin sacrificar coherencia — que es justo lo que la docente pidió
al advertir que el tiempo apremia.

### 2. ¿Por qué el patrón Observer y no otro?

El problema real es: cuando un préstamo se atrasa, algo debe reaccionar (hoy: avisar por consola;
mañana: quizás correo o SMS). Si `GestorPrestamos` conociera el detalle de cada canal, cada canal
nuevo obligaría a modificarlo (viola OCP). Observer desacopla "detectar el evento" de "reaccionar
al evento": `GestorPrestamos` solo conoce la interfaz `NotificadorAtraso`.

Se descartó **Strategy** porque el cálculo de atraso es una regla fija
(`hoy > fechaDevolucionEsperada`), no una familia de algoritmos intercambiables — aplicar Strategy
ahí habría sido sobre-ingeniería sin necesidad real.

*Si insiste "¿y por qué no simplemente un `if` y una función `notificarPorConsola()`?"* — responde:
funcionaría hoy, pero acoplaría `GestorPrestamos` a una implementación concreta; agregar un
segundo canal (email) obligaría a tocar y volver a probar esa clase. Con Observer, se agrega una
clase nueva (`EmailNotificador implements NotificadorAtraso`) sin tocar `GestorPrestamos`.

### 3. ¿Cómo aplican los principios SOLID?

| Principio | Cómo se ve en el diseño |
|---|---|
| **SRP** | Cada clase tiene una sola razón para cambiar: `Usuario` sabe si puede prestar, `Equipo` sabe su disponibilidad, `Prestamo` sabe si está atrasado, `GestorPrestamos` orquesta, los repositorios abstraen almacenamiento, `NotificadorAtraso` abstrae notificación. |
| **OCP** | Se agregan nuevos canales de notificación (o nuevos tipos de repositorio) sin modificar `GestorPrestamos`. |
| **LSP** | Cualquier implementación de `NotificadorAtraso` (consola, email futuro) debe poder sustituir a otra sin romper el comportamiento que `GestorPrestamos` espera. |
| **ISP** | `NotificadorAtraso` tiene un solo método — ninguna implementación se ve forzada a implementar algo que no usa. |
| **DIP** | `GestorPrestamos` depende de abstracciones (interfaces de repositorio y de notificador), no de clases concretas — por eso el motor de persistencia (hoy SQLite vía Eloquent) se podría cambiar por otro (MySQL, Postgres) sin tocar la lógica de negocio. |

### 4. ¿Cómo se refleja el modelo 4+1 en este proyecto?

Resume las 5 vistas del documento (sección 1): escenarios (casos de uso) amarra a lógica (clases),
procesos (un solo hilo, síncrono), desarrollo (carpetas `Models/Repositories/Services/Notifiers`)
y física (Laravel + SQLite en un contenedor Docker). La clave a transmitir: **cada vista responde a la misma
pregunta** ("¿cómo se resuelven los 3 casos de uso?") desde un ángulo distinto — no son 5 diagramas
sueltos.

### 5. ¿Por qué esas multiplicidades (`1 -- 0..*`)?

Un `Usuario` puede acumular muchos préstamos a lo largo del tiempo, pero cada `Prestamo`
pertenece a exactamente un usuario — de ahí `Usuario 1 -- 0..* Prestamo`. Igual para `Equipo`.
La regla de que un equipo **no puede tener dos préstamos activos a la vez** no es una
multiplicidad estática (UML no lo expresa bien como cardinalidad fija), así que se valida en
tiempo de ejecución dentro de `registrarPrestamo` (RN2) — vale la pena decir esto explícitamente
si preguntan "¿y dónde está esa regla en el diagrama de clases?": **no está en el diagrama porque
no es expresable como multiplicidad; está en el comportamiento (`estaDisponible()`), y se prueba
con un test unitario.**

### 6. ¿Qué pasaría si el sistema necesitara varios encargados trabajando a la vez?

Ahora mismo es un solo proceso, sin concurrencia real (vista de procesos). Si hubiera múltiples
encargados operando simultáneamente, el riesgo sería que dos registren un préstamo sobre el mismo
equipo al mismo tiempo. La extensión natural sería un control optimista sobre `Equipo.estado`
antes de confirmar (verificar que no cambió entre la lectura y la escritura). No se implementó
porque está fuera del alcance mínimo pedido, pero el diseño ya lo anticipa.

### 7. ¿Qué es un "mal olor" de código y cómo lo van a identificar?

Un mal olor no es un bug: el código funciona, pero tiene un síntoma superficial de un problema de
diseño más profundo que dificulta mantenerlo (ej. método largo, duplicación, clase que sabe
demasiado). Se identifican revisando el propio código ya escrito — ver el checklist en
`plan_tecnico_git_readme.md` — con captura, ubicación exacta (archivo/línea) y justificación de
por qué molesta a futuro. **Se diagnostican en la Fase 2 y se corrigen recién en la Fase 3.**

### 8. Si trabajaste el proyecto individualmente, ¿cómo lo vas a explicar?

Ten lista una respuesta corta y honesta sobre por qué es individual (tu situación real), y
enfatiza que precisamente por eso puedes responder por el 100% de las decisiones de diseño y
cada commit del historial — nadie más tomó ninguna decisión que no puedas justificar tú mismo.
Eso, bien presentado, es una fortaleza en la sustentación, no una debilidad.

### 9. ¿Por qué SQLite y no MySQL o PostgreSQL?

Decisión consciente de alcance: la docente pidió explícitamente no complicar el proyecto dado el
tiempo disponible, y SQLite es la opción más simple que Laravel ofrece de forma nativa — un solo
archivo, sin proceso de servidor aparte, sin usuario/contraseña que configurar. El patrón
Repository ya deja el punto de extensión listo: si más adelante hiciera falta MySQL o Postgres,
bastaría con cambiar la configuración de conexión y, si acaso, ajustar el detalle interno de
`EquipoRepository`/`UsuarioRepository`/`PrestamoRepository` — sin tocar `GestorPrestamos` ni las
reglas de negocio.

### 10. "Explícame esta línea de código" (pregunta genérica de defensa)

Para cualquier línea que señale, ten el hábito de responder en este orden: **(1)** qué hace en una
frase, **(2)** por qué está ahí (qué caso de uso o regla de negocio soporta), **(3)** qué pasaría
si no estuviera. Ese orden demuestra dominio real, no memorización.

### 11. Veo una API/`PrestamoController` en tu repo, ¿eso qué es?

Se agregó **después** de entregada la Fase 2, por iniciativa propia, para poder demostrar el
sistema con Postman durante la sustentación en lugar de solo mostrar pruebas automatizadas — no es
parte de los casos de uso evaluados ni cambia el diseño. Es una capa delgada: el controlador solo
recibe la petición HTTP, llama al método correspondiente de `GestorPrestamos`, y traduce el
resultado (o la excepción `OperacionInvalidaException`) a una respuesta JSON. Toda la lógica de
negocio real sigue viviendo exclusivamente en `GestorPrestamos` — el controlador no valida ni
decide nada por su cuenta. De paso agrega `LogNotificador`, una segunda implementación de
`NotificadorAtraso` (ver pregunta 2) que demuestra en código real la promesa de extensibilidad del
patrón Observer.

### 12. ¿Cómo elegiste esas 6 refactorizaciones de Fase 3 y no otras?

No se eligieron al azar: son, una a una, los 5 malos olores que ya habían quedado documentados con
archivo/línea en el informe de diagnóstico de Fase 2, más una extensión natural que surgió durante
el propio proceso (mover el cambio de estado del equipo hacia `Equipo::registrarDevolucion()`, nivel
Clases y objetos). Esto es intencional y vale la pena decirlo así en la sustentación: **el
diagnóstico de Fase 2 y la refactorización de Fase 3 no son dos entregas independientes, son la
misma historia contada en dos partes** — primero se encontró el problema con evidencia real, después
se corrigió con una técnica de nombre reconocible (Extract Method, Move Method, Replace Parameter
with Explicit Methods, Introduce Parameter Object, Extract Superclass), un commit por técnica, y las
mismas 15 pruebas en verde antes y después de cada una. El detalle completo (antes/después,
justificación, commit) está en `docs/Informe_Refactorizacion_Fase3.pdf`.
