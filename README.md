# AUTOFIX IA

Sistema de taller automotriz con órdenes de trabajo, diagnóstico asistido por IA (Groq), facturación y pagos.

Stack: **Laravel 12**, **Sanctum**, **Inertia + Vue 3**, **PostgreSQL**, Bounded Contexts en `src/`.

## Requisitos

- PHP 8.2+
- Composer
- Node.js 20+
- PostgreSQL (o SQLite para pruebas rápidas)

## Instalación (equipo)

```bash
git clone https://github.com/Lthony7/AutoFix-IA.git
cd AutoFix-IA
copy .env.example .env   # Windows
# cp .env.example .env   # Linux/macOS

composer install
php artisan key:generate
```

Edita `.env`:

| Variable | Notas |
|----------|--------|
| `DB_*` | Tu PostgreSQL local |
| `GROQ_API_KEY` | Key propia en https://console.groq.com/keys |
| `GROQ_MOCK` | `true` sin key; `false` para IA real |
| `GROQ_SSL_VERIFY` | En Windows, si falla cURL 60 → `false` (solo local) |
| `FACTURA_IVA_RATE` | Default `0.15` (Ecuador) |
| `FACTURA_SERIE` | Default `F001` |

```bash
php artisan migrate --seed
npm install
composer dev
```

App: http://localhost:8000

## Usuarios demo

Contraseña de todos: `password`

| Email | Rol |
|-------|-----|
| `admin@autofix.test` | Administrador |
| `recepcion@autofix.test` | Recepcionista |
| `mecanico@autofix.test` | Mecánico |
| `cliente@autofix.test` | Cliente (portal) |

El seeder también crea: vehículo `ABC-1234`, servicios, repuestos, una OT con factura/pago demo y otra OT pendiente para IA.

## Checklist demo por rol

1. **Admin** — ver menú completo, usuarios, reportes, dashboard con métricas.
2. **Recepción** — crear OT con servicios/repuestos → generar factura → registrar pago.
3. **Mecánico** — ver sus órdenes, crear/revisar diagnóstico IA.
4. **Cliente** — portal: mis vehículos / mis órdenes (y correo al cambiar estado OT).

## Flujo principal

`OT (ítems) → Factura (1:1) → Pago → Factura pagada`

También: `OT → Diagnóstico IA (Groq) → revisión mecánica`.

## Tests

```bash
# Crear BD de tests (una vez) — este entorno usa PostgreSQL (no pdo_sqlite)
# CREATE DATABASE taller_automotriz_test;

php artisan test
```

Usa la base `taller_automotriz_test` (`phpunit.xml`). `GROQ_MOCK=true` durante tests.

## Módulos (`src/`)

Auth, Cliente, Vehiculo, Mecanico, Servicio, Producto (repuestos), OrdenTrabajo, DiagnosticoIA, Factura, Pago, Reporte (reportes, historial, portal).

## Notas

- **No** subir `.env` ni keys de Groq.
- Facturación electrónica SRI / PDF fiscal: fuera de alcance actual.
- Documentación API legacy: ver `DOCUMENTATION.md` (boilerplate; el dominio Autofix está en este README).
