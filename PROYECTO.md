# RDI — Reglas y tecnologías

Documento vivo del proyecto. Se actualiza a medida que definimos convenciones.

---

## Stack

| Capa | Tecnología | Versión / notas |
|------|------------|-----------------|
| Backend | PHP | ^8.3 (usar 8.5 localmente) |
| Framework | Laravel | 13.x |
| Auth | Laravel Fortify | + Passkeys |
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

---

## Estructura relevante

```
app/                  # Backend Laravel
routes/web.php        # Rutas Inertia (Route::inertia)
resources/js/
  pages/              # Páginas Inertia (ej. dashboard.tsx)
  components/         # UI y layout
  layouts/
  hooks/
  types/
tests/                # Feature / Unit
```

---

## Reglas del proyecto

> Ir añadiendo aquí las convenciones acordadas.

### Generales

- [ ] (pendiente)

### Backend (Laravel)

- [ ] (pendiente)

### Frontend (Inertia + React)

- [ ] (pendiente)

### Base de datos

- [ ] (pendiente)

### Git / PRs

- [ ] (pendiente)

---

## Decisiones

| Fecha | Decisión | Motivo |
|-------|----------|--------|
| 2026-07-31 | Starter kit React + Inertia | Default Laravel 13 / agentes |
| 2026-07-31 | SQLite en local | Setup rápido |
