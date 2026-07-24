# Plan técnico: estructura, commits, README y malos olores

## 1. Estructura de carpetas (ya anticipada en la vista de desarrollo del documento Fase 1)

```
prestamo-equipos/
├── docs/                  # documento de diseño UML, diagramas, este plan
├── src/
│   ├── models/            # Usuario, Equipo, Prestamo + enums de estado
│   ├── repositories/       # UsuarioRepository, EquipoRepository, PrestamoRepository (en memoria)
│   ├── services/           # GestorPrestamos
│   └── notifiers/          # NotificadorAtraso (interfaz), ConsolaNotificador
├── tests/                  # un archivo de test por caso de uso + el del Observer
├── README.md
└── (config del lenguaje: package.json+tsconfig / composer.json / pom.xml, según se confirme)
```

La dependencia siempre apunta hacia adentro: `services` depende de los contratos de
`repositories` y `notifiers`, nunca al revés. Esto es lo que se va a poder señalar directamente
en la sustentación como aplicación real de DIP.

## 2. Secuencia de commits sugerida (Fase 2)

Cada commit debe ser pequeño, compilar/pasar tests, y tener un mensaje descriptivo — esto es
lo que la rúbrica llama "uso profesional de Git" (15 pts) y lo que deja evidencia de avance
incremental real, no un solo commit masivo:

1. `chore: inicializar repositorio` — README inicial, gitignore, configuración del framework de test.
2. `feat: agregar entidades de dominio (Usuario, Equipo, Prestamo)` — con sus enums de estado.
3. `feat: agregar repositorios en memoria` — Usuario/Equipo/PrestamoRepository.
4. `feat: agregar interfaz NotificadorAtraso y ConsolaNotificador`.
5. `feat: implementar registrarPrestamo (UC1) + tests` — casos 1-6 del plan de pruebas.
6. `feat: implementar registrarDevolucion (UC2) + tests` — incluye extensión de equipo dañado y notificación de atraso.
7. `feat: implementar listarPrestamosActivos (UC3) + tests`.
8. `docs: actualizar README con instrucciones de instalación y pruebas`.

La Fase 3 (aún no es esta entrega) seguirá el mismo criterio: **un commit por refactorización**,
mostrando antes/después con pruebas en verde — no lo adelantes ahora ni "limpies" el código de
más, ver sección 4.

## 3. Contenido sugerido del README

- Nombre del proyecto y una línea de descripción.
- Dominio del proyecto (D3 — sistema de préstamo de recursos) y alcance (los 3 casos de uso).
- Cómo instalar dependencias y cómo correr las pruebas (comando exacto, una vez se confirme el lenguaje).
- Estructura de carpetas (tabla de la sección 1).
- Enlace al documento de diseño UML (`docs/Documento_Diseno_UML_Fase1.html` o su PDF).
- Nota breve de que es un trabajo individual registrado bajo el dominio D3.

## 4. Checklist de malos olores a vigilar (para que el informe de Fase 2 sea genuino)

La Fase 2 pide identificar **mínimo 5 malos olores reales** en tu propio código, con archivo,
línea y justificación. Para que eso sea posible sin forzar nada, conviene escribir la primera
versión "como un MVP real" (rápido, funcional) y **no** pulirla de más — la limpieza llega en la
Fase 3. Vigila estos candidatos mientras programas, para saber dónde mirar después:

| Mal olor | Dónde suele aparecer en este diseño |
|---|---|
| **Método largo** | `registrarPrestamo` o `registrarDevolucion` si acumulan todas las validaciones en línea en vez de delegarlas. |
| **Duplicación de código** | La lógica de "buscar por id y validar que exista" repetida igual en varios métodos de `GestorPrestamos`. |
| **Números/strings mágicos** | El valor por defecto de días (`7`) hardcodeado en más de un lugar, o comparar contra el string `"ACTIVO"` en vez de usar el enum. |
| **Clase grande / God Object** | `GestorPrestamos` acumulando validación + orquestación + formateo de respuesta, si crece sin control. |
| **Complejidad condicional** | `if/else` anidados para validar usuario + equipo + fechas dentro del mismo método. |
| **Feature Envy** | Un método que manipula directamente los atributos internos de `Prestamo` en vez de pedirle a `Prestamo` que se autoevalúe (`estaAtrasado()`). |
| **Data clumps** | `usuarioId` + `equipoId` + `dias` viajando siempre juntos como parámetros sueltos en vez de agruparse en un objeto. |

**Importante:** no corrijas estos olores todavía. El objetivo de la Fase 2 es *diagnosticar*, no
curar — el informe pide capturas y justificación de por qué cada uno es un problema real. La
Fase 3 (semana 8) es donde se corrigen, uno por commit, con pruebas en verde antes y después.
