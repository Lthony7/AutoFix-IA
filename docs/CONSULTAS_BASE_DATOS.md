# Documentación de consultas a la base de datos

**Proyecto:** AUTOFIX IA (taller automotriz)  
**Motor:** PostgreSQL (`taller_automotriz_db`)  
**ORM:** Laravel Eloquent / Query Builder  
**Fecha de análisis:** 2026-07-29

Este documento describe **qué consultas realiza la aplicación**, sobre **qué tablas**, con **qué filtros**, y recomienda **índices**, **constraints**, **triggers** y otras estructuras de BD cuando aportan valor.

---

## 1. Resumen ejecutivo

| Aspecto | Estado actual |
|--------|----------------|
| Acceso a datos | Eloquent directo desde controladores y servicios (sin repositorios activos) |
| Triggers / vistas / procedimientos | **Ninguno** |
| Reglas de negocio críticas | En PHP (stock, estados factura/pago, capacidad de citas, numeración) |
| Índices explícitos de dominio | Solo en `citas_taller`, `presupuestos`, `orden_avances` (+ UNIQUE/FK) |
| Riesgos principales | Numeración concurrente (`generarNumero`), índices faltantes en OT/pagos/productos, stock sin CHECK en BD |

**Prioridad de mejoras de BD:**

1. Índices en `ordenes_trabajo`, `pagos`, `facturas`, `productos`, `orden_repuesto`
2. Constraint `CHECK (stock >= 0)` y/o refuerzo de concurrencia en stock
3. Numeración atómica (secuencia / advisory lock / retry ante UNIQUE)
4. Triggers solo donde la app no pueda garantizar integridad (opcional; no obligatorio si se endurece la capa PHP)

---

## 2. Modelo de datos (relaciones)

```
users 1──* clientes 1──* vehiculos
users 1──0..1 mecanicos
clientes 1──* ordenes_trabajo *──1 vehiculos
ordenes_trabajo *──0..1 mecanicos
ordenes_trabajo 1──* orden_servicio *──1 servicios
ordenes_trabajo 1──* orden_repuesto *──1 productos
ordenes_trabajo 1──0..1 diagnosticos_ia
ordenes_trabajo 1──0..1 facturas 1──* detalle_facturas
ordenes_trabajo 1──0..1 pagos ──0..1 facturas
ordenes_trabajo 1──* orden_avances *──1 users
clientes 1──* citas_taller *──1 vehiculos
citas_taller ──0..1 mecanicos | ordenes_trabajo | presupuestos
clientes 1──* presupuestos ──0..1 vehiculos
presupuestos 1──* presupuesto_servicios *──1 servicios
presupuestos 1──* presupuesto_repuestos *──1 productos
```

Tablas de soporte Laravel: `sessions`, `cache`, `jobs`, `failed_jobs`, `notifications`, `personal_access_tokens`, `password_reset_tokens`.

---

## 3. Índices y constraints existentes

### 3.1 Uniques de negocio

| Tabla | Columna(s) |
|-------|------------|
| `users` | `email` |
| `clientes` | `numero_documento`, `email` |
| `vehiculos` | `placa` |
| `mecanicos` | `documento` |
| `servicios` | `nombre` |
| `productos` | `codigo` |
| `ordenes_trabajo` | `numero` |
| `diagnosticos_ia` | `orden_trabajo_id` (1:1 con OT) |
| `facturas` | `numero`, `orden_trabajo_id` |
| `pagos` | `orden_trabajo_id`, `factura_id` |
| `presupuestos` | `numero` |

### 3.2 Índices compuestos / auxiliares explícitos

| Tabla | Índice | Justificación |
|-------|--------|---------------|
| `citas_taller` | `(fecha_hora, estado)`, `mecanico_id`, `cliente_id` | Calendario y ocupación de slots |
| `presupuestos` | `(cliente_id, estado)` | Listados portal / taller |
| `orden_avances` | `(orden_trabajo_id, created_at)` | Bitácora por OT |
| `sessions` | `user_id`, `last_activity` | Framework |
| `personal_access_tokens` | `token` UNIQUE, `expires_at` | Sanctum |
| `jobs` | `queue` | Cola |

