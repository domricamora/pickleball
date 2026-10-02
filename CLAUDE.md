# CLAUDE.md — AI coding instructions

**Read `plan.md` first.** It is the master specification. This file is the
operating contract for working in this repository.

## Status

Phases 0, 1 and 2 are complete. See `PHASE_STATUS.md`.
Next phase: **Phase 3 — Facility & Court Management**.

## Stack

- Laravel 13 (PHP 8.3+)
- Inertia 3 + React 19 + TypeScript (strict)
- Tailwind CSS 4 (via `@tailwindcss/vite`)
- Vite 8
- MySQL 9
- PHPUnit 12 + Laravel HTTP tests
- Larastan (PHPStan level 5), Pint, ESLint, Prettier

## Commands

```bash
# App
php artisan serve                 # http://localhost:8000
npm run dev                       # Vite dev server (run alongside)
npm run build                     # production assets

# Create the first administrator (credentials come from .env or --options)
php artisan db:seed --class=RolePermissionSeeder
php artisan app:install-admin

# Quality gates — all must pass before a phase is called complete
php artisan test
vendor/bin/phpstan analyse --memory-limit=1G
vendor/bin/pint --test
npm run lint
npm run types:check
npm run format:check

# Database
php artisan migrate
```

## Local environment

| Item | Value |
| --- | --- |
| Databases | `pickleplay` (local), `pickleplay_testing` (tests) |
| DB user | `root`, no password (WAMP) |
| Timezone | `Asia/Manila` via `APP_TIMEZONE` |
| Currency | Philippine peso (`₱`) |
| Local URL | http://localhost:8000 |

The workspace folder is `C:\wamp64\www\p`, served as `/p` by WAMP.

> **Do not reuse the `pickleball` database or the sibling
> `C:\wamp64\www\pickleball` folder.** That is a separate, abandoned
> aesthetics-clinic project ("Veloura") with its own schema and deploy script.
> This project uses the `pickleplay` database to stay isolated from it.

## Rules

- **Never commit `.env`**, credentials, API keys or certificates. `.gitignore`
  already blocks them; keep it that way. Real values live only in local `.env`,
  server `.env` and deployment secrets.
- **Never hard-code credentials** — not in seeders, config, JS, logs or
  comments. Admin accounts are created from environment variables.
- **Implement one phase at a time.** Do not start the next phase until the
  current one passes every gate in `PHASE_STATUS.md`.
- **Do not overwrite working functionality blindly.** Inspect first.
- Prefer Laravel conventions: thin controllers, Form Requests for validation,
  Policies for authorization, Actions/Services for business logic, Jobs for
  async work, Events/Listeners for decoupling.
- **Use database transactions and row locking for bookings, payments and
  stock.** Booking integrity outranks convenience (plan.md §31).
- Validate all input. Keep tenant isolation explicit — every tenant-owned model
  carries `organization_id` **and** `use BelongsToOrganization`.
- **The tenant global scope is the security boundary.** Never bypass it with a
  hand-written `where`. If a query genuinely must cross tenants, say so out
  loud with `withoutGlobalScope('organization')` and guard it with
  `bypassesTenantScope()`.
- **A signed-in user with no `organization_id` (a player) must see zero tenant
  rows.** The scope forces an unsatisfiable condition rather than skipping, so
  "no tenant" can never mean "all tenants".
- Use migrations for schema changes; never hand-edit the database.
- Write tests with new functionality. Unit tests for business logic, feature
  tests for endpoints.
- Use Tailwind design tokens (`pickle-*`, `energetic-*`, `lime-accent`, `ink`,
  `slate`, `soft-bg`, `hairline`, `rounded-card`, `rounded-panel`). Do not
  hard-code hex values in components and do not introduce new accent colours.
- Format money in Philippine peso and format dates in `Asia/Manila`.
- Keep tax configuration administrative — never hard-code tax assumptions.

## Brand voice

Playful, energetic, welcoming to beginners, credible for competitive players.
Modern and sporty rather than corporate. Avoid dark, glassy SaaS styling.

## Media

Use only royalty-free media (Unsplash, Pexels, Pixabay, Wikimedia where the
licence is confirmed). Record source URL, author, licence and download date in
`resources/media/media-credits.md`. Never use copyrighted commercial footage and
never imply a stock person is a real customer or employee. Do not fabricate
testimonials.