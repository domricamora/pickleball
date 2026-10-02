# Philippine Pickleball Courts SaaS Platform — AI Coding Implementation Plan

## 1. Project Goal

Build a modern, scalable SaaS platform for Philippine pickleball court operators and facilities.

The platform will combine:

- Public marketing website
- Court rental and online booking
- Court availability and scheduling
- Customer/member management
- POS and sales
- Products and merchandise sales
- Court packages, memberships and promos
- Payments and receipts
- Staff and operations management
- Inventory
- Expenses and basic accounting
- Reports and dashboards
- Notifications
- Multi-branch readiness
- SaaS-ready tenant architecture
- AI-assisted operational features in later phases

Primary stack:

- Laravel
- React
- Tailwind CSS
- Vite
- MySQL
- REST/JSON APIs where appropriate
- Git/GitHub
- PHPUnit/Pest + Laravel testing
- Playwright or equivalent browser/E2E testing
- PHP 8.3+
- Node.js current LTS

Repository:

`domricamora/pickleball.git`

Live:

- Temporary domain: `pickleball.deskpulse.click`
- Live folder: `pickleball.deskpulse.click`
- Existing SSH connection: `ck.deskpulse.click`

Local development requirement:

**Every phase must be runnable and reviewable on localhost before moving to the next phase.**

---

# 2. Important Security Rule

The credentials supplied for initial setup must NEVER be committed to Git.

Initial administrator:

- Email: `<ADMIN_EMAIL>`
- Password: `<ADMIN_PASSWORD>`

Live database:

- Database: `<LIVE_DB_DATABASE>`
- Username: `<LIVE_DB_USERNAME>`
- Password: `<LIVE_DB_PASSWORD>`

> **Redacted on purpose.** The real values were supplied out-of-band and are
> intentionally not stored in this repository (see rule above). Set them in the
> local and server `.env` files, and rotate the administrator password after
> first login.

These values must only exist in:

- local `.env`
- server `.env`
- deployment secret/configuration
- installation/setup documentation where absolutely necessary

Add `.env`, `.env.*`, credential files and deployment secrets to `.gitignore`.

The first admin password must be changed after first login.

Do not expose database credentials in source code, JavaScript, API responses, logs, screenshots, seeders, or GitHub.

---

# 3. Brand / Visual Direction

## Brand personality

The site should feel:

- Modern
- Sporty
- Energetic
- Friendly
- Premium but approachable
- Social/community-driven
- Philippine-local
- Easy for beginners
- Exciting for experienced players

Avoid the typical dark, overly corporate SaaS appearance.

The visual language should communicate:

**PLAY · BOOK · COMPETE · CONNECT**

## Recommended color system

Primary:

- Pickle Green: `#3A9D5D`
- Deep Court Green: `#14532D`
- Court Lime: `#B7E34A`

Energy accent:

- Coral/Orange: `#FF6B35`

Neutrals:

- Ink: `#10231A`
- Slate: `#475569`
- Soft Background: `#F7FAF5`
- White: `#FFFFFF`
- Border: `#DDE7DF`

Use green as the core brand color, lime for energetic highlights, and orange selectively for calls-to-action and important actions.

Do not use too many accent colors.

## Typography

Primary font:

**Plus Jakarta Sans**

Use:

- 800/700 for major headings
- 600 for section headings
- 500/400 for body text

Optional display treatment:

Use oversized bold headings with tight letter spacing for sports-style impact.

## UI style

Use:

- Large rounded cards
- 16–24px corner radius
- Soft shadows
- Strong whitespace
- Large photography
- Court-line inspired graphics
- Subtle gradients
- Pill badges
- Bold CTA buttons
- Large availability cards
- Calendar/schedule interfaces
- Mobile-first navigation

Avoid excessive glassmorphism.

---

# 4. Marketing Website

The marketing site is a major product surface, not an afterthought.

## Homepage structure

### Hero

Headline concept:

**Your Court. Your Game. Your Community.**

Supporting message:

Book pickleball courts, join games, manage memberships, buy products and stay connected with your local pickleball community.

Primary CTA:

**Book a Court**

Secondary CTA:

**Explore Facilities**

Hero imagery/video should show an authentic Asian/Filipino pickleball environment where possible.