Las FK de PostgreSQL suelen crear índices en la columna referenciada del lado “hijo”; aun así, varios filtros compuestos de la app **no** están cubiertos (ver sección 5).

### 3.3 Triggers / vistas / procedimientos

**No existen.** Toda la integridad de flujo (stock, sync factura↔pago, capacidad de citas) vive en la aplicación.

---

## 4. Consultas por módulo

Leyenda de operaciones: **S** = SELECT, **I** = INSERT, **U** = UPDATE, **D** = DELETE, **TX** = transacción.

### 4.1 Autenticación y usuarios

| ID | Ubicación | Op | Tablas | Qué hace | Filtros / patrón |
|----|-----------|----|--------|----------|------------------|
| AU1 | `AuthController::register` / `WebAuthController::register` | I | `users` | Alta de usuario | `create` |
| AU2 | `AuthController::login` | S | `users` | Login | `WHERE email` (UNIQUE) |
| AU3 | `AuthController::logout` | D | `personal_access_tokens` | Revocar token Sanctum | token actual |
| AU4 | `UsuarioWebController::index` | S | `users` | Listado + conteos por rol | `ORDER BY name`; `SELECT role, COUNT(*) GROUP BY role` |
| AU5 | `UsuarioWebController` CRUD | S/I/U/D | `users` | ABM usuarios | `findOrFail` / `update` / `delete` |
| AU6 | `CitaNotifier::notifyTaller` | S | `users` | Destinatarios de notificación | `activo = true`, `role IN (admin, recepcionista)` |
| AU7 | `MecanicoWebController` opciones | S | `mecanicos`, `users` | Usuarios disponibles para vincular mecánico | `user_id` no nulo / roles mecánico |

**Índices:** `email` UNIQUE cubre login. Recomendable índice `(activo, role)` si crecen notificaciones y listados filtrados.  
**Triggers:** no necesarios.

---

### 4.2 Clientes

| ID | Ubicación | Op | Tablas | Qué hace | Filtros / patrón |
|----|-----------|----|--------|----------|------------------|
| CL1 | `ClienteWebController::index` | S | `clientes`, `vehiculos` | Listado con vehículos | `with('vehiculos')`, `ORDER BY razon_social`, paginate |
| CL2 | `ClienteWebController::index` | S | `clientes` | KPIs (total / activos) | `COUNT` por `estado` |
| CL3 | `Cliente*Controller` store/update | I/U | `clientes` | Crear / editar | Validación UNIQUE doc/email |
| CL4 | `ClienteWebController::destroy` | S/D | `clientes`, `vehiculos` | Borrado bloqueado si tiene vehículos | `EXISTS` en vehículos |
| CL5 | `ClienteCuentaService::ensureForUser` | S/U/I | `clientes` | Vincular o crear ficha portal | `user_id`; `email` + `user_id IS NULL`; `EXISTS numero_documento` |
| CL6 | `PortalClienteWebController::actualizarDatos` | U TX | `clientes`, `users` | Actualizar perfil | Scope por usuario |

**Índices recomendados:** `(estado)`, `user_id` (si el FK no lo indexa de forma usable en todos los planes).  
**Triggers:** no necesarios; UNIQUE de documento/email ya protege integridad.

---

### 4.3 Vehículos e historial

| ID | Ubicación | Op | Tablas | Qué hace | Filtros / patrón |
|----|-----------|----|--------|----------|------------------|
| VE1 | `Vehiculo*Controller` | S/I/U/D | `vehiculos`, `clientes` | CRUD flota | `with('cliente')`, `ORDER BY placa` |
| VE2 | `PortalClienteWebController::misVehiculos` / `guardarVehiculo` | S/I | `vehiculos` | Portal: mis vehículos | `WHERE cliente_id` |
| VE3 | `HistorialWebController::index` | S | `vehiculos`, `clientes` | Buscar vehículo | `LOWER(placa/marca/modelo) LIKE`; `whereHas` cliente |
| VE4 | `HistorialWebController::show` | S | `ordenes_trabajo` (+ mecánico, pago, factura) | Historial OT del vehículo | `WHERE vehiculo_id`, `ORDER BY created_at DESC` |

