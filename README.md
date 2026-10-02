# PicklePlay — Philippine Pickleball Courts SaaS Platform

A multi-tenant SaaS platform for Philippine pickleball court operators and
facilities: public marketing site, court rental and booking, membership, POS,
inventory, staff, operations, finance and reporting.

**Play. Book. Compete. Connect.**

Built incrementally against [`plan.md`](plan.md). One phase at a time, each one
verified on localhost before the next begins. Current progress lives in
[`PHASE_STATUS.md`](PHASE_STATUS.md).

---

## Stack

| Layer | Technology |
| --- | --- |
| Backend | Laravel 13, PHP 8.3+ |
| Frontend | Inertia 3, React 19, TypeScript (strict) |
| Styling | Tailwind CSS 4 |
| Build | Vite 8 |
| Database | MySQL 9 |
| Tests | PHPUnit 12 (unit + feature) |
| Quality | Larastan (PHPStan L5), Pint, ESLint, Prettier |

---

## Requirements

- PHP 8.3+ with `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `fileinfo`
- Composer 2
- Node.js 20+ (tested on 22) and npm
- MySQL 8+

---

## Local setup

```bash
# 1. Install dependencies
composer install
npm install

# 2. Environment
cp .env.example .env        # Windows: copy .env.example .env
php artisan key:generate

# 3. Create the databases
mysql -u root -e "CREATE DATABASE pickleplay CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -e "CREATE DATABASE pickleplay_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 4. Point .env at your credentials, then migrate
php artisan migrate

# 5. Run it
php artisan serve            # http://localhost:8000
npm run dev                  # in a second terminal
```

For a production-style local run without the Vite dev server:

```bash
npm run build
php artisan serve
```

---

## Quality gates

Every phase must leave all of these green before it is called complete.

```bash
php artisan test             # unit + feature tests (MySQL-backed)
vendor/bin/phpstan analyse   # static analysis, level 5
vendor/bin/pint --test       # PHP formatting
npm run lint                 # ESLint
npm run types:check          # TypeScript strict
npm run format:check         # Prettier
npm run build                # production bundle
```

Fix formatting automatically with `vendor/bin/pint`, `npm run lint:fix` and
`npm run format`.
---

## Project layout

```
app/
  Actions/          Business logic (bookings, payments, inventory, ...)
  Http/Controllers/ Thin controllers; Site/ for public, Admin/ for back office
resources/
  css/app.css       Tailwind entry + brand design tokens
  js/
    app.tsx         Inertia/React entry point
    layouts/        PageLayout — public shell with header and footer
    pages/          One file per route (Home.tsx, Pricing.tsx, ...)
    components/     Reusable React components (layout, marketing, seo, ui)
    lib/            Formatters and helpers (peso, dates, class names)
    types/          Shared Inertia prop types
  views/            Blade root view and SSR head tags
    lib/            Helpers and shared utilities
routes/
  web.php           Public and app routes
  console.php       Scheduled tasks
tests/
  Unit/             Pure business logic
  Feature/          HTTP endpoints and workflows
docs/               Architecture and phase documentation
plan.md             Master specification (source of truth)
PHASE_STATUS.md     Phase-by-phase completion tracker
CLAUDE.md           AI coding instructions and project rules
```

### Architecture conventions

- **Thin controllers.** Business logic lives in `app/Actions`.
- **Form Requests** for validation, **Policies** for authorization,
  **Jobs** for async work, **Events/Listeners** for decoupling.
- **Transactions and row locking** for bookings, payments and stock movement.
- **Tenant isolation is explicit** — tenant-owned models carry `organization_id`.
- **Migrations only** for schema changes.
- **Inertia pages** receive content as props; never hard-code copy in the bundle.

---

## Brand

Defined once in `resources/css/app.css` as Tailwind tokens and reused everywhere.

| Role | Token | Value |
| --- | --- | --- |
| Primary | `pickle-500` | `#3A9D5D` Pickle Green |
| Primary dark | `pickle-900` | `#14532D` Deep Court Green |
| Highlight | `lime-accent` | `#B7E34A` Court Lime |
| CTA accent | `energetic-500` | `#FF6B35` Coral/Orange |
| Text | `ink` | `#10231A` |
| Body text | `slate` | `#475569` |
| Background | `soft-bg` | `#F7FAF5` |
| Border | `hairline` | `#DDE7DF` |

Typography is **Plus Jakarta Sans**, self-hosted through the Vite build (no
third-party font CDN at runtime).

Guidelines: green is the core colour, lime marks energy, and orange is reserved
for calls to action. Do not introduce additional accent colours or hard-code hex
values in components.

---

## Philippines specifics

- Currency formatted as `₱1,250.00`
- Timezone `Asia/Manila` (`APP_TIMEZONE`)
- Address model includes Barangay, City/Municipality, Province, Region
- Philippine mobile number formats
- Tax configuration is administrative, never hard-coded

---

## Security

- `.env` and all credential files are git-ignored and must never be committed.
- No credentials in source, seeders, JavaScript, logs or responses.
- Real secrets live only in local `.env`, server `.env` and deployment secrets.
- Rotate the initial administrator password after first login.

See plan.md §2 for the full security rule and §16 (Phase 16) for the hardening
roadmap.

---

## Roadmap

| Phase | Scope | Status |
| --- | --- | --- |
| 0 | Project Foundation | **Complete** |
| 1 | Marketing Site | **Complete** |
| 2 | Authentication & SaaS Foundation | **Complete** |
| 3 | Facility & Court Management | Next |
| 4 | Booking Engine | Pending |
| 5 | Philippine Payments & Receipts | Pending |
| 6 | Customer / Player CRM | Pending |
| 7 | Memberships & Packages | Pending |
| 8 | Events, Open Play & Tournaments | Pending |
| 9 | POS & Product Sales | Pending |
| 10 | Inventory | Pending |
| 11 | Staff & Operations | Pending |
| 12 | Finance & Reporting | Pending |
| 13 | Notifications & Marketing Automation | Pending |
| 14 | AI Features | Pending |
| 15 | Analytics | Pending |
| 16 | Security Hardening | Pending |
| 17 | Testing & QA | Pending |
| 18 | Deployment | Pending |

---

## License

Proprietary. All rights reserved.