### Section: Find Your Game

Show:

- Court locations
- Available times
- Court type
- Indoor/outdoor
- Pricing
- Distance
- Availability

### Section: Book in Seconds

Three-step flow:

1. Choose facility
2. Pick date and time
3. Pay and receive confirmation

### Section: More Than Court Rental

Cards for:

- Court bookings
- Open play
- Tournaments
- Coaching
- Memberships
- Equipment
- Events

### Section: Built for Court Owners

Explain:

- Booking management
- Customer management
- POS
- Inventory
- Staff
- Reports
- Promotions
- Analytics

CTA:

**Run Your Pickleball Facility Smarter**

### Community section

Highlight:

- Local players
- Leagues
- Events
- Tournaments
- Beginner sessions
- Social games

### Testimonials

Use real testimonials once available.

Do not fabricate testimonials.

### Final CTA

**Ready to Play?**

Book your court today.

---

# 5. Royalty-Free Media Strategy

Use legally reusable media only.

Preferred sources:

- Unsplash
- Pexels
- Pixabay
- Wikimedia Commons where licensing is confirmed

Search specifically for:

- Asian pickleball players
- Filipino sports players
- Southeast Asian athletes
- Asian indoor sports facilities
- Asian friends playing sports
- Philippine lifestyle/sports scenes
- pickleball courts
- pickleball paddles
- pickleball communities

Important:

Do not imply that a stock actor/player is a real customer or employee.

Record:

- source URL
- author/creator
- license
- download date

Create:

`resources/media/media-credits.md`

Videos should be optimized locally or via appropriate CDN delivery.

Use:

- poster image
- compressed MP4/WebM where licensing permits
- lazy loading
- responsive images
- modern formats
- mobile fallback image

Never download copyrighted commercial advertising footage.

---

# 6. SaaS Architecture

Design the system as modular from day one.

Core domains:

1. Authentication
2. Organizations/tenants
3. Facilities/branches
4. Courts
5. Court schedules
6. Customers
7. Bookings
8. Payments
9. Memberships
10. Events
11. Tournaments
12. POS
13. Products
14. Inventory
15. Staff
16. Expenses
17. Reports
18. Notifications
19. Marketing
20. Audit logs
21. Settings
22. SaaS administration

Future-ready:

- Multi-location
- Multiple organizations
- White-label branding
- Subscription billing
- Module-based billing
- API access
- Mobile application
- AI assistant

---

# 7. User Roles

Implement RBAC.

Initial roles:

### Super Admin

Controls:

- SaaS tenants
- Plans
- Modules
- Global settings
- Platform reports
- Administrators
- System configuration

### Facility Owner

Controls:

- Facility
- Courts
- Pricing
- Bookings
- Customers
- Staff
- POS
- Inventory
- Reports

### Manager

Operational management.

### Front Desk

Bookings, check-in, payments and customer assistance.

### Cashier

POS and payments.

### Staff

Limited operational access.

### Coach

Sessions, clients and schedules.

### Customer / Player

- Profile
- Book courts
- Pay
- View bookings
- Membership
- Events
- Orders
- Notifications

---

# 8. Phase 0 — Project Foundation

## Objectives

Create the clean Laravel foundation.

Tasks:

- Initialize Laravel
- Configure PHP
- Install React integration
- Install Tailwind
- Configure Vite
- Configure ESLint/formatting
- Configure Git
- Configure environment files
- Configure MySQL
- Configure testing
- Create basic application shell
- Establish architecture conventions
- Add README
- Add CLAUDE.md / AI coding instructions
- Add project documentation

Git:

`domricamora/pickleball.git`

## Acceptance test

Run locally:

- Laravel homepage
- React page
- Tailwind styling
- MySQL connection
- migrations
- tests
- production build

---

# 9. Phase 1 — Marketing Site

Build the complete public website first.

Pages:

- Home
- Courts
- Facilities
- Pricing
- Memberships
- Events
- Tournaments
- About
- Contact
- FAQ
- Blog/news
- Login
- Register
- Book a Court

Components:

- Header
- Footer
- Hero
- Court cards
- Facility cards
- Pricing cards
- Booking CTA
- Testimonials
- FAQ
- Event cards
- Mobile navigation