**Índices recomendados:**

- `(cliente_id, activo)` — portal y filtros
- `(vehiculo_id, created_at)` en `ordenes_trabajo` — historial
- Si la búsqueda `LIKE '%…%'` crece: extensión `pg_trgm` sobre `placa`, `marca`, `modelo`, `razon_social`

**Triggers:** no necesarios.

---

### 4.4 Mecánicos

| ID | Ubicación | Op | Tablas | Qué hace | Filtros / patrón |
|----|-----------|----|--------|----------|------------------|
| ME1 | `Mecanico*Controller` | S/I/U/D | `mecanicos` | CRUD | `ORDER BY nombres` |
| ME2 | `GroqDiagnosticService` | S | `mecanicos` | Sugerir por especialidad | `activo`; `LOWER(especialidad) LIKE %kw%` LIMIT 3 |

**Índices / constraints recomendados:**

- `UNIQUE (user_id)` WHERE `user_id IS NOT NULL` (hoy solo se valida en app)
- Índice de expresión / `pg_trgm` en `especialidad` si la IA sugiere con frecuencia

**Triggers:** no necesarios.

---

### 4.5 Servicios y productos (inventario)

| ID | Ubicación | Op | Tablas | Qué hace | Filtros / patrón |
|----|-----------|----|--------|----------|------------------|
| SE1 | `ServicioWebController::index` | S | `servicios` | Listado priorizado | `orderByRaw` CASE (diagnóstico computarizado primero) |
| SE2 | `Servicio*Controller` CRUD | I/U/D | `servicios` | ABM | `nombre` UNIQUE |
| PRD1 | `ProductoWebController::index` | S | `productos` | Inventario filtrable | `tipo_producto = repuesto`; ilike; categoría; stock bajo; activo |
| PRD2 | `ProductoWebController::index` | S | `productos` | Distinct categorías | `DISTINCT categoria` |
| PRD3 | `ProductoWebController` store/update/ajustarStock | I/U | `productos` | Alta / edición / ajuste stock | — |
| PRD4 | `ProductoWebController::destroy` | S/U/D | `orden_repuesto`, `productos` | Si está usado → desactivar; si no → borrar | `DB::table(...)->exists` |

**Índices recomendados en `productos`:**

```sql
CREATE INDEX idx_productos_tipo_activo ON productos (tipo_producto, activo);
CREATE INDEX idx_productos_tipo_categoria ON productos (tipo_producto, categoria);
-- Útil para reportes de stock bajo:
CREATE INDEX idx_productos_stock_bajo ON productos (tipo_producto, stock, stock_minimo)
  WHERE activo = true AND tipo_producto = 'repuesto';
```

**Constraint recomendado:**

```sql
ALTER TABLE productos ADD CONSTRAINT productos_stock_non_negative CHECK (stock >= 0);
```

**Triggers:** opcionales (ver sección 6.2 stock).

---

### 4.6 Órdenes de trabajo

| ID | Ubicación | Op | Tablas | Qué hace | Filtros / patrón |
|----|-----------|----|--------|----------|------------------|
| OT1 | `OrdenTrabajoWebController::index` | S | `ordenes_trabajo` + eager | Listado | Opcional `mecanico_id`; `ORDER BY created_at DESC` |
| OT2 | `store` | I TX | `ordenes_trabajo`, opcional `citas_taller` | Crear OT (+ cita) | `generarNumero()` |
| OT3 | `update` | U/D/I TX | OT, líneas, stock, cita | Actualizar OT e ítems | Sync servicios/repuestos |
| OT4 | `storeAvance` | I/U | `orden_avances`, OT | Bitácora | — |
| OT5 | `asignarMecanico` / `cambiarEstado` | U | OT, `citas_taller` | Asignación / estado | Sync `cita.mecanico_id` |
| OT6 | `destroy` | D | OT (+ restaurar stock) | Eliminar | — |
| OT7 | Options (formularios) | S | clientes, vehículos, mecánicos, servicios, productos | Catálogos | activos / tipo repuesto |

