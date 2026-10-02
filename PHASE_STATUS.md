# PHASE_STATUS

Phase tracker for the Philippine Pickleball Courts SaaS Platform.
Source of truth: [`plan.md`](plan.md).

---

## Phase 0 — Project Foundation

**Status: COMPLETE**

**Local URL:** http://localhost:8000

### Completed

- [x] Laravel 13 initialised on PHP 8.3
- [x] Inertia 3 + React 19 + TypeScript (strict) wired end to end
- [x] Tailwind CSS 4 via `@tailwindcss/vite`
- [x] Vite 8 configured (React plugin, Inertia plugin, self-hosted fonts)
- [x] Path alias `@/` configured in both Vite and tsconfig
- [x] ESLint 9 (flat config) + Prettier configured
- [x] Larastan / PHPStan level 5 + Pint configured
- [x] Git initialised on `main`, `.gitignore` hardened for secrets
- [x] `.env.example` committed; `.env` and credential files git-ignored
- [x] Credentials redacted from `plan.md` (plan §2 rule)
- [x] MySQL 9 configured; `pickleplay` and `pickleplay_testing` created
- [x] Migrations run against MySQL
- [x] Tests run against MySQL (`pickleplay_testing`), not SQLite
- [x] Asia/Manila timezone wired through `APP_TIMEZONE`
- [x] Brand design tokens defined in `resources/css/app.css`
- [x] Application shell: `AppLayout`, `Button`, `Welcome` page
- [x] README.md, CLAUDE.md, AGENTS.md, docs/architecture.md

### Verification

| Gate | Result |
| --- | --- |
| Laravel homepage renders | Pass — HTTP 200 |
| React page renders | Pass — Inertia `Welcome` component mounts |
| Tailwind styling | Pass — brand tokens present in built CSS |
| MySQL connection | Pass |
| Migrations | Pass — 3 base migrations |
| Tests | Pass — 5 tests, 17 assertions |
| Production build | Pass — 569 modules, 2.04s |
| ESLint | Pass |
| TypeScript | Pass |
| PHPStan (L5) | Pass |
| Pint | Pass |

### Notes

- Database is named `pickleplay`, **not** `pickleball`. The `pickleball`
  database on this machine belongs to a separate abandoned clinic project in
  `C:\wamp64\www\pickleball` and must not be reused.
- `npm run build` must be run at least once before the test suite passes,
  because the root view references the Vite manifest.
- Pest is **not** installed: `pestphp/pest-plugin-laravel` v5 requires PHP 8.4
  and v4 does not support Laravel 13. PHPUnit 12 ships with Laravel and is used
  instead. Revisit if PHP is upgraded to 8.4+.

---

## Phase 1 — Marketing Site

**Status: COMPLETE**

**Local URL:** http://localhost:8000

### Pages

| Route | Page | Notes |
| --- | --- | --- |
| `/` | Home | Hero, Find Your Game, Book in Seconds, features, operator section, community, CTA |
| `/facilities` | Facilities | Filters + honest empty state until Phase 3 |
| `/courts` | Courts | Court-type overview + empty state |
| `/pricing` | Pricing | Three peso-priced tiers |
| `/memberships` | Memberships | Tiers, benefits, session packages |
| `/events` | Events | Event categories + empty state |
| `/tournaments` | Tournaments | Singles/Doubles/Mixed doubles + empty state |
| `/about` | About | Mission and brand values |
| `/contact` | Contact | Contact cards + LocalBusiness schema |
| `/faq` | FAQ | Accessible accordion + FAQPage schema |
| `/blog` | Blog | Empty state |
| `/book` | Book a Court | Landing page; the engine itself is Phase 4 |
| `/sitemap.xml` | Sitemap | Generated from a static route list |
| `/robots.txt` | Robots | Disallows `/admin`, `/dashboard`, `/profile` |

### Components

`Header` (sticky + mobile drawer with Escape and scroll-lock),
`Footer`, `Hero`, `SectionHeading`, `BookingCta`, `PricingCard`, `Faq`,
`Button`/`ButtonLink`, `Seo`, `PageLayout`, `EmptyState`.

### SEO

- Title, description, canonical, Open Graph and Twitter cards are rendered
  **server-side** in `app.blade.php` from the controller's `seo` prop, so
  crawlers and social scrapers see them without running JavaScript.
- The React `<Seo />` component re-states the same values after hydration;
  explicit props override the shared payload.
- JSON-LD: `Organization` + `WebSite` (home), `LocalBusiness` (contact),
  `FAQPage` (FAQ).
- `sitemap.xml` and `robots.txt` served by `SeoController`.
- No location landing pages for invented facilities (plan.md §33).

### Verification

| Gate | Result |
| --- | --- |
| 15 routes return HTTP 200 | Pass |
| Tests | Pass — 34 tests, 231 assertions |
| ESLint / TypeScript / Prettier | Pass |
| PHPStan (L5) / Pint | Pass |
| Production build | Pass |

### Notes

