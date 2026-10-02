# Architecture

Describes the conventions this codebase follows. The master specification is
[`plan.md`](../plan.md); the phase tracker is [`../PHASE_STATUS.md`](../PHASE_STATUS.md).

---

## Stack

| Layer | Technology |
| --- | --- |
| Backend | Laravel 13 on PHP 8.3+ |
| Rendering | Inertia 3 (server-driven routing, React 19 SPA) |
| Language | TypeScript (strict) |
| Styling | Tailwind CSS 4 via `@tailwindcss/vite` |
| Build | Vite 8 |
| Database | MySQL 9 |
| Tests | PHPUnit 12 |
| Static analysis | Larastan (PHPStan level 5) |
| Formatting | Pint (PHP), Prettier (TS/CSS) |
| Linting | ESLint 9 (flat config) |

---

## Request lifecycle

A page request follows one path:

```
Browser
  → routes/web.php
    → Controller (thin: validate, authorise, delegate, respond)
      → Action / Service (business logic, transactions)
        → Eloquent Model
          → MySQL
    → Inertia\Response::render('PageName', $props)
      → resources/views/app.blade.php  (root view)
        → Vite bundle → resources/js/pages/PageName.tsx
```

React components never fetch data directly in the first pass: the controller
supplies props through Inertia. Add client-side requests only when a feature
genuinely needs them (availability polling, POS search), and keep the initial
render server-driven for performance and SEO.

---

## Directory layout

```
app/
  Actions/            Business logic, one class per operation
  Casts/              Attribute casts
  Concerns/           Reusable model behaviour
  Enums/              Backed enums for statuses and types
  Exceptions/         Domain exceptions
  Http/
    Controllers/
      Site/           Public marketing + customer-facing
      Admin/          Back-office
      Auth/           Authentication
    Middleware/
    Requests/         Form Requests (validation)
    Resources/        API/JSON resources
  Models/             Eloquent models
  Policies/           Authorization policies
  Providers/          Service providers
  Jobs/               Queued work
  Events/Listeners/   Domain events
resources/
  css/app.css         Tailwind entry + brand tokens
  js/
    app.tsx           Inertia/React entry
    components/       Reusable components
    layouts/          Page shells
    lib/              Helpers, formatters, utilities
    pages/            One file per route
routes/
  web.php
  console.php
tests/
  Unit/               Pure business logic, no HTTP
  Feature/            HTTP endpoints and workflows
```

---

## Conventions

### Controllers

Controllers stay thin. They validate (via Form Request), authorise (via
Policy), delegate to an Action, and return a response.

```php
public function store(StoreBookingRequest $request): RedirectResponse
{
    $this->authorize('create', Booking::class);

    $booking = CreateBooking::handle($request->validated());

    return to_route('bookings.show', $booking);
}
```

### Actions

Business logic belongs in `app/Actions`, grouped by domain. Actions that touch
bookings, payments or stock **must** run inside a transaction and use locking
where a concurrent write could corrupt state.

```php
DB::transaction(function () use ($data) {
    // lock the parent row to serialise competing bookings
});
```

### Authorization

Every tenant-owned model carries `organization_id`, and authorization is
enforced server-side with Policies. Never rely on hiding UI elements alone.

### Validation

Form Requests, always. Validate at the edge and assume nothing downstream.

### Database

- Migrations only — never hand-edit a schema.
- Index deliberately: `organization_id`, `facility_id`, `court_id`, booking
  date, booking status, `customer_id`, payment status.
- Use the smallest correct column type; money is `decimal`, not `float`.
- Bookings store UTC and render in `Asia/Manila`.

### Frontend

- TypeScript strict; `npm run types:check` must pass.
- ESLint must pass; no `any` without a written justification.
- Use design tokens rather than raw hex values.
- Keep pages accessible: semantic elements, labels, `alt` text, visible focus.

---

## Multi-tenancy

The platform is tenant-ready from the start (plan §6). Every tenant-owned table
has an `organization_id` with a global scope so cross-tenant data cannot leak by
default. Creating explicit scopes from day one is far cheaper than retrofitting
isolation after launch.

Branches (facilities) hang off an organization, so a tenant can operate several
locations from one account.

---

## Formatting

| Concern | Rule |
| --- | --- |
| Currency | Philippine peso — `₱1,250.00` |
| Timezone | `Asia/Manila` via `APP_TIMEZONE` |
| Dates in UI | `M j, Y` (e.g. `Feb 3, 2026`) |
| Times in UI | `g:i A` (e.g. `6:30 PM`) |
| Tax | Administrative configuration — never hard-coded |

---

## Testing strategy

| Level | Scope |
| --- | --- |
| Unit | Pure business logic, no database or HTTP |
| Feature | HTTP endpoints, Inertia responses, authorisation, transactions |
| Browser (E2E) | Critical flows, added in Phase 17 |

Tests run against MySQL (`pickleplay_testing`) using `RefreshDatabase`, not
SQLite, so schema and query behaviour match production.

Every module ships with tests alongside its implementation (plan §17).