**`generarNumero()` (OT / factura / presupuesto):**

```text
SELECT numero FROM {tabla}
WHERE numero LIKE '{PREFIJO}-{Ymd}-%'
ORDER BY numero DESC
LIMIT 1
→ incrementar secuencia diaria
```

Cubierto por UNIQUE en `numero`, pero **no es atómico** bajo concurrencia (ver §6.3).

**Índices recomendados en `ordenes_trabajo`:**

```sql
CREATE INDEX idx_ot_estado ON ordenes_trabajo (estado);
CREATE INDEX idx_ot_mecanico_estado ON ordenes_trabajo (mecanico_id, estado);
CREATE INDEX idx_ot_mecanico_created ON ordenes_trabajo (mecanico_id, created_at DESC);
CREATE INDEX idx_ot_cliente_created ON ordenes_trabajo (cliente_id, created_at DESC);
CREATE INDEX idx_ot_vehiculo_created ON ordenes_trabajo (vehiculo_id, created_at DESC);
CREATE INDEX idx_ot_created ON ordenes_trabajo (created_at DESC);
```

**Triggers:** no obligatorios; la sincronización de estado/cita puede quedarse en app.

---

### 4.7 Stock de repuestos en OT

| ID | Ubicación | Op | Tablas | Qué hace | Filtros / patrón |
|----|-----------|----|--------|----------|------------------|
| ST1 | `OrdenRepuestoStockService::aplicarNuevos` | S/U/I | `productos`, `orden_repuesto` | Reservar stock | `lockForUpdate` + `decrement` |
| ST2 | `OrdenRepuestoStockService::reemplazar` | S/D + ST1 | líneas OT | Reemplazo completo | Restaurar + aplicar |
| ST3 | `OrdenRepuestoStockService::restaurar` | U | `productos` | Devolver stock | `increment` |
| ST4 | `ValidatesRepuestoStock` | S | `productos`, `orden_repuesto` | Validar stock disponible | `SUM(cantidad)` por OT + producto |

**Índice crítico:**

```sql
CREATE INDEX idx_orden_repuesto_ot_producto
  ON orden_repuesto (orden_trabajo_id, producto_id);
```

**Nota:** `CalendarioWebController::crearOrdenDesdeCita` puede insertar líneas de `orden_repuesto` copiadas del presupuesto **sin** pasar por `OrdenRepuestoStockService`. Conviene alinear ese flujo con el servicio de stock (corrección de aplicación; no solo índice).

---

### 4.8 Citas y calendario

| ID | Ubicación | Op | Tablas | Qué hace | Filtros / patrón |
|----|-----------|----|--------|----------|------------------|
| CI1 | `CalendarioWebController::index` | S | `citas_taller` + eager | Eventos del rango | `fecha_hora BETWEEN`; filtros por rol |
| CI2 | `index` | S | `ordenes_trabajo` | OTs del día (staff) | `DATE(created_at) = día` |
| CI3 | `DisponibilidadCitasService::slotsParaFecha` | S | `citas_taller` | Ocupación de slots | `whereDate(fecha_hora)`; `estado IN (programada, reagendada)` |
| CI4 | `store` / `agendarCliente` | I/U TX | `citas_taller`, `presupuestos` | Crear cita (± vincular presupuesto) | — |
| CI5 | `crearOrdenDesdeCita` | I/U TX | cita, OT, líneas | OT desde cita | Copia servicios/repuestos del presupuesto |
| CI6 | cancelar / reagendar / completar | U | `citas_taller` | Cambios de estado/fecha | — |
| CI7 | presupuestos disponibles | S | `presupuestos` | Selector al agendar | `cliente_id`; estados editables; `valido_hasta` |

**Índices:** `(fecha_hora, estado)` ya cubre CI1/CI3. Complementos útiles:

```sql
CREATE INDEX idx_citas_orden ON citas_taller (orden_trabajo_id);
CREATE INDEX idx_presupuestos_cliente_estado_validez
  ON presupuestos (cliente_id, estado, valido_hasta);
```