SEO:

- Metadata
- Open Graph
- Twitter/X cards
- Schema.org
- LocalBusiness schema
- SportsActivityLocation where appropriate
- Sitemap
- Robots
- Canonicals
- Clean URLs

Performance:

- Lazy loading
- WebP/AVIF
- Responsive images
- Video optimization
- Code splitting
- Minimal JavaScript
- Core Web Vitals monitoring

## Local checkpoint

The entire marketing site must work on localhost.

---

# 10. Phase 2 — Authentication & SaaS Foundation

Implement:

- Registration
- Login
- Logout
- Password reset
- Email verification
- Profile
- Role permissions
- Organization/tenant model
- Branch model
- Admin dashboard

Use secure authorization policies.

Create initial admin through a controlled seeder or installation command using environment variables.

Do not hard-code credentials.

---

# 11. Phase 3 — Facility & Court Management

Admin functionality:

Facilities:

- Name
- Address
- Barangay
- City
- Province
- Region
- Contact
- Operating hours
- Photos
- Description
- GPS coordinates

Courts:

- Court name
- Number
- Type
- Indoor/outdoor
- Surface
- Status
- Capacity
- Amenities
- Pricing

Schedule:

- Operating hours
- Blocked periods
- Maintenance
- Holidays
- Special schedules

Pricing:

- Weekday
- Weekend
- Peak
- Off-peak
- Holiday
- Member price
- Guest price

---

# 12. Phase 4 — Booking Engine

This is the core business module.

Features:

- Court availability
- Calendar
- Time slots
- Booking creation
- Booking modification
- Cancellation
- Rescheduling
- Booking confirmation
- Check-in
- No-show
- Refund status
- Booking history

Prevent:

- Double booking
- Invalid time ranges
- Booking outside operating hours
- Booking blocked courts

Use database transactions and locking where necessary.

Booking statuses:

- Pending
- Confirmed
- Checked In
- Completed
- Cancelled
- No Show
- Refunded

Customer booking flow:

Facility → Date → Court → Time → Customer → Payment → Confirmation

---

# 13. Phase 5 — Philippine Payments & Receipts

Build payment abstraction so multiple gateways can be supported.

Primary target:

**PayMongo**

Support where applicable:

- GCash
- Maya
- Cards
- Bank/payment methods available through gateway

Also support:

- Cash
- Manual bank transfer
- POS payment

Payment system:

- Payment intent
- Transaction
- Payment status
- Reference number
- Refund
- Receipt
- Webhooks
- Failed payments

Never store raw card information.

Add official Philippine peso formatting:

`₱1,250.00`

Use Asia/Manila timezone.

---

# 14. Phase 6 — Customer / Player CRM

Customer profile:

- Name
- Email
- Mobile
- Birthday
- Address
- Emergency contact
- Skill level
- Preferred playing time
- Membership
- Booking history
- Purchase history
- Event history
- Notes
- Consent/preferences

Customer segmentation:

- New player
- Active player
- Inactive player
- Member
- VIP
- Tournament player
- Frequent renter

Dashboard metrics:

- Total bookings
- Total spending
- Last visit
- Favorite facility
- Favorite court
- Membership status

---

# 15. Phase 7 — Memberships & Packages

Membership plans:

- Monthly
- Quarterly
- Annual
- Custom

Benefits:

- Discount
- Priority booking
- Free sessions
- Member-only events
- Guest passes

Packages:

- 5 sessions
- 10 sessions
- Monthly unlimited
- Off-peak package

Track usage and expiration.

Prevent overuse.

---

# 16. Phase 8 — Events, Open Play & Tournaments

Events:

- Open play
- Beginner sessions
- Clinics
- Coaching
- Leagues
- Tournaments
- Community events

Tournament functionality:

- Registration
- Brackets
- Divisions
- Player/team registration
- Match scheduling
- Scores
- Standings
- Results

Future architecture should support:

- Singles
- Doubles
- Mixed doubles
- Skill divisions

---

# 17. Phase 9 — POS & Product Sales

Sell:

- Pickleball paddles
- Balls
- Grips
- Bags
- Apparel
- Drinks
- Snacks
- Accessories
- Rental equipment

