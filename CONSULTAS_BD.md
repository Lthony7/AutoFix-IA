# Documento de Consultas a Base de Datos

## 1) Objetivo

Este documento resume las consultas a base de datos identificadas en el proyecto (Laravel + Eloquent/Query Builder), indicando:

- Qué consultas se hacen y en qué archivo/método.
- Qué tablas y filtros se usan.
- Cuándo conviene crear **índices**.
- Si existe necesidad de **triggers** o si la lógica debe quedarse en aplicación.

---

## 2) Consultas detectadas por módulo

> Nota: La mayoría de operaciones son Eloquent (no SQL crudo), por lo que aquí se describe su comportamiento equivalente en BD.

### 2.1 App Services (`app/Services`)

#### `OrdenRepuestoStockService.php`
- **SELECT + lockForUpdate** sobre `productos` por `id` para reservar stock.
- **UPDATE** `productos.stock` con `decrement()` / `increment()`.
- **INSERT** en `orden_repuesto` para registrar líneas.
- **DELETE** en `orden_repuesto` al reemplazar líneas de una orden.
- **Propósito:** consistencia de stock durante altas/ediciones de órdenes.

#### `DisponibilidadCitasService.php`
- **SELECT** en `citas_taller` por fecha (`whereDate(fecha_hora)`), estado y exclusión opcional de cita.
- **Propósito:** calcular slots ocupados y validar disponibilidad.

#### `ClienteCuentaService.php`
- **SELECT** en `clientes` por `user_id`, `email`, `numero_documento`.
- **UPDATE** para vincular `user_id` a cliente existente.
- **INSERT** para crear cliente automáticamente.
- **Propósito:** consolidar cuenta de cliente con usuario autenticado.

#### `AplicarSugerenciaIaAOrdenService.php`
- **SELECT** en `servicios`, `productos` (activos) para mapear sugerencias IA.
- **INSERT** en `orden_servicio` y `orden_repuesto`.
- **UPDATE** en `ordenes_trabajo` y `citas_taller`.
- **Propósito:** aplicar sugerencias IA a una orden/cita existente.

#### `CitaNotifier.php`, `OrdenEstadoNotifier.php`, `FacturaClienteNotifier.php`
- **SELECT** de usuarios/clientes/relaciones para construir notificaciones.
- **INSERT** implícito en `notifications` (vía sistema de notificaciones Laravel).

#### `GroqDiagnosticService.php`
- **SELECT** en `mecanicos` con `where activo`, `orderBy nombres`, y búsquedas `LOWER(especialidad) LIKE`.
- **Propósito:** sugerencia de mecánicos para diagnóstico.

---

### 2.2 Dashboard (`app/Http/Controllers/DashboardController.php`)

- **SELECT + AGREGACIONES**:
  - `count()` en `ordenes_trabajo` con filtros de estado/mecánico.
  - `count()` de facturas pendientes por estado.
  - `sum(total)` en `pagos` por estado y mes/año.
  - `count()` de clientes activos.
- **SELECT con relaciones** para últimas órdenes.
- **Propósito:** indicadores del panel principal.

---

### 2.3 Módulos de dominio (`src/*`)

#### Auth (`src/Auth`)
- CRUD de `users`.
- Tokenización Sanctum (`personal_access_tokens`): INSERT/DELETE.
- Listado de usuarios con filtro por rol/búsqueda (`ilike`) y paginación.

#### Cliente (`src/Cliente`)
- CRUD de `clientes`.
- Listados con `with('vehiculos')`, conteos por estado.
- Validación previa de eliminación: `vehiculos()->exists()`.

#### Vehículo (`src/Vehiculo`)
- CRUD de `vehiculos`.
- Listados ordenados por placa con relación `cliente`.
- Opciones de cliente activo para formularios.

#### Mecánico (`src/Mecanico`)
- CRUD de `mecanicos`.
- Listados paginados por nombre.
- Búsqueda de usuarios disponibles para vinculación (`users.role='mecanico'`, `activo`).

#### Servicio (`src/Servicio`)
- CRUD de `servicios`.
- Listados ordenados por nombre (incluye ordenamiento especial para diagnóstico).

#### Producto / Inventario (`src/Producto`)
- CRUD de `productos`.
- Filtros frecuentes: `tipo_producto='repuesto'`, `activo`, `categoria`, `stock<=stock_minimo`.
- Búsqueda por `ilike` en `codigo`, `nombre`, `proveedor`.
- Validación de uso en `orden_repuesto` antes de eliminar.

#### Orden de Trabajo (`src/OrdenTrabajo`)
- CRUD de `ordenes_trabajo`.
- Carga de múltiples relaciones (`cliente`, `vehiculo`, `mecanico`, `pago`, `factura`, etc.).
- Sincronización de servicios/repuestos (INSERT/DELETE sobre tablas pivote).
- Generación de número correlativo diario (`numero LIKE OT-YYYYMMDD-%` + orden descendente).
- Transacciones para proteger integridad de stock/estado.