- lucide-react 1.x removed brand icons, so Facebook/Instagram marks in the
  footer are small inline SVGs.
- PHPUnit 12 needs `#[DataProvider]` attributes, not `@dataProvider`.
- List pages intentionally render empty states — real facilities arrive in
  Phase 3 and must not be faked for SEO.
- Photography is still the CSS court-line treatment; the licensed media pass
  with `resources/media/media-credits.md` entries is still outstanding.

---

## Phase 2 — Authentication & SaaS Foundation

**Status: COMPLETE**

**Local URL:** http://localhost:8000/login

### Authentication

Fortify 1.40 backs every flow; its Blade views are pointed at Inertia pages in
`App\Providers\FortifyServiceProvider`.

| Route | Page |
| --- | --- |
| `/login` | `auth/Login` |
| `/register` | `auth/Register` |
| `/forgot-password` | `auth/ForgotPassword` |
| `/reset-password/{token}` | `auth/ResetPassword` |
| `/verify-email` | `auth/VerifyEmail` |
| `/user/confirm-password` | `auth/ConfirmPassword` |
| `/two-factor-challenge` | `auth/TwoFactorChallenge` |
| `/dashboard` | `Dashboard` |
| `/profile` | `Profile` |

Registration, login, logout, password reset, email verification, profile
update, 2FA and passkeys are all enabled.

### Tenancy

- `organizations` — tenants, with Philippine business details, timezone,
  currency and **administrative** tax configuration
- `branches` — physical locations, with PH address fields and GPS coordinates
- `users` gained `organization_id`, `branch_id`, `phone`, `skill_level`,
  `is_active`
- `organization_users` — multi-tenant membership with a role per tenant
- `BelongsToOrganization` trait applies an automatic global scope

### RBAC

`App\Enums\Role` is the single source of truth for the eight roles in plan.md §7
and the permissions each one holds. `RolePermissionSeeder` materialises them and
is idempotent. `spatie/laravel-permission` backs `hasRole` / `hasPermission`;
Super Admin holds every permission implicitly.

### Installer

```bash
php artisan app:install-admin --email=... --password=... --organization="..."
```

Reads `ADMIN_EMAIL` / `ADMIN_PASSWORD` / `ADMIN_NAME` / `ADMIN_ORGANIZATION`
from the environment, validates a strong password (12+ chars, mixed case,
digits), optionally creates a first tenant and branch, and **never** hard-codes
or echoes a credential.

### Verification

| Gate | Result |
| --- | --- |
| Tests | Pass — 59 tests, 354 assertions |
| ESLint / TypeScript / Prettier | Pass |
| PHPStan (L5) / Pint | Pass |
| Production build | Pass |
| Live login → dashboard | Pass — POST 302, `/dashboard` 200 |

### Issues found and fixed during this phase

1. **Tenant isolation was silently disabled.** `BelongsToOrganization` skipped
   its scope whenever `runningInConsole()` was true. That is also true inside
   **queue workers**, so a job for one tenant could read another's rows. The
   scope is now keyed on the authenticated user alone.
2. **A player account could read every tenant's data.** With
   `organization_id === null` the scope previously applied no condition at all,
   meaning "no tenant" meant "all tenants". The scope now forces an
   unsatisfiable condition.
3. **`$request->string('token')` returned empty** for the password reset page —
   `input()` reads query and body, but the token is a *route* parameter. Fixed
   with `$request->route('token')`.
4. **`skill_level` was not mass-assignable**, so profile updates silently
   dropped it. `organization_id` / `branch_id` are deliberately still excluded.
5. Fortify has no notion of a suspended account, so `is_active` was never
   enforced. Added `Fortify::authenticateUsing()` plus the `active` middleware
   that ends the session of a user suspended after signing in.
6. `auth:sanctum` was applied without Sanctum installed, 500-ing every
   protected route. Fortify's session guard is used instead.

### Notes

- `assertGuest()` takes a guard name, so `$this->assertGuest('message')` throws.
- Fortify's login limiter returns **429**, not a validation error, once locked
  out — asserted explicitly.
- PHPStan needs `--memory-limit=1G` now that tenancy scopes are analysed.

---

## Phase 3 — Facility & Court Management

**Status: COMPLETE**

**Local URL:** http://localhost:8000/admin/facilities

### Schema

- `courts` — name, number (unique per facility), surface, type,
  indoor/outdoor, status, capacity, amenities, notes
- `court_schedules` — recurring weekly hours, weekday 0 (Sun) to 6 (Sat)
- `court_blocks` — maintenance, holiday, event and private closures
- `court_prices` — weekday, weekend, peak, off-peak, holiday, member, guest
- `branch_photos` — ordered facility imagery
- `branches` gained `description` and `opening_hours`

### Domain

Enums (`CourtStatus`, `CourtSurface`, `BlockType`, `PriceType`) carry the
labels and rules, so the UI and the booking engine read from one place.
`PriceResolver` picks the winning rate: court-specific beats branch-wide, and
among equals the most specific type wins. It returns `null` when nothing
matches rather than guessing a price.

