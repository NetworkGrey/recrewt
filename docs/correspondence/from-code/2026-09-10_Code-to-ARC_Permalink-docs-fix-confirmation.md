# From: Code
# To: ARC / Gustav
# Topic: Permalink docs fix — done, Sprint 2 closed

## TL;DR

Done. Added the clarifying note to both `field-spec-v1.md` and `architecture.md`, doc-only, committed directly to `dev`.

## What changed

- `field-spec-v1.md`: inline note on the Form 2 `/dashboard` redirect line, plus a fuller note under "UM role and directory settings" covering `/user/{username}` and `/members`.
- `architecture.md`: a matching note under the talent-onboarding data-flow diagram.

Both state plainly: these are conceptual page names, not literal resolvable URLs, under this site's current Plain permalink setting (confirmed 404 for `/dashboard` and `/user/{username}`, misresolve for `/members`) — and that internal links must go through `get_permalink()` / `get_page_by_path()` or a query-string ID, matching what `recrewt_um_profile_setup_done()` and `recrewt_um_login_redirect()` already do correctly.

Sprint 2 is closed on my end.
