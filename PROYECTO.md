# RDI — Reglas y tecnologías

Documento vivo del proyecto. Se actualiza a medida que definimos convenciones.

---

## Producto

RDI es una aplicación para **reportar incidencias** en recorridos de seguridad.

- Los **guardias** realizan recorridos con puntos de revisión (QR).
- En cada punto registran una incidencia o marcan el punto como seguro.
- Los **contactos** atienden las incidencias.
- El sistema es **multi-área** (plantas/ubicaciones): los datos no se mezclan entre plantas.

---

## Stack

| Capa | Tecnología | Versión / notas |
|------|------------|-----------------|
| Backend | PHP | ^8.3 (usar 8.5 localmente) |
| Framework | Laravel | 13.x |
| Auth | Laravel Fortify | Passkeys; **sin registro público** |
| Frontend bridge | Inertia.js | v3 (`inertiajs/inertia-laravel` + `@inertiajs/react`) |
| UI | React | 19.x |
| Estilos | Tailwind CSS | v4 |
| Componentes | Radix UI + shadcn-style | `resources/js/components/ui` |
| Iconos | Lucide React | — |
| Build | Vite | + `@vitejs/plugin-react` |
| Rutas tipadas | Laravel Wayfinder | PHP ↔ TypeScript |
| DB (local) | SQLite | por defecto |
| Tests | PHPUnit | 12.x |
| Calidad PHP | Pint + Larastan/PHPStan | — |
| Calidad JS | ESLint + Prettier + `tsc` | — |
| AI / agentes | Laravel Boost | `AGENTS.md` + skills en `.cursor/skills` |

---

## Arranque local

```bash
composer run dev
```

App: http://localhost:8000

### Usuarios demo (seeder)

| Email | Password | Rol |
|-------|----------|-----|
| `admin@example.com` | `password` | Super admin |
| `guard@example.com` | `password` | Guard en Planta Norte |
| `contact@example.com` | `password` | Contact en Planta Norte |

```bash
php artisan migrate:fresh --seed
```

---

## Estructura relevante

```
app/
  Enums/AreaRole.php
  Models/Area.php, AreaUser.php, User.php
  Policies/
  Http/Controllers/AreaController.php, UserController.php
routes/web.php
resources/js/
  pages/areas/, pages/users/
  components/app-sidebar.tsx
tests/Feature/
```

---

## Modelo de acceso (multi-área)

- **`users.is_super_admin`**: gestiona todas las áreas y usuarios.
- **`area_user`**: membresía `user` ↔ `area` con un rol por área:
  - `admin` — gestiona usuarios de esa área
  - `guard` — recorridos (fase 2+)
  - `contact` — incidencias (fase 2+)
- Un usuario puede pertenecer a **varias áreas** con **roles distintos**.
- Contexto de sesión: `current_area_id` (selector en sidebar).

---

## Dominio futuro (fase 2+)

Siempre con `area_id` para no mezclar plantas:

- `rounds`, `checkpoints` (QR), `round_assignments`
- `patrol_runs` / `checkpoint_logs`
- `incidents`

---

## Reglas del proyecto

### Generales

- **Idioma de la aplicación: español.** Toda la UI visible al usuario debe estar en español: menús, botones, leyendas, placeholders, breadcrumbs, toasts, mensajes de validación, instrucciones y textos de ayuda. No dejar cadenas en inglés en pantallas o componentes de producto.
- Locale Laravel: `APP_LOCALE=es` (traducciones en `lang/es.json` y archivos de idioma).
- No mezclar datos entre áreas; todo lo operativo debe estar scoped por `area_id`.

### Backend (Laravel)

- Roles de área vía enum `App\Enums\AreaRole` (no Spatie por ahora).
- Autorización con Policies + middleware `manage.areas`.
- Usuarios solo se crean desde el panel (registro Fortify desactivado).

### Frontend (Inertia + React)

- Páginas en `resources/js/pages`.
- Navegación de administración solo visible según permisos compartidos por Inertia.

### Base de datos

- Unique `(user_id, area_id)` en `area_user`.
- `areas.code` único.

### Git / PRs

- **Mensajes de commit en español.** Describir el *porqué* del cambio en 1–2 oraciones claras (ej. qué problema resuelve o qué capacidad agrega), no solo listar archivos tocados.

---

## Decisiones

| Fecha | Decisión | Motivo |
|-------|----------|--------|
| 2026-07-31 | Starter kit React + Inertia | Default Laravel 13 / agentes |
| 2026-07-31 | SQLite en local | Setup rápido |
| 2026-07-31 | Rol por área + `is_super_admin` | Flexibilidad multi-planta |
| 2026-07-31 | Sin registro público | Solo admins crean usuarios |
| 2026-07-31 | UI 100% en español | Producto orientado a operación en campo |
| 2026-07-31 | Commits en español | Consistencia con idioma del proyecto |
