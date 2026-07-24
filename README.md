# Sistema de Préstamo de Equipos Tecnológicos

Proyecto integrador de Diseño de Software (UCOM0310) — Universidad Espíritu Santo.
Dominio **D3 — Sistema de préstamo de recursos**, acotado a equipos tecnológicos (laptops,
proyectores, cámaras). Trabajo individual, registrado bajo el dominio D3.

## Documentación

| Documento | Contenido |
|---|---|
| [docs/Documento_Diseno_UML_Fase1.pdf](docs/Documento_Diseno_UML_Fase1.pdf) | Fase 1: enfoque 4+1, casos de uso, clases, secuencias, patrón Observer |
| [docs/Informe_Malos_Olores_Fase2.pdf](docs/Informe_Malos_Olores_Fase2.pdf) | Fase 2: 5 malos olores diagnosticados con ubicación y justificación |
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

- **app** — contenedor con el proyecto Laravel montado en `./backend`.
- **db-viewer** — visor web de la base de datos SQLite en [http://localhost:8080](http://localhost:8080) ([coleifer/sqlite-web](https://github.com/coleifer/sqlite_web)).

## Comandos útiles

```bash
# Correr las migraciones
docker compose exec app php artisan migrate

# Correr la suite de pruebas
docker compose exec app php artisan test

# Cualquier otro comando artisan / composer
docker compose exec app php artisan <comando>
docker compose exec app composer <comando>
```

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
    │   ├── Repositories/        # UsuarioRepository, EquipoRepository, PrestamoRepository
    │   ├── Services/            # GestorPrestamos (orquesta los 3 casos de uso)
    │   └── Notifiers/           # NotificadorAtraso (interfaz) + ConsolaNotificador — patrón Observer
    ├── database/migrations/     # usuarios, equipos, prestamos
    └── tests/Feature/           # 15 pruebas: 1 archivo por caso de uso + patrón Observer
```

## Casos de uso implementados

1. **Registrar Préstamo** — valida usuario habilitado + equipo disponible antes de crear el préstamo.
2. **Registrar Devolución** — cierra el préstamo; si el equipo vuelve dañado lo marca como tal; notifica atrasos vía Observer.
3. **Consultar Historial de Préstamos** — lista los préstamos activos con su indicador de atraso.

## Estado del proyecto

- ✅ Fase 1 — Documento de diseño UML
- ✅ Fase 2 — Código base funcional, 15 pruebas en verde, informe de malos olores
- ⏳ Fase 3 — Refactorización (semana 8), cubriendo los 4 niveles a partir de los 5 malos olores ya diagnosticados