**Triggers:** no recomendados para capacidad de slots (la lógica de horarios es de aplicación). Un UNIQUE parcial por mecánico+hora solo tendría sentido si se define regla estricta de 1 cita/mecánico/slot.

---

### 4.9 Diagnóstico IA

| ID | Ubicación | Op | Tablas | Qué hace | Filtros / patrón |
|----|-----------|----|--------|----------|------------------|
| IA1 | `DiagnosticoIaWebController::index` | S | `diagnosticos_ia` | Listado | `with` OT; `whereHas` mecánico |
| IA2 | `create` | S | `ordenes_trabajo` | OTs sin diagnóstico | `whereDoesntHave('sugerenciaIa')`; estados pendientes |
| IA3 | `generar` / `store` | I/U TX | `diagnosticos_ia`, OT, catálogos | Generar y opcionalmente aplicar sugerencias | UNIQUE `orden_trabajo_id` |
| IA4 | `revisar` | U | `diagnosticos_ia`, OT | Confirmar / pasar a reparación | — |
| IA5 | `AplicarSugerenciaIaAOrdenService` | S/I/U | servicios, productos, OT, líneas | Aplicar sugerencias | `LOWER(nombre) LIKE`; carga catálogo activo en memoria |

**Índices:** UNIQUE de `orden_trabajo_id` suficiente para 1:1. Para reportes por estado: `(estado)`, `(es_simulado)`.  
**Triggers:** no necesarios (la UNIQUE ya impone un diagnóstico por OT).

---

### 4.10 Facturas

| ID | Ubicación | Op | Tablas | Qué hace | Filtros / patrón |
|----|-----------|----|--------|----------|------------------|
| FA1 | `FacturaWebController::index` | S | `facturas` | Listado | eager cliente/OT; `ORDER BY created_at DESC` |
| FA2 | `store` | I/U TX | `facturas`, `detalle_facturas`, OT, clientes | Emitir factura + detalle + snapshot cliente | `generarNumero()` |
| FA3 | show/edit/update | S/U | `facturas` | Consulta / edición | Bloqueo si anulada |
| FA4 | `destroy` | S/D | `facturas` | Borrar si no tiene pago | `EXISTS` pago |
| FA5 | `ordenesSinFacturaOptions` | S | `ordenes_trabajo` | Selector | `whereDoesntHave('factura')` |

**Índices recomendados:**

```sql
CREATE INDEX idx_facturas_estado ON facturas (estado);
CREATE INDEX idx_facturas_cliente_estado ON facturas (cliente_id, estado);
CREATE INDEX idx_facturas_created ON facturas (created_at DESC);
```

**Triggers:** opcionales para sync con pagos (preferible mantener en PHP junto a `PagoWebController`).

---

### 4.11 Pagos

| ID | Ubicación | Op | Tablas | Qué hace | Filtros / patrón |
|----|-----------|----|--------|----------|------------------|
| PA1 | `PagoWebController::index` | S | `pagos` | Listado | eager OT.cliente/vehiculo |
| PA2 | `store` / `update` | I/U | `pagos`, `facturas`, OT | Registrar/actualizar; sync montos/estado factura | UNIQUE por OT y factura |
| PA3 | `destroy` | D | `pagos` | Eliminar | — |
| PA4 | `ordenesSinPagoOptions` | S | `ordenes_trabajo` | Selector | `whereDoesntHave('pago')` |

**Índice recomendado (dashboard / reportes):**

```sql
CREATE INDEX idx_pagos_estado_created ON pagos (estado, created_at);
```

**Triggers:** no obligatorios; UNIQUE ya garantiza 1 pago por OT/factura.

---

### 4.12 Presupuestos

| ID | Ubicación | Op | Tablas | Qué hace | Filtros / patrón |
|----|-----------|----|--------|----------|------------------|
| PS1 | `PresupuestoWebController::index` | S | `presupuestos` | Búsqueda taller | `estado`; `ilike numero`; `orWhereHas` cliente/vehículo |
| PS2 | `PortalPresupuestoWebController` | S/I/U | `presupuestos` | CRUD portal | Scope `cliente_id` |
| PS3 | `PresupuestoLineasService::syncLineas` | D/I/U TX | líneas + catálogos + presupuesto | Reemplazar líneas y totales | Valida stock de catálogo |
| PS4 | `generarNumero` / `recalcularTotales` | S/U | `presupuestos` | Numeración PRE-… y sumas | `LIKE` diario |

