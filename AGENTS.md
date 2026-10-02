# AGENTS.md

This file mirrors [`CLAUDE.md`](CLAUDE.md) so the same instructions apply to any
AI coding agent regardless of the tool it runs under.

**Read `plan.md` first** — it is the master specification. Then read
`PHASE_STATUS.md` to see which phase is complete and what is next.

Summary of the non-negotiables:

1. Implement **one phase at a time**; do not start the next phase until the
   current one passes every gate in `PHASE_STATUS.md`.
2. **Never commit secrets.** No `.env`, credentials, API keys or certificates.
   No credentials hard-coded anywhere, including seeders and comments.
3. **Booking integrity first** — transactions and row locking for bookings,
   payments and stock (plan.md §31).
4. Keep tenant isolation explicit; every tenant-owned model carries
   `organization_id`.
5. Use Tailwind design tokens, never raw hex values in components.
6. Philippine peso and `Asia/Manila` everywhere.
7. Write tests with new functionality and keep all quality gates green.

Full detail: [`CLAUDE.md`](CLAUDE.md).