POS features:

- Product search
- Barcode support
- Cart
- Discounts
- Customer association
- Cash
- Digital payment
- Receipt
- Refund
- Daily closing

---

# 18. Phase 10 — Inventory

Inventory:

- Products
- Categories
- SKU
- Barcode
- Suppliers
- Cost
- Selling price
- Stock
- Reorder level
- Purchase orders
- Stock adjustments
- Transfers
- Damaged stock

Inventory movements must be auditable.

---

# 19. Phase 11 — Staff & Operations

Staff:

- Employee profile
- Role
- Branch
- Schedule
- Attendance
- Time-in/out
- Leave
- Commission where applicable

Operations:

- Opening checklist
- Closing checklist
- Court maintenance
- Equipment maintenance
- Incident reports
- Staff tasks

---

# 20. Phase 12 — Finance & Reporting

Build operational finance first.

Reports:

- Daily sales
- Court revenue
- Product revenue
- Membership revenue
- Event revenue
- Expenses
- Refunds
- Payment methods
- Outstanding amounts
- Revenue by branch
- Revenue by court
- Revenue by day/time
- Customer spending

Dashboard KPIs:

- Revenue
- Bookings
- Occupancy
- Average booking value
- Active members
- New customers
- Repeat customers
- Product sales

Use Philippine Peso.

Keep full accounting/GL architecture modular so it can be expanded later.

---

# 21. Phase 13 — Notifications & Marketing Automation

Channels:

- Email
- SMS-ready architecture
- In-app notifications

Notifications:

- Booking confirmation
- Payment confirmation
- Booking reminder
- Cancellation
- Reschedule
- Event registration
- Membership expiration
- Package expiration
- Payment failure

Marketing automation:

- Welcome sequence
- Abandoned booking
- Inactive player reactivation
- Membership renewal
- Event promotion
- Birthday campaigns

Build queues/jobs rather than synchronous notification sending.

---

# 22. Phase 14 — AI Features

AI should assist operations rather than control financial or booking decisions without validation.

Possible features:

### AI Facility Assistant

Questions:

- Which courts are available?
- What are today's bookings?
- Which hours are most popular?
- Which memberships are expiring?

### AI Business Insights

Generate summaries such as:

- busiest periods
- underused courts
- customer retention trends
- inventory risks
- revenue patterns

### AI Customer Assistant

Help users:

- find courts
- understand pricing
- find events
- understand memberships

### AI Content Assistant

Generate draft:

- social posts
- event descriptions
- email campaigns
- promotional copy

All generated operational data must remain subject to normal application permissions.

---

# 23. Phase 15 — Analytics

Track:

- Traffic
- Leads
- Registrations
- Booking conversion
- Checkout abandonment
- Revenue
- Customer retention
- Court utilization
- Membership conversion

Marketing funnel:

Visitor → Facility View → Court View → Booking → Payment → Completed Booking → Repeat Booking

---

# 24. Phase 16 — Security Hardening

Implement:

- CSRF
- XSS protection
- SQL injection prevention
- Rate limiting
- Authentication throttling
- Authorization policies
- Audit logging
- Secure headers
- HTTPS
- Secure cookies
- Session protection
- File upload validation
- Image validation
- Webhook signature validation
- Secrets management

Run:

- dependency audits
- static analysis
- automated tests
- browser tests
- security checks

---

# 25. Phase 17 — Testing & QA

Every module must have:

### Unit tests

Business logic.

### Feature tests

Laravel endpoints and workflows.

### Browser/E2E tests

Critical flows:

1. Register
2. Login
3. Search facility
4. View court
5. Select date
6. Select time
7. Book
8. Pay
9. Receive confirmation
10. Admin sees booking
11. Admin checks in customer
12. POS sale
13. Inventory deduction

Test edge cases:

- simultaneous bookings
- cancelled bookings
- payment failure
- expired sessions
- blocked courts
- invalid schedules
- timezone boundaries

---

# 26. Phase 18 — Deployment

Deployment target:

`pickleball.deskpulse.click`

Server connection:

`ck.deskpulse.click`

Live directory:

`pickleball.deskpulse.click`

Deployment sequence:

