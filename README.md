# Sistema de Préstamo de Equipos Tecnológicos

Proyecto integrador de Diseño de Software (UCOM0310) — Universidad Espíritu Santo.
Dominio **D3 — Sistema de préstamo de recursos**, acotado a equipos tecnológicos (laptops,
proyectores, cámaras). Trabajo individual, registrado bajo el dominio D3.

## Documentación

| Documento | Contenido |
|---|---|
| [docs/Documento_Diseno_UML_Fase1.pdf](docs/Documento_Diseno_UML_Fase1.pdf) | Fase 1: enfoque 4+1, casos de uso, clases, secuencias, patrón Observer |
| [docs/Informe_Malos_Olores_Fase2.pdf](docs/Informe_Malos_Olores_Fase2.pdf) | Fase 2: 5 malos olores diagnosticados con ubicación y justificación |
| [docs/Informe_Refactorizacion_Fase3.pdf](docs/Informe_Refactorizacion_Fase3.pdf) | Fase 3: 6 refactorizaciones (4 niveles) con antes/después, commit y diagrama de clases actualizado |
| [docs/especificacion_casos_uso.md](docs/especificacion_casos_uso.md) | Flujos y reglas de negocio de los 3 casos de uso |

## Stack

- **PHP 8.3 + Laravel 13**, con **SQLite** (archivo único, sin servidor de base de datos).
- Todo corre en **Docker** — no requiere instalar PHP, Composer ni Laravel en el host.
- Pruebas con **PHPUnit** (incluido con Laravel).

## Cómo levantar el proyecto

```bash
docker compose up -d --build
```

Esto construye la imagen (PHP + Composer + extensión SQLite) y dos servicios:

- **app** — Laravel sirviendo en [http://localhost:8000](http://localhost:8000) (`php artisan serve`).
- **db-viewer** — visor web de la base de datos SQLite en [http://localhost:8080](http://localhost:8080) ([coleifer/sqlite-web](https://github.com/coleifer/sqlite_web)).

## Comandos útiles

```bash
# Correr las migraciones
docker compose exec app php artisan migrate

# Cargar datos de ejemplo (3 usuarios, 3 equipos) para probar la API
docker compose exec app php artisan db:seed

# Correr la suite de pruebas
docker compose exec app php artisan test

# Cualquier otro comando artisan / composer
docker compose exec app php artisan <comando>
docker compose exec app composer <comando>
```

## API para probar en Postman (no forma parte de los casos de uso evaluados; es solo una capa fina sobre `GestorPrestamos` para poder demostrar el sistema)

Base URL: `http://localhost:8000/api`. Corre `php artisan db:seed` primero para tener usuarios (ids
1-3) y equipos (ids 1-3) de ejemplo.

| Método | Ruta | Body (JSON) | Caso de uso |
|---|---|---|---|
| `POST` | `/prestamos` | `{"usuario_id": 1, "equipo_id": 1, "dias": 7}` | UC1 — Registrar Préstamo |
| `POST` | `/prestamos/{id}/devolucion` | `{"danado": false}` | UC2 — Registrar Devolución |
| `GET` | `/prestamos/activos` | — | UC3 — Consultar Historial |

Respuestas de error (usuario no habilitado, equipo no disponible, préstamo inexistente) devuelven
`422` con `{"error": "..."}`. Si un préstamo devuelto estaba atrasado, se registra un aviso en
`storage/logs/laravel.log` vía `LogNotificador` (una segunda implementación de `NotificadorAtraso`
distinta de `ConsolaNotificador` — demuestra que se puede agregar un canal nuevo sin tocar
`GestorPrestamos`).

## Estructura

```
prestamo-equipos/
├── docs/                      # Documento de diseño UML, informe de malos olores, prep. de sustentación
├── docker/Dockerfile          # Imagen PHP 8.3 + Composer + SQLite
├── docker-compose.yml         # Servicios app + db-viewer
└── backend/                   # Proyecto Laravel
    ├── app/
    │   ├── Models/             # Usuario, Equipo, Prestamo (Eloquent)
    │   ├── Enums/               # RolUsuario, EstadoEquipo, EstadoPrestamo
    │   ├── Repositories/        # Repository (base, Fase 3) + UsuarioRepository, EquipoRepository, PrestamoRepository
    │   ├── Services/            # GestorPrestamos, SolicitudPrestamo (Fase 3), OperacionInvalidaException
    │   ├── Notifiers/           # NotificadorAtraso (interfaz), ConsolaNotificador, LogNotificador — patrón Observer
    │   └── Http/Controllers/    # PrestamoController — capa fina de API para demos (no evaluada)
    ├── routes/api.php           # POST /prestamos, POST /prestamos/{id}/devolucion, GET /prestamos/activos
    ├── database/migrations/     # usuarios, equipos, prestamos
    ├── database/seeders/        # datos de ejemplo para probar la API
    └── tests/Feature/           # 15 pruebas: 1 archivo por caso de uso + patrón Observer
```

## Casos de uso implementados

1. **Registrar Préstamo** — valida usuario habilitado + equipo disponible antes de crear el préstamo.
2. **Registrar Devolución** — cierra el préstamo; si el equipo vuelve dañado lo marca como tal; notifica atrasos vía Observer.
3. **Consultar Historial de Préstamos** — lista los préstamos activos con su indicador de atraso.

## Estado del proyecto

- ✅ Fase 1 — Documento de diseño UML
- ✅ Fase 2 — Código base funcional, 15 pruebas en verde, informe de malos olores
- ✅ Fase 3 — 6 refactorizaciones (Métodos, Clases y objetos, Condicionales, Datos), un commit por cada una, 15 pruebas en verde antes y después, diagrama de clases actualizado