#### Citas (`src/Cita`)
- CRUD lógico de `citas_taller` (agendar, reagendar, cancelar, completar).
- Consultas por rango de fecha/hora para calendario.
- Filtros por rol/propietario (mecánico/cliente).
- Integración con presupuesto/orden (creación y actualización transaccional).

#### Presupuesto (`src/Presupuesto`)
- CRUD de `presupuestos` y líneas (`presupuesto_servicios`, `presupuesto_repuestos`).
- Recalculo de totales con `sum`.
- Generación de número correlativo diario (`PRE-YYYYMMDD-%`).
- Filtros por estado/cliente/vehículo y búsqueda.

#### Factura (`src/Factura`)
- CRUD de `facturas` y `detalle_facturas`.
- Reglas de facturación por orden (`whereDoesntHave`, estados, validaciones).
- Generación de número correlativo diario (`F-YYYYMMDD-%`).
- Cálculo de totales desde líneas de orden.

#### Pago (`src/Pago`)
- CRUD de `pagos`.
- Listado de pagos con relaciones.
- Sincronización de estado de factura según pago.

#### Reporte (`src/Reporte`)
- **Consultas analíticas pesadas** con `JOIN`, `GROUP BY`, `SUM`, `COUNT`:
  - Órdenes por estado.
  - Ingresos por fecha.
  - Top servicios y repuestos.
  - Vehículos/órdenes por cliente.
  - Diagnósticos IA por estado.
  - Métricas de inventario (stock bajo/sin stock).
- Historial de vehículo con búsqueda textual y `withCount`.
- Portal cliente con filtros por `cliente_id` y estados.

---

## 3) Necesidad de índices (recomendación)

### 3.1 Índices de prioridad alta

1. `ordenes_trabajo (mecanico_id, created_at DESC)`  
2. `ordenes_trabajo (cliente_id, created_at DESC)`  
3. `ordenes_trabajo (vehiculo_id, created_at DESC)`  
4. `ordenes_trabajo (estado, created_at DESC)`  
5. `pagos (estado, created_at)`  
6. `productos (tipo_producto, activo, nombre)`  
7. `productos (tipo_producto, activo, stock)`  
8. `orden_repuesto (producto_id)`  
9. `orden_repuesto (orden_trabajo_id, producto_id)`  
10. `orden_servicio (servicio_id)`  
11. `clientes (user_id)` (idealmente único parcial cuando no es null)

### 3.2 Índices de prioridad media

1. `facturas (estado, created_at DESC)`  
2. `facturas (cliente_id, estado)`  
3. `vehiculos (cliente_id, activo, placa)`  
4. `users (role, activo)`  
5. `mecanicos (activo, nombres)`  
6. `diagnosticos_ia (estado)`

### 3.3 Índices situacionales (según crecimiento de datos)

- `pg_trgm` para búsquedas `ILIKE '%texto%'`:
  - `productos.nombre`, `productos.codigo`, `users.name`, `users.email`, `mecanicos.especialidad`.
- Útil cuando el volumen crece y las búsquedas libres degradan.

---

## 4) ¿Se necesitan triggers?

### 4.1 No necesarios (recomendado mantener en aplicación)

- **Stock de repuestos:** ya controlado con transacciones y `lockForUpdate`.
- **Sincronización pago-factura:** depende de reglas de negocio y estados, mejor en servicios.
- **Totales de presupuesto/factura:** lógica de dominio más mantenible en aplicación.
- **Notificaciones:** ya resuelto por eventos/notificadores de Laravel.

### 4.2 Sí podría evaluarse trigger solo en casos específicos

- **Auditoría legal/inmutable** (facturas/pagos): si hay exigencia regulatoria estricta, un trigger para bitácora append-only puede ser válido.
- Si no existe esa exigencia, es preferible auditoría vía eventos de aplicación + tabla de auditoría explícita.

### 4.3 Alternativa recomendada a trigger

- Constraint de integridad:
  - `CHECK (stock >= 0)` en `productos`.
- Evita inconsistencias sin duplicar reglas complejas en trigger.

---

## 5) Riesgos y oportunidades de optimización

1. En algunos flujos IA se cargan catálogos completos (`get()`) y luego se filtra en PHP.  
   **Mejorar** con búsqueda SQL directa y/o caché.

2. `ReporteDatosService` ejecuta varias agregaciones en una sola solicitud.  
   **Mejorar** con cache de corto TTL o materialización de métricas.

3. Uso de `whereDate()` en agendas puede perder eficiencia de índice en tablas grandes.  
   **Mejorar** usando rango datetime (`>= inicio_dia` y `< fin_dia`).

---

## 6) Conclusión

- El proyecto tiene una cobertura de consultas amplia y consistente con Eloquent.
- **La necesidad principal es de índices**, especialmente en `ordenes_trabajo`, `pagos`, `productos`, `orden_repuesto` y `orden_servicio`.
- **No hay necesidad fuerte de triggers** para operación normal; la lógica transaccional en aplicación está bien planteada.
- Si se implementan primero los índices de prioridad alta, se reduce de forma importante el costo de listados, filtros y reportes.

