# Plan de pruebas unitarias (Given / When / Then)

Independiente de framework: se traduce directo a Jest (`describe`/`it`), PHPUnit/Pest
(`test`/`it`), o JUnit (`@Test`) apenas se confirme el lenguaje. Cada caso referencia las
reglas de negocio (RN) definidas en `especificacion_casos_uso.md`.

---

## UC1 — Registrar Préstamo

| # | Given | When | Then |
|---|---|---|---|
| 1 | Usuario habilitado + equipo `DISPONIBLE` | se registra el préstamo | se crea un `Prestamo` en estado `ACTIVO` y el equipo pasa a `PRESTADO` |
| 2 | Usuario con `habilitado = false` | se intenta registrar el préstamo | se rechaza (RN1); no se crea ningún préstamo ni cambia el equipo |
| 3 | Equipo en estado `PRESTADO` | se intenta registrar otro préstamo sobre ese equipo | se rechaza (RN2) |
| 4 | Equipo en estado `DANADO` | se intenta prestarlo | se rechaza (mismo criterio que "no disponible") |
| 5 | `dias = 5` | se registra el préstamo | `fechaDevolucionEsperada = fechaPrestamo + 5 días` (RN3) |
| 6 | Usuario o equipo con id inexistente | se intenta registrar el préstamo | error "no encontrado", sin efectos secundarios |

## UC2 — Registrar Devolución

| # | Given | When | Then |
|---|---|---|---|
| 1 | Préstamo `ACTIVO`, no atrasado, `danado = false` | se registra la devolución | préstamo pasa a `DEVUELTO`, equipo pasa a `DISPONIBLE`, ningún observador es notificado |
| 2 | Préstamo `ACTIVO`, `danado = true` | se registra la devolución | el equipo pasa a `DANADO` (no a `DISPONIBLE`) |
| 3 | Préstamo cuya `fechaDevolucionEsperada` ya pasó | se registra la devolución | se invoca `notificar()` en **cada** observador registrado (exactamente una vez cada uno) |
| 4 | Préstamo no atrasado | se registra la devolución | **no** se invoca `notificar()` en ningún observador |
| 5 | Id de préstamo inexistente, o préstamo ya `DEVUELTO` | se intenta registrar la devolución | se rechaza con error claro (RN5) |

## UC3 — Consultar Historial de Préstamos

| # | Given | When | Then |
|---|---|---|---|
| 1 | Préstamos con distintos estados (activo, atrasado, devuelto) | se consulta el historial completo | se retornan todos, cada uno con el indicador de atraso correcto (RN6) |
| 2 | No hay préstamos registrados | se consulta el historial | se retorna una lista vacía, sin error |
| 3 | Préstamos activos y devueltos mezclados | se filtra "solo activos" | solo aparecen los que no están en estado `DEVUELTO` |

---

## Prueba adicional recomendada: el patrón Observer en sí

Vale la pena un test dedicado que no es "por caso de uso" sino sobre el mecanismo de extensión,
porque es lo que vas a defender como patrón aplicado:

> **Given** dos notificadores registrados en `GestorPrestamos` (pueden ser dobles de prueba /
> spies), **When** se cierra un préstamo atrasado, **Then** ambos notificadores reciben la
> llamada `notificar(prestamo)` — demostrando que `GestorPrestamos` no conoce la implementación
> concreta, solo la interfaz `NotificadorAtraso`.

Esto es exactamente lo que hace que el patrón sea verificable con pruebas, no solo una
afirmación en el documento de diseño.

## Convención de nombres sugerida

- Un archivo de test por caso de uso: `registrar-prestamo.test.*`, `registrar-devolucion.test.*`,
  `consultar-historial.test.*`, más `observer-notificacion.test.*` para la prueba adicional.
- Cada test unitario debe poder ejecutarse con repositorios en memoria "limpios" (sin estado
  compartido entre tests) para evitar pruebas frágiles por orden de ejecución.
