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

**Status: NOT STARTED**

**Local URL:** http://localhost:8000/login

### Scope

- [ ] Registration, login, logout, password reset, email verification, profile
- [ ] Role permissions (Super Admin, Facility Owner, Manager, Front Desk,
      Cashier, Staff, Coach, Customer)
- [ ] Organization/tenant model with global scope
- [ ] Branch model
- [ ] Admin dashboard
- [ ] Initial admin created through a controlled installer or seeder driven by
      environment variables — never hard-coded (plan.md §10, §2)

---

## Remaining phases

| Phase | Scope | Status |
| --- | --- | --- |
| 2 | Authentication & SaaS Foundation | Pending |
| 3 | Facility & Court Management | Pending |
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

## Definition of done (per plan.md §38)

A phase is **not** complete until:

- [ ] Code implemented
- [ ] Database migrations completed
- [ ] Tests written
- [ ] Tests passing
- [ ] Frontend build passing
- [ ] Localhost verified
- [ ] Responsive UI checked
- [ ] Security implications reviewed
- [ ] Documentation updated
- [ ] `PHASE_STATUS.md` updated
- [ ] Git commit created
- [ ] User can visually review the phase locally