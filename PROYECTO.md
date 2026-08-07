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
  Models/Area.php, Round.php, Checkpoint.php, CheckpointQuestion.php, …
  Policies/
  Http/Controllers/RoundController.php, Checkpoint*Controller.php, …
routes/web.php
resources/js/
  pages/areas/, pages/users/, pages/rounds/, pages/checkpoints/, pages/guard/

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

## Dominio actual (configuración)

Siempre scoped por `area_id`:

- **`rounds`**: recorridos de una planta (`title`, `instructions`, `is_active`)
- **`checkpoints`**: puntos de revisión ordenados (`name`, `instructions`, `position`, `token` UUID para QR)
- **`checkpoint_questions` / `checkpoint_question_options`**: cuestionario de opción múltiple por punto (configuración admin)
- **`checkpoint_submissions` / `checkpoint_submission_answers`**: respuestas del guardia al escanear el QR

Gestión admin: super-admin y admin de área, filtrada por el área activa del sidebar.

Desde editar recorrido → **Configurar cuestionario** / **Descargar QR** en cada punto.

Escaneo: `GET/POST /scan/{token}` (usuario autenticado con rol `guard` o `admin` del área).

Panel guardia: `/guardia` — acciones (iniciar recorrido); listado solo de recorridos activos en áreas con rol **`guard`**.

**`patrol_runs` / `patrol_checkpoint_visits`**: recorrido en curso o finalizado del guardia (`started_at`, `finished_at`, visitas por punto con cuestionario o “área sin novedad”). Escáner QR in-app valida presencia antes de abrir el punto.

**`patrol_checkpoint_visit_photos`**: hasta 3 fotos opcionales por visita; se optimizan en el servidor (JPEG) y se guardan en el disco `public`.

Consulta admin: pestaña **Rondines** — listado de recorridos del área → rondines realizados (estado, guardia, inicio/fin, duración) → detalle por punto (respuestas, fotos, urgente) y descarga PDF.

**Panel admin:** KPIs del área activa (urgentes hoy, en curso, finalizados hoy, tiempo promedio 7 días), lista de urgentes recientes, rondines activos y volumen de finalizados últimos 7 días.

**Urgente de revisión:** el guardia puede marcar un punto como urgente al enviar cuestionario o «sin novedad»; se guarda en la visita y se notifica a los contactos asignados al recorrido (`round_contact`) vía WhatsApp y/o SMS (Twilio).

**Contactos:** teléfono y preferencias de notificación en usuarios; asignación por recorrido en editar recorrido.

**Incidencias:** desde `/guardia` o desde un punto (`/scan/{token}/incidencia`) el guardia reporta con mensaje, hasta 3 fotos y flag urgente. OpenAI limpia el texto y asigna una **categoría** del área; se notifica a los contactos de esa categoría (`incident_category_contact`). Si viene del scan, la incidencia queda ligada a patrulla/punto y la visita queda con outcome `incident`.

**Categorías:** CRUD admin por área (`/incident-categories`) con código, descripción y contactos asignados.

## Dominio futuro

- Panel de contactos (dashboard de urgentes y recorridos asignados)
- `round_assignments` / reportes agregados
- Abandonar patrulla a mitad

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
- `checkpoints.token` único (URL de escaneo `/scan/{token}`).
- Respuestas de cuestionario en `checkpoint_submissions` ligadas a `user_id`.

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
| 2026-07-31 | Recorridos + puntos por área | Configuración antes de ejecución/cuestionarios |
| 2026-08-02 | Cuestionario de opción múltiple por checkpoint | Prep. para reporte estructurado al escanear QR |
| 2026-08-02 | QR (`qrcode` npm) + scan autenticado por token | Guardia responde; base para reportes por usuario |
| 2026-08-02 | Panel de acciones del guardia post-login | Separar UX operativa del dashboard de admin |
| 2026-08-04 | Patrol runs + visitas + escáner QR | Tiempos de recorrido y prueba de presencia |
