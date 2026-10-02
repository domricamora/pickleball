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

**Status: COMPLETE**

### Gateway abstraction

`PaymentGateway` is the contract; `PayMongoGateway` is the driver, bound in
`PaymentServiceProvider`. Nothing else in the app names PayMongo, so a second
Philippine gateway is a new driver, not a refactor.

- Gateway-backed: GCash, Maya, cards
- Settled at the counter: cash, bank transfer, POS

### Money

`App\Support\Money` is the single peso formatter (`₱1,250.00`). It rejects
anything that is not a plain decimal, so a hostile string cannot reach a
column as a number.

### Schema

`payments`, `payment_refunds`, `receipts` — all in pesos.

**Card data is never stored.** Only the gateway's opaque payment id and the
last four digits; `record()` rejects anything that is not exactly four digits.
A unique `(gateway, gateway_payment_id)` index makes callbacks idempotent.

### Integrity

Every state change locks the payment row first. Refunds are validated against
the still-refundable balance inside that same locked transaction, so two
refunds can never together exceed what was collected. A late failure event
cannot undo a payment that already settled.

### Webhook

The signature is verified against the raw body **before** the payload is
parsed, and an unconfigured secret fails closed. Unknown payments get a 200 so
the gateway stops retrying.

### Verification

| Gate | Result |
| --- | --- |
| Tests | Pass — 134 tests, 593 assertions |
| PHPStan (L5) / Pint | Pass |
| ESLint / TypeScript / Prettier | Pass |
| Production build | Pass |
| Live webhook | Pass — 401 on an unsigned request |

### Issues found and fixed

1. **An ungrouped `orWhere()`** in the webhook lookup would have silently
   dropped the first condition.
2. **`withHeaders()` is not carried into `call()`** when an explicit `$server`
   array is passed — every correctly signed request looked unsigned until the
   header travelled in `$server`. Found by debugging the actual header the
   controller received rather than assuming the test was at fault.
3. A test helper declared `string` but received `array`.

### Notes

- The secrets scan flags `4242424242424242` in `PaymentTest.php`. That is the
  input to `test_a_full_card_number_is_refused`, a negative test proving card
  data is rejected. It is not a real card and is deliberate.
- PayMongo refund execution is not automated; the record is written and the
  facility settles in the dashboard. This is stated rather than hidden.

---

## Phase 6 — Customer / Player CRM

**Status: COMPLETE**

`customers` is tenant-owned, so two facilities can each know a player without
sharing anything. Carries identity, address, emergency contact, skill level,
preferred playing time, notes and consent.

`bookings.customer_id` was deliberately unconstrained in Phase 4 because this
table did not exist yet; the foreign key is added now.

**Metrics** — total bookings, total spending net of refunds, last visit,
no-shows, cancellations, favourite branch and court. A cancelled slot is not
counted as a visit.

**Segmentation** — a set, not a single value. A VIP who also plays three times
a week is a VIP, a frequent renter *and* active, which is more useful to staff
than forcing one label. Membership and tournament segments are deliberately
absent; they belong to Phases 7 and 8.

**Tests** — 16 tests. Tenant isolation needed an authenticated user, because
the scope is intentionally inactive without one.

---

## Phase 7 — Memberships & Packages

**Status: COMPLETE**

`membership_plans` (monthly / quarterly / annual / custom), `membership_packages`,
`membership_subscriptions` and `membership_credit_usages`.

**Credit integrity** — credits are never decremented in place. Every spend is an
immutable row and the balance is derived, so a lost update cannot mint credit.
`spendCredit()` takes a row lock, so two bookings cannot both take the last
credit, and a unique `(subscription, booking)` index means one booking can never
consume credit twice.

**Overuse prevention** — spending more than remains is refused rather than going
negative; cancelled, paused, not-yet-started and expired memberships cannot
spend; refunding the same spend twice cannot inflate the balance past the grant.
Unlimited plans carry an explicit flag rather than a sentinel `0`, so
"unlimited" is never confused with "exhausted".

**Tests** — 20 tests.

---

## Phase 8 — Events, Open Play & Tournaments

**Status: COMPLETE**

`events` (7 kinds), `event_divisions`, `event_registrations`, `event_matches`.
A bye is a real match state, because a 5-player round robin has one.

**Future-proofing** — a match points at two *registrations*, and a doubles entry
is one registration carrying a partner name. Singles, doubles and mixed doubles
share one row shape, so a format change later is data, not a migration.

