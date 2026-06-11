# reCREWt — Sprint log

Living document. Every significant decision, rejected option, and outcome gets logged here.
Most recent entry at the top.

---

## Sprint 1 — Registration + basic talent profile

**Started:** June 2026
**Status:** In progress

### Decisions made

**Stack: WP ecosystem for POC, not the Next.js stack from the handoff doc**
- The handoff document recommended Next.js, NestJS, PostgreSQL, Clerk, and S3.
- The live site already had Elementor Pro, Ultimate Member, and WP Job Manager installed.
- Decision: use the WP ecosystem for POC to avoid rebuilding auth, profiles, uploads, and search from scratch.
- Next.js stack remains the target for a future migration once scale or AI search demands it.
- Rationale: POC speed and cost. Rebuilding the handoff stack adds 6-8 weeks before a single user can register.

**Model: Sonnet 4.6 over Haiku for development sessions**
- Haiku considered for cost saving.
- Decision: stay on Sonnet 4.6 for architecture, code, and spec work.
- Haiku reserved for bulk content generation or simple lookups.
- Rationale: shallower Haiku outputs on code tasks cost more tokens to correct than Sonnet's higher upfront cost.

**Swipe feature: Sprint 3, not in tandem with Sprint 1**
- Considered building swipe UI in parallel.
- Decision: strictly sequential. Swipe depends on the directory, which depends on profiles existing.
- Rationale: parallel tracks that share integration points create more rework than time saved.

**Registration form: 3 fields only (email, password, confirm)**
- Considered adding name and phone to registration.
- Decision: defer all non-auth fields to the profile setup form.
- Rationale: friction on registration is the leading cause of drop-off. Keep the gate minimal.

**Ethnicity field: deferred to Sprint 2, optional, POPIA-gated**
- Field is standard in casting industry workflows.
- Decision: not in Sprint 1. When added, it must be optional, clearly labelled, visible only to verified casting professionals, and accompanied by a POPIA consent statement.
- Outstanding: Gustav to confirm POPIA consent wording with legal counsel before Sprint 2.

**DOB: stored in full, displayed as age range publicly**
- Full date of birth required for casting (age-specific roles).
- Decision: store full DOB in UM user meta, but display only a derived age range on public profiles (e.g. "25-30"). Full DOB visible to casting_pro and admin roles only.

**Video showreels: URL embed for POC, not direct upload**
- Direct video upload requires transcoding infrastructure (R2/S3 + worker queue).
- Decision: accept YouTube or Vimeo URL in the extended profile form (Sprint 2). Direct upload deferred to post-POC.
- Bottleneck flag: direct video upload will require paid infrastructure. Estimated cost TBD.

**GitHub: set up at start of Sprint 1**
- Decision: repo created before any WP admin work begins.
- Custom code only — no vendor plugins, no DB, no media.
- Two branches: main (stable) and dev (active).

### Outstanding items

- [ ] Gustav: confirm WP staging environment approach before Elouise builds forms
- [ ] Gustav: confirm POPIA consent wording with legal counsel
- [ ] Gustav + Elouise: agree on SFTP credentials storage (password manager)
- [ ] Elouise: confirm Ultimate Member version number in WP admin
- [ ] Gustav: wire Freelance "Get Started" button to /register once page is created

---

## Backlog decisions (not yet sprinted)

**Payments: PayFast preferred over Stripe for South African market**
- Stripe support for ZAR has improved but PayFast has stronger local adoption and lower fees for SA transactions.
- Decision pending. Both flagged as possible bottlenecks — neither is free.
- Peach Payments also noted as an option.

**AI matching: deferred to Sprint 6**
- Requires vector DB (pgvector or Pinecone) and embedding pipeline.
- Not buildable in WP ecosystem without a separate microservice.
- Sprint 6 target: Railway-hosted Node service with Anthropic API for semantic matching.
- Cost bottleneck: Pinecone free tier limited; pgvector requires a managed Postgres instance.

**React Native / Flutter app: post-POC roadmap only**
- Mobile web (Elementor responsive) is the POC mobile strategy.
- Native app deferred until POC is validated with real users.
