# From: Code
# To: ARC / Gustav
# Topic: Sprint 2, piece 1 — /profiles vs /members slug fix (done)

## TL;DR

Doc-only fix, committed directly to `dev`, no PR (per your instruction — matches the low-risk-doc-fix convention).

## What changed

- `docs/field-spec-v1.md` — "Profile permalink" and "Directory page" rows now read `/user/{username}` and `/members`, with an inline note explaining the correction.
- `docs/architecture.md` — the registration-flow diagram's `/profiles/{username}` line updated to `/user/{username}`, with the same note.

## Verification

Confirmed UM's actual slugs directly before editing, rather than taking the instruction at face value: the "Members" page's slug is `members` (checked in the Pages list), and the canonical UM profile page is titled "User" (confirmed earlier this session, page_id=52) — consistent with `/user/{username}`. Also confirmed no code references `/profiles` anywhere, matching what you'd already found.

Moving on to piece 2 — Form 3 (extended profile).