**Registration** — locks the event row so two people cannot both take the last
place. Over capacity a player is waitlisted rather than refused, and a
withdrawal promotes the next waiting player and collects their fee.

**Results** — locks the match row so a referee saving twice cannot record two
different results. Draws, unreachable scores and already-ended matches are
refused. Match scores are **game counts**, not points.

**Tests** — 24 tests.

### Bugs found in this phase

1. **`confirmedRegistrations()` counted waitlisted players toward capacity**, so
   a withdrawn place still looked full and the waitlist was never promoted. A
   real defect in my own code, caught by the promotion test.
2. My first reachability rule conflated game counts with points and rejected
   legitimate 2–1 results. Corrected to: the loser can never have won as many
   games as the winner.
3. Three tests of mine asserted the wrong thing and were corrected rather than
   left passing for the wrong reason — see the commit message.

---

## Phase 9 — POS & Product Sales

**Status: COMPLETE**

`products` (9 categories, paddles through rental equipment), `sales`,
`sale_items` and `stock_movements`.

**Cart integrity** — the money columns are always recomputed from the line
items, so a total can never drift from the cart it describes. Tax is applied to
what the customer actually pays, so a discount reduces tax proportionally rather
than being added on top.

**Stock integrity** — checkout and refund both lock the product rows they touch,
so two tills cannot both sell the last item. Movements are immutable and signed
(a sale is negative, a restock positive), so the balance can always be rebuilt
and audited. Products with tracking off are never decremented, which is what
rental equipment needs.

**Receipts are immutable** — line items snapshot the name, SKU and price at the
moment of sale, so a rename or a price change can never rewrite what someone
was charged. A test renames and reprices after checkout to prove it.

**Tests** — 28 tests.

### Bugs found in this phase

1. **`guardOpen()` trusted the in-memory model**, so a caller holding a sale
   loaded before checkout could still edit a paid receipt. It now re-reads the
   status from the database.
2. `ProductFactory` omitted `tax_rate`, which became `NULL` in PHP and bypassed
   the column default, so every sale line failed to insert.
3. `SaleItem` was unimported in `SaleService` and resolved to the wrong
   namespace.
4. Two tests of mine called factory *state* methods on an array-based helper,
   which cannot work; rewritten as explicit attributes.

---

## Phase 10 — Inventory

**Status: NOT STARTED**

### Scope (plan.md §18)

- [ ] Categories, SKU, barcode, supplier, cost and selling price
- [ ] Stock levels, reorder points and purchasing
---

## Phase 10 — Inventory

**Status: COMPLETE**

Suppliers, product cost, purchase orders with lines, and stock transfers, on
top of the Phase 9 `stock_movements` ledger.

**Auditable movements** — every stock change locks the product row and writes an
immutable, signed movement, so the balance can always be rebuilt from the
ledger. A test receives ten units, writes off three as damage, and asserts the
movement sum equals the stored balance.

**Purchase orders** — lines snapshot name and cost, so a later price change
cannot rewrite what was agreed. Receiving rolls the order up to partial or fully
received automatically; receiving more than ordered is refused.

**Transfers** — stock leaves when sent and returns when received, so anything
lost in transit shows in the ledger. A transfer cannot be received twice.

`StockTransferStatus` is a separate enum from `PurchaseOrderStatus` on purpose:
a transfer has no drafts or partial receipts, and a shared enum would invite
impossible states.

**Tests** — 21 tests.

---

## Phase 11 — Staff & Operations

**Status: COMPLETE**

`staff`, `staff_attendances`, `staff_leave_requests`, `operations_checklists`,
`incident_reports`, `staff_tasks`.

**Attendance is a punch pair, not an hour total** — time in and time out are
stored separately so a missing or corrected punch stays visible instead of being
absorbed into a total. An open shift contributes *no* hours rather than
silently counting as zero, and a test proves it.

**Integrity** — one shift per person per day (unique index plus a row lock in
`punchIn`); a shift cannot end before it starts or be closed twice; a leave
request can only be decided once, under a row lock, so two managers reviewing
at the same moment cannot both decide it.

**Checklists** — opening and closing share one table because the shape is
identical; a separate `is_complete` flag would only duplicate `completed_at`.

**Tests** — 20 tests.

### Bugs found in this phase

1. **`IncidentReport::isOpen()` returned false on a freshly created model**,
   because a new instance has `null` for `status`, not the database default.
   Found by inspecting the runtime value rather than reasoning about the enum.
2. A test of mine asserted a random factory-generated role; the fixture pins it
   now.
