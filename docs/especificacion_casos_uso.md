# Especificación detallada de casos de uso

Sistema de Préstamo de Equipos Tecnológicos (D3). Este documento amplía la Figura 1 del
documento de diseño con el detalle necesario para implementar directamente el código,
sin importar el lenguaje que finalmente se confirme.

Actor único: **Encargado**.

---

## UC1 — Registrar Préstamo

**Precondiciones**
- El usuario solicitante existe en el sistema.
- El equipo solicitado existe en el sistema.

**Flujo principal**
1. El Encargado indica: id de usuario, id de equipo, y cantidad de días del préstamo.
2. El sistema busca al usuario y verifica que esté habilitado (**RN1**).
3. El sistema busca el equipo y verifica que esté disponible (**RN2**) — «include» *Validar Disponibilidad*.
4. El sistema calcula `fechaDevolucionEsperada = fechaPrestamo + días` (**RN3**).
5. El sistema crea un `Prestamo` en estado `ACTIVO` y lo guarda.
6. El sistema cambia el estado del `Equipo` a `PRESTADO`.
7. El sistema confirma el registro al Encargado.

**Flujos alternos**
- **3a.** Usuario no habilitado → se rechaza con "usuario no habilitado"; no se crea el préstamo.
- **3b.** Equipo no disponible (`PRESTADO` o `DANADO`) → se rechaza con "equipo no disponible".
- **1a.** Usuario o equipo con id inexistente → error "no encontrado".

**Postcondiciones**
- Existe un nuevo `Prestamo` en estado `ACTIVO` vinculado a ese usuario y ese equipo.
- El `Equipo` queda en estado `PRESTADO`.

---

## UC2 — Registrar Devolución

**Precondiciones**
- Existe un `Prestamo` en estado `ACTIVO` o `ATRASADO` para el equipo que se devuelve.

**Flujo principal**
1. El Encargado indica el id del préstamo y si el equipo vuelve en mal estado (`danado: boolean`).
2. El sistema busca el préstamo y verifica que esté `ACTIVO` o `ATRASADO` (**RN5**).
3. El sistema registra `fechaDevolucionReal = hoy` y cambia el préstamo a `DEVUELTO`.
4. **Si** `danado = true` → «extend» *Registrar Equipo Dañado*: el equipo pasa a `DANADO`.
   **Si no** → el equipo pasa a `DISPONIBLE`.
5. El sistema evalúa si el préstamo estaba atrasado (**RN6**). Si lo estaba, notifica a todos los
   observadores registrados (patrón Observer, ver guía de sustentación).
6. El sistema confirma la devolución al Encargado.

**Flujos alternos**
- **2a.** El préstamo no existe o ya estaba `DEVUELTO` → error "préstamo inválido o ya cerrado".

**Postcondiciones**
- El préstamo queda `DEVUELTO` con `fechaDevolucionReal` registrada.
- El equipo queda `DISPONIBLE` o `DANADO` según corresponda.
- Si hubo atraso, los observadores fueron notificados exactamente una vez.

---

## UC3 — Consultar Historial de Préstamos

**Precondiciones**
- Ninguna especial (puede no haber préstamos registrados todavía).

**Flujo principal**
1. El Encargado solicita ver los préstamos (todos, o solo los activos).
2. El sistema obtiene la lista completa desde `PrestamoRepository`.
3. Para cada préstamo, el sistema calcula si está atrasado — «include» *Calcular Atraso* (**RN6**).
4. El sistema retorna la lista con el indicador de atraso por cada préstamo.

**Flujos alternos**
- **2a.** No hay préstamos registrados → se retorna una lista vacía (no es un error).

**Postcondiciones**
- Ninguna (caso de uso de solo lectura, no modifica estado).

---

## Reglas de negocio (RN)

| # | Regla |
|---|---|
| RN1 | Un usuario con `habilitado = false` no puede iniciar un préstamo. |
| RN2 | Un equipo solo puede prestarse si su estado es `DISPONIBLE`. |
| RN3 | `fechaDevolucionEsperada = fechaPrestamo + N días`. Valor de `N` por defecto sugerido: **7**. |
| RN5 | Solo se puede devolver un préstamo en estado `ACTIVO` o `ATRASADO` (no uno ya `DEVUELTO`). |
| RN6 | Un préstamo está atrasado si `fechaDevolucionReal` (o, si aún no se devuelve, la fecha actual) es posterior a `fechaDevolucionEsperada`. |

**Nota:** se dejó fuera intencionalmente cualquier regla de "máximo de préstamos simultáneos por
usuario" o de multas — no son necesarias para los 3 casos de uso mínimos y añadirían complejidad
que la docente pidió evitar. Si en la sustentación preguntan por qué no están, esa es la respuesta:
decisión consciente de alcance, no un olvido.
