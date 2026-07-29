# Consultas a base de datos — AutoFix-IA

Documento de referencia del acceso a PostgreSQL en el sistema de taller Autofix: esquema, consultas por módulo, índices existentes, recomendaciones (índices, triggers, constraints, secuencias) y riesgos de concurrencia.

**Stack:** Laravel 12 + Eloquent · PostgreSQL · PK UUID · sin SoftDeletes en entidades de negocio.

**Alcance:** código de aplicación (`src/`, `app/Services`, `app/Http/Controllers`). No incluye seeders ni tests, salvo cuando ilustran unicidad o flujos críticos.

---

## Tabla de contenidos

1. [Mapa del dominio](#1-mapa-del-dominio)
2. [Esquema e índices actuales](#2-esquema-e-índices-actuales)
3. [Consultas por módulo](#3-consultas-por-módulo)
4. [Índices recomendados](#4-índices-recomendados)
5. [Triggers, vistas, constraints y secuencias](#5-triggers-vistas-constraints-y-secuencias)
6. [Transacciones y concurrencia](#6-transacciones-y-concurrencia)
7. [Prioridades de endurecimiento](#7-prioridades-de-endurecimiento)

---

## 1. Mapa del dominio

Flujo principal:

```text
Cliente → Vehículo → Cita / Presupuesto → Orden de trabajo
  → Diagnóstico IA (opcional)
  → Factura (1:1 con OT) → Pago (1:1 con OT/factura)
```

Relaciones 1:1 reforzadas en BD con `UNIQUE` sobre FK:

| Relación | Columna UNIQUE |
|----------|----------------|
| OT ↔ Diagnóstico IA | `diagnosticos_ia.orden_trabajo_id` |
| OT ↔ Factura | `facturas.orden_trabajo_id` |
| OT ↔ Pago | `pagos.orden_trabajo_id` |
| Factura ↔ Pago | `pagos.factura_id` |

---

## 2. Esquema e índices actuales

> En PostgreSQL, una FK **no** crea índice automático. Solo aparecen aquí los índices/UNIQUE declarados en migraciones.

### 2.1 Catálogo y maestros

| Tabla | Claves / UNIQUE | Índices explícitos | Notas |
|-------|-----------------|--------------------|-------|
| `users` | UNIQUE `email` | — | `role`, `activo` |
| `clientes` | UNIQUE `numero_documento`, `email` | — | FK `user_id` → users **sin index** |
| `vehiculos` | UNIQUE `placa` | — | FK `cliente_id` **sin index** |
| `mecanicos` | UNIQUE `documento` | — | FK `user_id` **sin index** |
| `servicios` | UNIQUE `nombre` | — | |
| `productos` | UNIQUE `codigo` | — | Filtros frecuentes: `tipo_producto`, `activo`, `stock` |

### 2.2 Operación del taller

| Tabla | Claves / UNIQUE | Índices explícitos | Notas |
|-------|-----------------|--------------------|-------|
| `ordenes_trabajo` | UNIQUE `numero` | — | FKs `cliente_id`, `vehiculo_id`, `mecanico_id`, `created_by`, `updated_by` sin index |
| `orden_servicio` | — | — | FKs sin index |
| `orden_repuesto` | — | — | FKs sin index; stock se mueve en PHP |
| `orden_avances` | — | `(orden_trabajo_id, created_at)` | Bitácora |
| `diagnosticos_ia` | UNIQUE `orden_trabajo_id` | — | 1 diagnóstico por OT |
| `citas_taller` | — | `(fecha_hora, estado)`, `mecanico_id`, `cliente_id` | FKs `vehiculo_id`, `orden_trabajo_id`, `presupuesto_id` sin index |
| `presupuestos` | UNIQUE `numero` | `(cliente_id, estado)` | |
| `presupuesto_servicios` / `_repuestos` | — | — | FKs sin index adicional |

### 2.3 Facturación y pagos

| Tabla | Claves / UNIQUE | Índices explícitos | Notas |
|-------|-----------------|--------------------|-------|
| `facturas` | UNIQUE `numero`, UNIQUE `orden_trabajo_id` | — | Snapshot fiscal del cliente; FK `cliente_id` sin index |
| `detalle_facturas` | — | — | FK `factura_id` sin index |
| `pagos` | UNIQUE `orden_trabajo_id`, UNIQUE `factura_id` | — | |

### 2.4 Infra Laravel

| Tabla | Índices / UNIQUE relevantes |
|-------|-----------------------------|
| `personal_access_tokens` | UNIQUE `token`; morph `tokenable`; `expires_at` |
| `sessions` | `user_id`, `last_activity` |
| `notifications` | morph `notifiable` |
| `jobs` / `failed_jobs` / `cache` | cola y uuid según migraciones Laravel |

---

## 3. Consultas por módulo

Leyenda: **F** = frecuente (pantallas del día a día) · **P** = potencialmente pesada a escala.

### 3.1 Dashboard — `app/Http/Controllers/DashboardController.php`

| Método | Propósito | Tablas | Filtros / eager | Tipo | Frecuencia |
|--------|-----------|--------|-----------------|------|------------|
| `index` | Órdenes abiertas | `ordenes_trabajo` | `mecanico_id` (rol mecánico); `estado NOT IN (finalizada, entregada, cancelada)` | SELECT COUNT | **F** |
| `index` | Facturas pendientes | `facturas` | `estado IN (borrador, emitida)` | SELECT COUNT | **F** |
| `index` | Ingresos del mes | `pagos` | `estado = pagado` + mes/año de `created_at` | SELECT SUM | **F** |
| `index` | Clientes activos | `clientes` | `estado = true` | SELECT COUNT | **F** |
| `index` | Órdenes recientes | `ordenes_trabajo` + cliente/vehículo | `ORDER BY created_at DESC LIMIT 5` + `with` | SELECT | **F** |

SQL equivalente (órdenes abiertas):

```sql
SELECT COUNT(*) FROM ordenes_trabajo
WHERE mecanico_id = :id  -- solo rol mecánico
  AND estado NOT IN ('finalizada', 'entregada', 'cancelada');
```

### 3.2 Auth y usuarios

| Archivo / método | Propósito | Consulta efectiva | Tipo |
|------------------|-----------|-------------------|------|
| `AuthController` / `WebAuthController` register | Alta usuario | INSERT `users` | INSERT |
| Login | Autenticación | `WHERE email = ?` | SELECT |
| Logout API | Revocar token | DELETE `personal_access_tokens` | DELETE |
| `UsuarioWebController::index` | Listado + stats | `ORDER BY name`; filtros `role`, `ILIKE name/email`; agregados por rol | SELECT **F** |
| CRUD usuarios | Mantener cuentas | find / create / update / delete | CRUD |

### 3.3 Cliente — `ClienteWebController` / `ClienteController`

| Método | Propósito | Detalle | Tipo |
|--------|-----------|---------|------|
| `index` (web) | Listado | `with(vehiculos ORDER BY placa)`, `ORDER BY razon_social`, paginate + counts por `estado` | SELECT **F** |
| `index` (API) | Todos | `Cliente::all()` sin paginar | SELECT **P** |
| `store` / `update` | Alta/edición | INSERT/UPDATE | CRUD |
| `destroy` | Baja | Bloquea si `vehiculos()->exists()` | SELECT + DELETE |

### 3.4 Vehículo, servicio, mecánico, producto

CRUD estándar + listados:

| Módulo | Consultas relevantes |
|--------|----------------------|
| **Vehículo** | `with(cliente)`, `ORDER BY placa`, paginate; destroy hard delete |
| **Servicio** | `ORDER BY` prioriza “diagnóstico computarizado”, luego `nombre` |
| **Mecánico** | Listado por nombres; opciones de usuario excluyen `user_id` ya asignados |
| **Producto (inventario)** | `WHERE tipo_producto = 'repuesto'` + `ILIKE` codigo/nombre/proveedor + `categoria` + stock bajo (`stock <= stock_minimo`) + `activo` → **F/P**. Al borrar: `EXISTS` en `orden_repuesto`; si está en uso, desactiva en lugar de borrar |

### 3.5 Orden de trabajo — crítico

**Controladores:** `OrdenTrabajoWebController`, `OrdenTrabajoController`  
**Stock:** `app/Services/OrdenRepuestoStockService.php`  
**Modelo:** `OrdenTrabajoEloquentModel::generarNumero()`

| Método | Propósito | Tablas | Detalle | Tipo |
|--------|-----------|--------|---------|------|
| `index` | Listado OT | OT + relaciones | filtro `mecanico_id`; `ORDER BY created_at DESC`; eager amplio | SELECT **F/P** |
| `store` | Crear OT (+cita opcional) | OT, citas | `DB::transaction`; `generarNumero()` | INSERT |
| `update` | Editar OT/líneas | OT, `orden_servicio`, `orden_repuesto`, productos, citas | transaction; sync servicios; `stockService->reemplazar` | MIX |
| `destroy` | Borrar OT | OT, productos | restaura stock + delete (cascade líneas) | DELETE |
| `storeAvance` | Bitácora | `orden_avances` | INSERT + UPDATE `updated_by` | INSERT/UPDATE |
| `asignarMecanico` / `cambiarEstado` | Workflow | OT [, cita] | UPDATE estado/mecánico | UPDATE |

Generación de número:

```sql
SELECT numero FROM ordenes_trabajo
WHERE numero LIKE 'OT-YYYYMMDD-%'
ORDER BY numero DESC
LIMIT 1;
-- luego INSERT con OT-YYYYMMDD-NNN
```

Stock seguro (`aplicarNuevos`):

```sql
BEGIN;
SELECT * FROM productos WHERE id = :pid FOR UPDATE;
UPDATE productos SET stock = stock - :qty WHERE id = :pid;  -- previo check stock >= qty
INSERT INTO orden_repuesto (...);
COMMIT;
```

### 3.6 Citas — `CalendarioWebController` + `DisponibilidadCitasService`

| Método | Propósito | Consulta | Tipo |
|--------|-----------|----------|------|
| `index` | Agenda por rango | `with(...)` + `WHERE fecha_hora BETWEEN` + filtros por rol | SELECT **F** |
| `index` | OTs del día | `whereDate(created_at)` | SELECT |
| `disponibilidad` / slots | Cupos | `whereDate(fecha_hora)` + `estado IN (programada, reagendada)` | SELECT **F** |
| `store` / `agendar` | Crear cita | INSERT; agendar en transaction + UPDATE presupuesto | INSERT/UPDATE |
| `crearOt` | OT desde cita+presupuesto | transaction; copia líneas presupuesto → `orden_*` **sin descontar stock** | INSERT ⚠️ |
| cancelar / reagendar / completar | Estados | UPDATE estado/fecha | UPDATE |

### 3.7 Presupuesto

| Archivo | Propósito | Detalle |
|---------|-----------|---------|
| `PresupuestoWebController` | Listado taller | `with(cliente, vehiculo)`; filtro `estado`; búsqueda `ILIKE numero` / `whereHas` cliente o placa |
| `PortalPresupuestoWebController` | CRUD portal | `generarNumero()` `PRE-YYYYMMDD-NNN`; transactions en store/update |
| `PresupuestoLineasService::syncLineas` | Sync líneas | transaction: DELETE líneas → valida servicio/producto activos y stock (solo lectura) → INSERT → UPDATE totales. **No reserva stock.** |

### 3.8 Factura

| Método | Propósito | Detalle | Tipo |
|--------|-----------|---------|------|
| `index` | Listado | `with(cliente, ordenTrabajo) ORDER BY created_at DESC` | SELECT **F** |
| `store` | Emitir | transaction: INSERT factura + detalles; opcional UPDATE cliente; UNIQUE OT evita duplicado | INSERT |
| `update` | Recalcular | Recalcula desde OT si cambia descuento | UPDATE |
| `destroy` | Borrar | Bloquea si existe `pago` | DELETE |
| `ordenesSinFacturaOptions` | Select create | `whereDoesntHave('factura')` + líneas | SELECT |

`FacturaEloquentModel::generarNumero()` sigue el mismo patrón SELECT último + INSERT que OT/presupuesto (`F-YYYYMMDD-NNN` / serie configurable).

### 3.9 Pago

| Método | Propósito | Detalle | Tipo |
|--------|-----------|---------|------|
| `store` / `update` | Registrar / actualizar | INSERT/UPDATE pago; sincroniza montos/estado de factura (**sin** `DB::transaction`) | INSERT/UPDATE ⚠️ |
| `ordenesSinPagoOptions` | Select | `whereDoesntHave('pago')` | SELECT |
| `destroy` | Eliminar | DELETE duro; **no** revierte estado de factura | DELETE ⚠️ |

### 3.10 Diagnóstico IA

| Método | Propósito | Detalle | Tipo |
|--------|-----------|---------|------|
| `index` | Listado | `with(orden.cliente, vehiculo)`; mecánico: `whereHas` por `mecanico_id` | SELECT |
| `create` | OT sin diagnóstico | `whereDoesntHave('sugerenciaIa')` + estados pendientes | SELECT |
| `store` | Generar | transaction: INSERT diagnóstico + UPDATE OT + `AplicarSugerenciaIa` (servicios/stock) | INSERT/UPDATE |
| `revisar` | Validar | UPDATE diagnóstico; puede pasar OT a en reparación | UPDATE |

`GroqDiagnosticService` / `AplicarSugerenciaIaAOrdenService` cargan catálogos de servicios, productos y mecánicos (a veces enteros o filtrados por especialidad) → **P** en memoria si el catálogo crece.

### 3.11 Reportes, portal e historial

#### `ReporteDatosService::recopilar` — **P/F**

Agregaciones por request de pantalla/export:

```sql
SELECT estado, COUNT(*) FROM ordenes_trabajo GROUP BY estado;

SELECT DATE(created_at), SUM(total) FROM pagos
WHERE estado = 'pagado'
GROUP BY DATE(created_at)
ORDER BY 1 DESC LIMIT 30;

-- Top servicios / repuestos: JOIN + GROUP BY + LIMIT 10
-- Vehículos por cliente: JOIN clientes + COUNT
-- Inventario: COUNT / whereColumn(stock, stock_minimo) / SUM(precio * stock)
```

#### Portal cliente (`PortalClienteWebController`)

- Vehículos / órdenes / historial filtrados por `cliente_id` o `user_id` del cliente logueado.
- Factura visible si estado emitida/pagada.
- `actualizarDatos`: transaction sobre `clientes` + `users`.

#### Historial (`HistorialWebController`)

- `vehiculos` + `withCount(ordenesTrabajo)` + búsqueda `LOWER(...) LIKE`.
- Show: órdenes del vehículo con pago/factura.

### 3.12 Notificadores

| Servicio | Consulta | Efecto |
|----------|----------|--------|
| `CitaNotifier` | `users WHERE activo AND role IN (admin, recepcionista)` | INSERT `notifications` |
| `OrdenEstadoNotifier` / `FacturaClienteNotifier` | Carga relaciones OT/factura/cliente | INSERT `notifications` |

---

## 4. Índices recomendados

### 4.1 Ya útiles (mantener)

- UNIQUE de negocio: documentos, emails, placas, códigos, números OT/factura/presupuesto.
- 1:1 duros OT ↔ factura / pago / diagnóstico.
- Citas: `(fecha_hora, estado)`, `mecanico_id`, `cliente_id`.
- Presupuestos: `(cliente_id, estado)`.
- Avances: `(orden_trabajo_id, created_at)`.

### 4.2 Prioridad alta

Propuesta de migración Laravel (nombres orientativos):

| Índice sugerido | Columnas | Por qué |
|-----------------|----------|---------|
| `ordenes_trabajo_estado_created_at_index` | `(estado, created_at)` | Dashboard abiertas, listados, reportes |
| `ordenes_trabajo_mecanico_id_index` | `(mecanico_id)` | Filtro mecánico (dashboard, OT, IA) |
| `ordenes_trabajo_cliente_id_index` | `(cliente_id)` | Portal, historial, joins reportes |
| `ordenes_trabajo_vehiculo_id_index` | `(vehiculo_id)` | Historial por vehículo, `withCount` |
| `orden_servicio_orden_trabajo_id_index` | `(orden_trabajo_id)` | Eager líneas / tops |
| `orden_servicio_servicio_id_index` | `(servicio_id)` | JOIN top servicios |
| `orden_repuesto_orden_trabajo_id_index` | `(orden_trabajo_id)` | Eager / restaurar stock |
| `orden_repuesto_producto_id_index` | `(producto_id)` | JOIN top + `EXISTS` al borrar producto |
| `productos_tipo_activo_categoria_index` | `(tipo_producto, activo, categoria)` | Inventario y catálogos |
| `pagos_estado_created_at_index` | `(estado, created_at)` | Ingresos mes/día |
| `facturas_estado_index` | `(estado)` | Dashboard pendientes |
| `facturas_cliente_id_index` | `(cliente_id)` | Portal facturas |
| `clientes_user_id_index` | `(user_id)` | Portal: `WHERE user_id = ?` (**muy frecuente**) |
| `vehiculos_cliente_id_index` | `(cliente_id)` | Listados / portal |
| `detalle_facturas_factura_id_index` | `(factura_id)` | Show factura |
| `presupuesto_servicios_presupuesto_id_index` | `(presupuesto_id)` | Sync / show |
| `presupuesto_repuestos_presupuesto_id_index` | `(presupuesto_id)` | Sync / show |
| `citas_taller_vehiculo_id_index` | `(vehiculo_id)` | FK / joins |
| `users_role_activo_index` | `(role, activo)` | Notifiers + filtros usuarios |
| `mecanicos_user_id_index` | `(user_id)` | `$user->mecanico` |

Ejemplo de migración:

```php
Schema::table('ordenes_trabajo', function (Blueprint $table) {
    $table->index(['estado', 'created_at']);
    $table->index('mecanico_id');
    $table->index('cliente_id');
    $table->index('vehiculo_id');
});

Schema::table('clientes', function (Blueprint $table) {
    $table->index('user_id');
});

Schema::table('pagos', function (Blueprint $table) {
    $table->index(['estado', 'created_at']);
});
```

### 4.3 Prioridad media

| Necesidad | Recomendación |
|-----------|---------------|
| Búsquedas `ILIKE '%texto%'` en nombre/placa/proveedor | Extensión `pg_trgm` + índice GIN (`gin_trgm_ops`) si el catálogo crece; un B-tree no ayuda con comodín a la izquierda |
| Prefijo `LIKE 'OT-20260729-%'` | El UNIQUE btree sobre `numero` ya es suficiente para el patrón de prefijo |
| Stock bajo | Índice parcial opcional: `WHERE tipo_producto = 'repuesto' AND activo AND stock <= stock_minimo` |

---

## 5. Triggers, vistas, constraints y secuencias

| Candidato | ¿Crear en BD? | Justificación |
|-----------|---------------|---------------|
| **Trigger de stock** en `orden_repuesto` | **Recomendado endurecer** (trigger **o** unificar app) | Hoy el descuento es PHP (`OrdenRepuestoStockService`). `CalendarioWebController::crearOt` inserta líneas **sin** descontar → stock inconsistente. Un trigger `BEFORE INSERT/UPDATE/DELETE` centralizaría el movimiento; alternativa mínima: siempre pasar por el service. |
| **Trigger totales de factura** | **No** | Totales se calculan en PHP (`FacturaEloquentModel::calcularDesdeOrden`) y se sincronizan desde pagos. El snapshot fiscal es intencional. |
| **Trigger estado factura ↔ pago** | **No** (mejor transaction PHP) | La app ya sincroniza; falta envolver store/update de pago en `DB::transaction`. |
| **Vista materializada de reportes** | **No por ahora** / **Sí si escala** | `ReporteDatosService` hace ~10 agregaciones por request. Con pocos miles de filas basta. Si crece: MV de ingresos diarios + refresh, o cache Redis. |
| **UNIQUE adicionales** | Parcial | Ya hay 1:1 OT↔factura/pago/diagnóstico. Útiles: UNIQUE parcial de citas activas por slot; UNIQUE `mecanicos.user_id` WHERE NOT NULL (hoy se valida en Request). |
| **CHECK constraints** | **Sí (ligeros)** | `productos.stock >= 0`; `pagos.total >= 0`; `orden_repuesto.cantidad > 0`; opcional CHECK de enums/estados. La app valida, la BD es red de seguridad. |
| **Secuencia / contador de números** (OT, factura, presupuesto) | **Sí** | `generarNumero()` hace SELECT del último + INSERT → carrera concurrente (UNIQUE evita duplicado con error, no genera el siguiente). Mejor: tabla `document_sequences` con `UPDATE ... RETURNING` o `nextval` por serie/día. |
| **Función / lock de cupos de cita** | **Opcional** | Disponibilidad = read + insert sin lock → se puede superar `max_citas_por_slot`. Mitigar con re-conteo `FOR UPDATE` en transaction o exclusion constraint. |

### Ejemplo CHECK (PostgreSQL)

```sql
ALTER TABLE productos
  ADD CONSTRAINT productos_stock_non_negative CHECK (stock >= 0);

ALTER TABLE orden_repuesto
  ADD CONSTRAINT orden_repuesto_cantidad_positive CHECK (cantidad > 0);
```

### Ejemplo contador atómico (idea)

```sql
CREATE TABLE document_sequences (
  serie varchar(16) NOT NULL,
  fecha date NOT NULL,
  last_value integer NOT NULL DEFAULT 0,
  PRIMARY KEY (serie, fecha)
);

-- En transacción:
INSERT INTO document_sequences (serie, fecha, last_value)
VALUES ('OT', CURRENT_DATE, 1)
ON CONFLICT (serie, fecha)
DO UPDATE SET last_value = document_sequences.last_value + 1
RETURNING last_value;
```

---

## 6. Transacciones y concurrencia

### 6.1 Usos actuales de `DB::transaction`

| Ubicación | Qué protege |
|-----------|-------------|
| OT store/update/destroy (Web + API) | OT + cita/líneas + stock |
| Factura store (Web + API) | Factura + detalles (+ cliente) |
| Diagnóstico IA store | Diagnóstico + OT + sugerencias/stock |
| Presupuesto portal store/update + `syncLineas` | Cabecera + líneas + totales |
| Cita `agendar` / `crearOt` | Presupuesto+cita / OT+líneas+vínculo |
| Portal `actualizarDatos` | Cliente + user |

### 6.2 `lockForUpdate`

Solo en `OrdenRepuestoStockService::aplicarNuevos`. Es correcto **únicamente** si el caller ya abrió una transaction (sí en OT / Diagnóstico). Fuera de transaction, en PostgreSQL el lock no se mantiene entre statements.

`restaurar` / `increment` de stock **no** usan `lockForUpdate`.

### 6.3 Race conditions reales

1. **Números correlativos** (`OT-` / `F-` / `PRE-`)  
   Dos requests leen el mismo último número → mismo correlativo → uno falla por UNIQUE.  
   Mitigación: secuencias/contadores con row lock (§5).

2. **Stock**  
   - Bien: update OT / IA dentro de transaction + `lockForUpdate`.  
   - Mal: `crearOt` crea `orden_repuesto` **sin** decrementar.  
   - Presupuesto valida stock pero no reserva → oversell entre presupuestos/OTs.

3. **Cupos de cita**  
   Check de disponibilidad + `create` sin lock → superar cupo del slot.

4. **Pagos ↔ factura**  
   Store de pago sin transaction: si falla el UPDATE de factura tras el INSERT, estados desalineados.  
   Destroy de pago no revierte `facturas.estado`.

5. **Doble factura / doble pago**  
   Cubierto por UNIQUE en BD. Correcto.

---

## 7. Prioridades de endurecimiento

Orden sugerido de trabajo (sin estimar calendario; por impacto técnico):

1. **Índices FK/filtro** en `ordenes_trabajo`, `orden_*`, `productos`, `clientes.user_id`, `pagos(estado, created_at)`, `facturas.estado` / `cliente_id`.
2. **Unificar descuento de stock** (incluir `crearOt`) o trigger en BD + `CHECK (stock >= 0)`.
3. **Secuencias atómicas** para números de documento (OT, factura, presupuesto).
4. **Transaction** en flujo pago ↔ factura; revisar delete de pago (revertir estado o soft-cancel).
5. **Vistas materializadas / cache** solo si reportes se vuelven lentos; hoy la lógica en PHP es aceptable a volumen bajo/medio.
6. **pg_trgm** si las búsquedas `ILIKE '%q%'` se notan lentas en inventario/clientes/placas.

---

## Referencia rápida de archivos

| Área | Rutas principales |
|------|-------------------|
| Migraciones de dominio | `src/*/Infrastructure/Migrations/` |
| Migraciones infra | `database/migrations/` |
| Modelos Eloquent | `src/*/Infrastructure/Models/` |
| Controladores web/API | `src/*/Application/Controllers/` |
| Dashboard | `app/Http/Controllers/DashboardController.php` |
| Stock | `app/Services/OrdenRepuestoStockService.php` |
| Disponibilidad citas | `app/Services/DisponibilidadCitasService.php` |
| Reportes | `src/Reporte/Application/Services/ReporteDatosService.php` |
| Números de documento | `*EloquentModel::generarNumero()` en OT, Factura, Presupuesto |

---

*Documento generado a partir del código del repositorio. Actualizar al agregar módulos, filtros frecuentes o reglas de integridad nuevas.*