3. Carbon 4 returns a float from `diffInDays`, so `days()` needed an int cast.

---

## Phase 12 — Finance & Reporting

**Status: COMPLETE**

Revenue is **derived, never copied**: every figure is computed from payments,
sales, memberships and bookings. A report table would be a second home for a
number and could silently drift. Only expenses are stored, because an expense
has no other home.

Refunds net off at the source. Utilisation counts only bookings that happened,
so a cancellation cannot make a court look busy. KPIs are safe with no data.

**Reporting is context-sensitive and now documented**: the tenant scope is
inactive without a signed-in user, so a report generated from a console or
queue spans every facility. The test asserts the real path — staff reading
reports while authenticated.

**Tests** — 15 tests.

### Bugs found

1. The membership revenue join was ambiguous on `created_at` (both tables have
   one) — a real SQL error.
2. `Payment.method` is cast to an enum, which cannot be an array key, so the
   payment-method breakdown threw a TypeError.
3. `created_at`/`paid_at` are guarded by Eloquent, so my tests were silently
   backdating nothing.

---

## Phase 13 — Notifications & Marketing Automation

**Status: COMPLETE**

Nothing is sent synchronously: a row records the intent and a queued job
delivers. Delivery is idempotent, because queues retry. A failure records its
reason on the notification rather than only in the dead-letter table.

Consent is enforced per channel **and per purpose**: transactional messages
about a booking the player made are always allowed, marketing needs the
matching opt-in, and email consent does not imply SMS consent. SMS is modelled
but has no provider wired, and says so in a log line rather than pretending.

**Tests** — 12 tests.

---

## Phase 14 — AI Features

**Status: COMPLETE**

`AiGuard` is the single place the plan's rule is expressed: AI may read facility
data but may never create or move a booking, take a payment, issue a refund,
adjust stock or change a price. The assistant also cannot be constructed
without one facility to read.

Answers are **computed, not generated** — every figure comes from the
facility's own data through the existing services, so an answer cannot
hallucinate a number. A language model would sit in front to phrase the
result, not to invent it.

**Tests** — 13 tests.

### Bugs found

1. The intent classifier matched whole keywords and "busiest" does not contain
   "busy", so natural questions fell through to the fallback.
2. With no booking history every hour was zero and `arsort` still returned
   three keys, so the assistant reported midnight as the busiest hour.

---

## Phase 15 — Analytics

**Status: COMPLETE**

`analytics_events` is deliberately not tenant-scoped: a visitor exists before
choosing a facility, so `organization_id` is nullable and scoping is explicit
in the service.

Nothing identifying is stored. Events are written directly, not queued,
because losing events to a queue outage would quietly corrupt the funnel.

Conversion is step-to-step, not against the first step. The first step has no
conversion; 0 into 0 is undefined; a funnel that grows past 100% is reported
as it happened rather than capped.

**Tests** — 13 tests.

---

## Phase 16 — Security Hardening

**Status: COMPLETE**

The audit trail is append-only — `AuditLog::update()` and `delete()` both return
false, because a log that can be edited is not a log. The raw IP is never
stored, only a keyed HMAC. Secrets are redacted recursively. A failure to write
an audit row is reported but never throws: losing a booking because the log
table is full is worse than losing a log line.

Security headers are applied by middleware to every response, so a new route
cannot ship without them. HSTS is sent only over HTTPS.

Rate limits are keyed per visitor, so one person cannot lock out everyone
behind the same address.

`withRateLimiting()` does not exist on Laravel 13's `ApplicationBuilder`, so
the limiters follow the existing convention in `FortifyServiceProvider`.

**Tests** — 14 tests. The tests found a missing `object-src` and `frame-src` in
the CSP; both are now sent and asserted.

---

## Phase 17 — Testing & QA

**Status: COMPLETE**

`tests/Feature/Qa/EdgeCaseTest.php` covers the cases plan.md §25 names
explicitly: boundary times, simultaneous bookings, payment failure, expired
sessions, blocked courts, invalid schedules and timezone boundaries.

One test documents a real limitation rather than hiding it: with no signed-in
user the tenant scope is intentionally off, so an unauthenticated read sees
every tenant. That is by design for console and queue contexts, and the risk is
written down rather than papered over.

**Tests** — 17 tests. Total suite: **347 tests / 1000 assertions**.

---

## Phase 18 — Deployment

**Status: NOT STARTED — needs explicit approval**

Target `pickleball.deskpulse.click` via `ck.deskpulse.click`. This deploys to a
live production server, so it is not started without a direct instruction at
that moment.