**Índices:** `(cliente_id, estado)` ya existe. Ampliar con `valido_hasta` (§4.8). Búsquedas `ILIKE` → `pg_trgm` si el volumen lo exige.  
**Triggers:** no necesarios para totales (se recalculan en servicio).

---

### 4.13 Dashboard

Fuente: `app/Http/Controllers/DashboardController.php`

| ID | Op | Tabla | Consulta (resumen) | Índice sugerido |
|----|----|-------|--------------------|-----------------|
| DA1 | S COUNT | `ordenes_trabajo` | Abiertas: `estado NOT IN (finalizada, entregada, cancelada)` (± `mecanico_id`) | `(mecanico_id, estado)` / `(estado)` |
| DA2 | S COUNT | `facturas` | `estado IN (borrador, emitida)` | `(estado)` |
| DA3 | S SUM | `pagos` | `estado = pagado` y mes/año actuales | `(estado, created_at)` |
| DA4 | S COUNT | `clientes` | `estado = true` | `(estado)` |
| DA5 | S | `ordenes_trabajo` | Últimas 5 con cliente/vehículo | `(created_at DESC)` / `(mecanico_id, created_at DESC)` |

---

### 4.14 Reportes analíticos

Fuente: `src/Reporte/Application/Services/ReporteDatosService.php`

| ID | Op | Tablas | Consulta | Índice / nota |
|----|----|--------|----------|---------------|
| RE1 | S AGG | `ordenes_trabajo` | `GROUP BY estado` → conteos | `(estado)` |
| RE2 | S AGG | `pagos` | Pagados por `DATE(created_at)`, SUM total, LIMIT 30 | `(estado, created_at)` |
| RE3 | S JOIN AGG | `orden_servicio` ⨝ `servicios` | Top 10 servicios por uso/ingresos | FK `servicio_id` |
| RE4 | S JOIN AGG | `orden_repuesto` ⨝ `productos` | Top 10 repuestos por cantidad/ingresos | FK `producto_id`; `(orden_trabajo_id, producto_id)` |
| RE5 | S JOIN AGG | `ordenes_trabajo` ⨝ `clientes` | Top clientes por OT / vehículos distintos | `(cliente_id)` |
| RE6 | S AGG | `diagnosticos_ia` | Conteos por `estado` y `es_simulado` | `(estado)`, `(es_simulado)` |
| RE7 | S AGG | `productos` | KPIs inventario + stock bajo LIMIT 15 | índices de §4.5 |
| RE8 | S | `ordenes_trabajo` / `pagos` | Totales globales | mismos índices |

---

### 4.15 Portal cliente (resto)

| ID | Ubicación | Op | Tablas | Qué hace |
|----|-----------|----|--------|----------|
| PO1 | `misOrdenes` / `historial` / `mostrarOrden` | S | `ordenes_trabajo` (+ relaciones) | Scope `cliente_id IN (...)` |
| PO2 | `mostrarFactura` | S | `facturas` | Estados emitida/pagada |
| PO3 | notificaciones | S | `notifications` | Últimas del usuario |

Índices alineados con OT/facturas/clientes ya listados.

---

## 5. Matriz: consulta → necesidad de índice / trigger