### Authorisation

`BranchPolicy` and `CourtPolicy` are registered in `AppServiceProvider`. Every
admin controller calls `$this->authorize(...)`, and `organization_id` is always
derived server-side — for a court it comes from its branch, so a court can never
belong to a different tenant than the branch it sits in.

### Verification

| Gate | Result |
| --- | --- |
| Tests | Pass — 87 tests, 484 assertions |
| ESLint / TypeScript / Prettier | Pass |
| PHPStan (L5) / Pint | Pass |
| Production build | Pass |
| Live admin pages (authenticated) | Pass — all 200 with correct components |

### Issues found and fixed during this phase

1. **`$this->user()` does not exist on a plain controller**, 500-ing facility
   creation. Replaced with `request()->user()`.
2. **Laravel 13's base `Controller` no longer includes `AuthorizesRequests`**,
   so every `$this->authorize()` call failed. Added the traits explicitly.
3. **Super Admin could not create a court.** The branch validation assumed the
   caller had an `organization_id`, but platform staff deliberately do not. The
   tenant is now derived from the branch instead, which is both more permissive
   for platform staff and stricter for everyone else.
4. **Holiday pricing was wrong.** `Carbon::isHoliday()` needs a package that is
   not installed, and a national calendar is the wrong source anyway —
   Philippine holidays vary per locality. Holidays are now resolved from the
   tenant's own holiday blocks.
5. `Rule::exists()->when(fn (Rule $rule) => ...)` resolves `Rule` to the
   validation *facade*, not the interface. Fully qualified.

### Notes

- This Inertia version's `useForm().post()` takes `(url, options)` and submits
  `form.data`, so coerced values must be written with `setData` first.
- Courts are soft-deleted so historical bookings keep their reference.

---

## Phase 4 — Booking Engine

**Status: COMPLETE**

**Local URL:** http://localhost:8000/book

### Schema

- `bookings` — reference, half-open `[starts_at, ends_at)` interval, status,
  peso amount, players, and lifecycle stamps (confirmed, checked in, completed,
  cancelled, no show, refunded)
- `booking_status_history` — an audit row for every transition

Indexes: `(court_id, starts_at, ends_at)` for the availability query,
`(organization_id, status, starts_at)`, `(branch_id, starts_at)`,
`(user_id, starts_at)`.

### Double-booking protection

A unique index cannot express "no two intervals may intersect", so
`App\Actions\Booking\BookCourt` does it in code:

1. open a transaction,
2. `SELECT ... FOR UPDATE` on the court row, serialising every attempt for
   that court,
3. re-check availability against schedules, blocks, court status and
   occupying bookings,
4. insert.

A second concurrent request blocks on the lock, then re-reads and sees the
first booking, so it is rejected instead of silently overwriting.

### Availability

`AvailabilityService` intersects the weekly schedule, court status, blocks and
existing bookings. Intervals are half-open, so back-to-back bookings
(18:00–19:00 and 19:00–20:00) are both allowed. Times are handled in
Asia/Manila and stored in UTC.

### Actions

`BookCourt`, `ChangeBookingStatus` (confirm / check in / complete / no show /
refund / cancel) and `RescheduleBooking`. Each locks the rows it reads and
writes a `booking_status_history` row. Rescheduling excludes the booking from
its own overlap check and reprices at the current rate.

### Verification

| Gate | Result |
| --- | --- |
| Tests | Pass — 107 tests, 534 assertions |
| ESLint / TypeScript / Prettier | Pass |
| PHPStan (L5) / Pint | Pass |
| Production build | Pass |
| Live `/book` page | Pass — HTTP 200, Inertia `Book` component |

### Issues found and fixed

1. **`Carbon::diffInMinutes()` returns a signed float in Carbon 4**, so
   `duration_minutes` was stored as `-60` and MySQL rejected the row. Wrapped
   in `abs()` and cast to `int`.
2. `booking_status_history` is singular, so Eloquent inferred
   `booking_status_histories`. Set `$table` explicitly.
3. `CarbonInterface::parse()` is abstract and cannot be called statically.
4. One test of mine asserted the wrong thing (it changed the price *before*
   booking, so both sides were 500.00) — corrected so it actually proves
   repricing.
5. `assertInertia()->has($key, $value)` means "assert the count", not equality;
   used `where()` for value assertions.

### Notes

- Payment capture is Phase 5; bookings are created confirmed and paid later.
- True multi-process concurrency is not exercised by PHPUnit. The row-lock
  ordering is verified by asserting that a second booking attempt for the same
  slot never lands; real contention belongs in the Phase 17 browser tests.

---

## Phase 5 — Philippine Payments & Receipts

**Status: NOT STARTED**

### Scope

- [ ] Payment abstraction with a PayMongo driver (GCash, Maya, cards)
- [ ] Cash, manual bank transfer and POS payment methods
- [ ] Payment intent, transaction, status, reference and refund records
- [ ] Webhooks with signature verification
- [ ] Peso formatting to `₱1,250.00`
- [ ] **Never store raw card data**