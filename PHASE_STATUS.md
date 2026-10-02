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

**Status: NOT STARTED**

**Local URL:** http://localhost:8000

### Scope

- [ ] Home, Courts, Facilities, Pricing, Memberships, Events, Tournaments,
      About, Contact, FAQ, Blog, Login, Register, Book a Court
- [ ] Components: Header, Footer, Hero, CourtCard, FacilityCard, PricingCard,
      BookingCTA, Testimonials, FAQ, EventCard, MobileNav
- [ ] SEO: metadata, Open Graph, Twitter cards, Schema.org, LocalBusiness,
      SportsActivityLocation, sitemap, robots, canonicals
- [ ] Performance: lazy loading, WebP/AVIF, responsive images, code splitting
- [ ] Royalty-free media with credits in `resources/media/media-credits.md`

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