| Área | Índice | Trigger / constraint | Prioridad |
|------|--------|----------------------|-----------|
| Login por email | Ya UNIQUE | No | — |
| Listado OT / dashboard / portal | **Sí** — ver §4.6 | No | Alta |
| Ingresos mes / reportes pagos | **Sí** `(estado, created_at)` | No | Alta |
| Inventario / stock bajo | **Sí** tipo+activo (+ stock) | **CHECK stock ≥ 0** | Alta |
| Validación stock en OT | **Sí** `(orden_trabajo_id, producto_id)` | Opcional trigger stock | Alta |
| Calendario / slots | Ya `(fecha_hora, estado)` | No (capacidad en app) | — |
| Presupuestos portal | Ampliar con `valido_hasta` | No | Media |
| Historial / búsqueda placa | Trigram si crece | No | Media |
| Sugerencia mecánico por especialidad | Trigram / LOWER | No | Baja |
| 1 diagnóstico / factura / pago por OT | Ya UNIQUE | No | — |
| `mecanicos.user_id` único | UNIQUE parcial | No | Media |
| Numeración OT/Factura/Presupuesto | UNIQUE ya existe | No trigger; **secuencia o lock** | Alta |
| Sync factura ↔ pago | No | Preferible app; trigger solo si hay escrituras externas | Baja |
| Totales presupuesto | No | Preferible app | Baja |

---

## 6. Recomendaciones detalladas

### 6.1 Migración de índices sugerida (PostgreSQL)

```sql
-- Órdenes de trabajo
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_ot_estado
  ON ordenes_trabajo (estado);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_ot_mecanico_estado
  ON ordenes_trabajo (mecanico_id, estado);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_ot_mecanico_created
  ON ordenes_trabajo (mecanico_id, created_at DESC);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_ot_cliente_created
  ON ordenes_trabajo (cliente_id, created_at DESC);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_ot_vehiculo_created
  ON ordenes_trabajo (vehiculo_id, created_at DESC);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_ot_created
  ON ordenes_trabajo (created_at DESC);

-- Pagos / facturas
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_pagos_estado_created
  ON pagos (estado, created_at);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_facturas_estado
  ON facturas (estado);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_facturas_cliente_estado
  ON facturas (cliente_id, estado);

-- Inventario y líneas
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_productos_tipo_activo
  ON productos (tipo_producto, activo);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_orden_repuesto_ot_producto
  ON orden_repuesto (orden_trabajo_id, producto_id);

-- Clientes / vehículos / presupuestos / citas
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_clientes_estado
  ON clientes (estado);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_vehiculos_cliente_activo
  ON vehiculos (cliente_id, activo);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_presupuestos_cliente_estado_validez
  ON presupuestos (cliente_id, estado, valido_hasta);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_citas_orden
  ON citas_taller (orden_trabajo_id);

-- Mecánico ↔ usuario (1:1)
CREATE UNIQUE INDEX CONCURRENTLY IF NOT EXISTS idx_mecanicos_user_id_unique
  ON mecanicos (user_id)
  WHERE user_id IS NOT NULL;
```

> En Laravel, preferir una migración Eloquent con `$table->index(...)` / `unique(...)` en lugar de SQL crudo, salvo índices parciales/expresión que Blueprint no exprese bien.

### 6.2 ¿Hace falta un trigger de stock?

**Situación actual:** `OrdenRepuestoStockService` usa `lockForUpdate` + `decrement`/`increment` dentro de transacciones. Eso es correcto para un solo punto de entrada.

**Cuándo sí conviene reforzar en BD:**

1. **CHECK** `stock >= 0` — barato y evita stock negativo aunque falle la app.
2. Trigger `AFTER INSERT/UPDATE/DELETE ON orden_repuesto` que ajuste `productos.stock` — solo si hay **varios writers** (jobs, scripts, otro servicio) o se quiere la regla “la línea es la fuente de verdad”.
3. Mientras el único writer sea Laravel y se unifique el flujo de “OT desde cita”, **no es obligatorio** el trigger; sí es obligatorio alinear `crearOrdenDesdeCita` con el servicio de stock.

### 6.3 ¿Hace falta trigger para numeración?

No. Mejor opciones:

- Tabla/secuencia por prefijo diario, o
- `pg_advisory_xact_lock(hashtext(prefijo))` antes de leer el máximo, o
- Insertar y, ante violación UNIQUE, reintentar.

El UNIQUE actual evita duplicados persistidos pero puede hacer fallar requests concurrentes sin retry.

### 6.4 ¿Hace falta trigger para estados (factura/pago/cita)?

**No como primera opción.** Las reglas son de flujo de negocio y ya están en controladores/servicios. Un trigger de sync factura↔pago solo aportaría si hubiera escrituras fuera de la app. Mantener la lógica en PHP facilita pruebas y despliegue.