1. SSH into server
2. Prepare PHP environment
3. Clone repository
4. Configure `.env`
5. Configure database
6. Configure web root
7. Configure PHP
8. Install Composer dependencies
9. Build frontend
10. Run migrations
11. Run seed/setup commands
12. Configure storage
13. Configure queue worker
14. Configure scheduler
15. Configure SSL
16. Configure cache
17. Run production tests
18. Smoke test public site
19. Smoke test login
20. Smoke test booking
21. Smoke test admin

Never upload `.env` to GitHub.

---

# 27. Git Workflow

Repository:

`domricamora/pickleball.git`

Branches:

- main
- develop
- feature/*
- fix/*
- hotfix/*

Recommended workflow:

Phase branch → implementation → tests → local review → commit → merge.

Commit examples:

`feat(marketing): build pickleball landing page`

`feat(booking): add court availability engine`

`fix(booking): prevent overlapping reservations`

`feat(pos): add product checkout`

Never commit:

- passwords
- API keys
- database credentials
- `.env`
- private certificates
- production logs

---

# 28. AI Coding Instructions

The AI coding agent must follow these rules.

## Before coding

1. Inspect existing repository.
2. Inspect current Laravel version.
3. Inspect package versions.
4. Inspect database structure.
5. Do not overwrite working functionality blindly.
6. Create a plan for the current phase.
7. Implement only the current phase.

## During coding

- Prefer Laravel conventions.
- Keep controllers thin.
- Put business logic in services/actions where appropriate.
- Use Form Requests.
- Use Policies.
- Use Events/Listeners where appropriate.
- Use Jobs for asynchronous tasks.
- Use database transactions for financial/booking operations.
- Use reusable React components.
- Use Tailwind design tokens.
- Keep tenant isolation explicit.
- Validate all inputs.
- Use migrations rather than manual database changes.
- Write tests with new functionality.

## After coding

Run:

- tests
- lint
- type checking where configured
- production frontend build
- PHP static analysis where configured

Then start the local application and visually verify the phase.

Do not begin the next phase until the current phase passes its acceptance checklist.

---

# 29. Local Development Checkpoint

Each phase must finish with a visible localhost milestone.

Example:

Phase 1:

`http://localhost:8000`

Phase 2:

`http://localhost:8000/login`

Phase 3:

`http://localhost:8000/admin/facilities`

Phase 4:

`http://localhost:8000/book`

Phase 5:

Payment test environment

Phase 6:

`/admin/customers`

etc.

Create a `PHASE_STATUS.md` file.

Example:

```text
Phase 4 — Booking Engine
Status: COMPLETE

Local URL:
http://localhost:8000/book

Completed:
[x] Court calendar
[x] Availability
[x] Booking creation
[x] Double-booking protection
[x] Cancellation

Tests:
[x] Feature tests
[x] Browser tests

Next:
Phase 5 — Payments
```

---

# 30. Recommended Database Domains

Core tables should include approximately:

- users
- roles
- permissions
- organizations
- organization_users
- facilities
- facility_settings
- courts
- court_schedules
- court_blocks
- court_prices
- customers
- customer_profiles
- bookings
- booking_items
- booking_status_history
- payments
- payment_transactions
- refunds
- memberships
- membership_plans
- membership_usages
- packages
- package_usages
- events
- event_registrations
- tournaments
- tournament_divisions
- tournament_players
- tournament_matches
- products
- product_categories
- suppliers
- inventory
- inventory_movements
- purchase_orders
- sales
- sale_items
- expenses
- staff
- staff_schedules
- attendance
- notifications
- marketing_campaigns
- audit_logs

Add indexes deliberately for:

- tenant/organization
- facility
- court
- booking date
- booking status
- customer
- payment status

---

# 31. Booking Availability Logic

Availability must consider:

1. Facility hours
2. Court operating hours
3. Court status
4. Maintenance blocks
5. Existing bookings
6. Event reservations
7. Tournament reservations
8. Customer booking rules
9. Membership restrictions
10. Holidays/special schedules

The system must prevent overlapping confirmed reservations at the database/application level.

Use transactions and appropriate locking.

---

# 32. Philippine Business Considerations

Design for:

- Philippine Peso
- Asia/Manila timezone
- Philippine mobile numbers
- GCash/Maya-compatible payment gateway architecture
- Philippine addresses
- Barangay
- Municipality/City
- Province
- Region
- Official receipts/invoice-ready records
- VAT/non-VAT configuration
- Discounts
- Refunds
- Cash transactions
- Bank transfers

Do not hard-code tax assumptions. Make tax configuration administrative.

---

# 33. Marketing SEO Strategy

Target search intent around:

- pickleball courts Philippines
- pickleball court rental
- pickleball court Manila
- pickleball near me
- pickleball booking Philippines
- pickleball courts [city]
- pickleball tournaments Philippines
- pickleball lessons Philippines
- pickleball equipment Philippines

Create location landing pages once facilities exist.

Example:

`/pickleball-courts/manila`

`/pickleball-courts/quezon-city`

`/pickleball-courts/cebu`

Do not create fake facilities or locations merely for SEO.

---

# 34. Admin Dashboard

Dashboard should immediately show:

- Today's bookings
- Today's revenue
- Court occupancy
- Upcoming bookings
- Pending payments
- New customers
- Membership expirations
- Low-stock products
- Upcoming events
- Staff status

Charts:

- Revenue trend
- Booking trend
- Court utilization
- Customer growth
- Product sales

---

# 35. Customer Dashboard

Customer should see:

- Upcoming bookings
- Booking history
- Membership
- Packages
- Event registrations
- Purchases
- Saved facilities
- Notifications
- Profile

Primary action:

**Book a Court**

---

# 36. UX Principles

The booking process should be extremely short.

Target:

**Facility → Court → Time → Customer → Payment → Confirmation**

Do not force users through unnecessary forms.

Mobile UX is critical because many Philippine customers will access the platform through phones.

Use:

- sticky booking CTA
- thumb-friendly controls
- large time slots
- clear pricing
- simple checkout
- clear payment status

---

# 37. Performance Targets

Marketing:

- LCP under 2.5s target
- CLS under 0.1 target
- optimized images
- lazy media
- minimal third-party scripts

Application:

- indexed database queries
- pagination
- caching
- queued jobs
- optimized API responses
- server-side authorization

---

# 38. Phase Completion Rule

A phase is NOT complete until:

- Code implemented
- Database migrations completed
- Tests written
- Tests passing
- Frontend build passing
- Localhost verified
- Responsive UI checked
- Security implications reviewed
- Documentation updated
- `PHASE_STATUS.md` updated
- Git commit created
- User can visually review the phase locally

Only then proceed to the next phase.

---

# 39. Final Launch Checklist

Before production:

- [ ] Production `.env`
- [ ] Database configured
- [ ] APP_KEY configured
- [ ] APP_DEBUG=false
- [ ] HTTPS active
- [ ] Storage linked
- [ ] Queue worker active
- [ ] Scheduler active
- [ ] Mail configured
- [ ] Payment gateway configured
- [ ] Webhooks configured
- [ ] Admin password changed
- [ ] Database credentials verified
- [ ] Backups configured
- [ ] Logs reviewed
- [ ] Error monitoring configured
- [ ] Sitemap generated
- [ ] Robots configured
- [ ] Analytics configured
- [ ] Booking tested
- [ ] Payment tested
- [ ] Refund tested
- [ ] POS tested
- [ ] Inventory tested
- [ ] Mobile tested
- [ ] Desktop tested
- [ ] Production smoke test passed

---

# 40. Master AI Agent Instruction

Build this project incrementally.

Do not attempt to implement the entire platform in one pass.

For every phase:

1. Read this plan.
2. Inspect the repository.
3. Inspect current implementation.
4. Identify the smallest safe implementation.
5. Build the phase.
6. Write/update tests.
7. Run tests.
8. Run frontend build.
9. Start the local server.
10. Verify the feature on localhost.
11. Update documentation.
12. Update `PHASE_STATUS.md`.
13. Commit the phase.
14. Stop and report the phase as complete.
15. Wait for approval before beginning the next phase.

The application must always remain runnable.

Prioritize:

**Security → Correctness → Booking integrity → UX → Performance → Scalability → Advanced AI**

The platform should be capable of growing from one Philippine pickleball facility into a multi-facility SaaS product without requiring a complete rewrite.