### 6.5 Vistas materializadas (opcional)

Para `ReporteDatosService` (tops y agregados), si el volumen crece, evaluar vistas materializadas refrescadas periódicamente (p. ej. `mv_servicios_top`, `mv_ingresos_diarios`). No son necesarias al inicio del proyecto.

### 6.6 Extensiones PostgreSQL

| Extensión | Uso |
|-----------|-----|
| `pgcrypto` | Ya requerida por tokens Sanctum (`gen_random_uuid`) |
| `pg_trgm` | Búsquedas `ILIKE` / `LOWER(...) LIKE` en placa, cliente, especialidad, número de presupuesto |

---

## 7. Riesgos y observaciones

1. **Concurrencia en `generarNumero()`** — OT, factura y presupuesto.
2. **Stock:** buen patrón con `lockForUpdate`, pero el alta de OT desde cita puede saltarse el descuento.
3. **Cascades:** borrar cliente/OT puede eliminar historial financiero (`CASCADE`). Valorar `RESTRICT` en facturas/pagos si se necesita auditoría estricta.
4. **Catálogo completo en memoria** (`AplicarSugerenciaIaAOrdenService`) — aceptable con pocos ítems; a escala, full-text o trigram.
5. **Seeders legacy** (`categorias`) no coinciden con el esquema actual (`productos.categoria` string).
6. **Sin soft deletes** — los borrados son físicos (salvo desactivación lógica de productos usados).

---

## 8. Mapa rápido archivo → consultas

| Archivo | Consultas clave |
|---------|-----------------|
| `app/Http/Controllers/DashboardController.php` | DA1–DA5 |
| `app/Services/DisponibilidadCitasService.php` | CI3 |
| `app/Services/ClienteCuentaService.php` | CL5 |
| `app/Services/OrdenRepuestoStockService.php` | ST1–ST3 |
| `app/Services/AplicarSugerenciaIaAOrdenService.php` | IA5 |
| `app/Services/GroqDiagnosticService.php` | ME2 |
| `src/OrdenTrabajo/.../OrdenTrabajoWebController.php` | OT1–OT7 |
| `src/Cita/.../CalendarioWebController.php` | CI1–CI7 |
| `src/Factura/.../FacturaWebController.php` | FA1–FA5 |
| `src/Pago/.../PagoWebController.php` | PA1–PA4 |
| `src/DiagnosticoIA/.../DiagnosticoIaWebController.php` | IA1–IA4 |
| `src/Presupuesto/...` | PS1–PS4 |
| `src/Producto/.../ProductoWebController.php` | PRD1–PRD4 |
| `src/Cliente/.../ClienteWebController.php` | CL1–CL4 |
| `src/Reporte/.../ReporteDatosService.php` | RE1–RE8 |
| `src/Reporte/.../PortalClienteWebController.php` | PO1–PO3, VE2 |
| `src/Reporte/.../HistorialWebController.php` | VE3–VE4 |
| `src/Auth/.../UsuarioWebController.php` | AU4–AU5 |

---

## 9. Conclusión

La aplicación concentra las consultas en **Eloquent** sobre un esquema PostgreSQL bien normalizado, con **UNIQUE** fuertes en numeración y relaciones 1:1 (diagnóstico, factura, pago). **No hay triggers** hoy; la mayoría de reglas de negocio pueden y deben permanecer en la capa PHP.

Lo más rentable a nivel BD es:

1. Añadir los **índices** de `ordenes_trabajo`, `pagos`, `facturas`, `productos` y `orden_repuesto`.
2. Añadir **CHECK (stock >= 0)** y unificar el descuento de stock en todos los flujos.
3. Endurecer **`generarNumero()`** con lock o retry (sin trigger).
4. Valorar **`pg_trgm`** y UNIQUE parcial de `mecanicos.user_id` cuando el volumen o la integridad lo pidan.

Los triggers de sincronización de estados o totales **no son necesarios** mientras Laravel sea el único escritor y las transacciones actuales se mantengan consistentes.
