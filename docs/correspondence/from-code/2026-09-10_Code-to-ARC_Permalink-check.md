# From: Code
# To: ARC / Gustav
# Topic: Permalink check before Sprint 3 — pass/fail on /dashboard, /members, /user/{username}

## TL;DR

**Permalinks are set to Plain** (`?p=123`), confirmed directly in Settings → Permalinks. Of the three paths: **two fail cleanly (404), one fails in a worse way than a 404** — it silently lands on a completely different, separate WordPress installation. Sprint 1's verification had a real gap. None of these three paths are safe to wire a Sprint 3 button to as-is.

## Permalink setting, read directly

Settings → Permalinks → Common Settings: **Plain** is the selected radio (`https://recrewt.app/reliant/?p=123`), confirmed by reading the actual checked radio input, not just the visible options list. "Post name" is not selected. This matches the "Post name permalinks are disabled" notice WP Staging shows throughout wp-admin, and matches what my own earlier report on the Extended Profile page already ran into (I used `?page_id=461` there for exactly this reason).

## The three paths

**`/dashboard` — FAIL (404, but clean).**
`https://recrewt.app/reliant/dashboard` returns a genuine 404 within the staging install itself (confirmed via network request: `GET .../reliant/dashboard → 404`, URL unchanged, page-not-found template). The Dashboard page only resolves at `?page_id=452`.

**`/user/talent-p1-test` — FAIL (404, but clean).**
Same pattern: `https://recrewt.app/reliant/user/talent-p1-test` returns a clean 404 within staging (confirmed via network request, URL unchanged). UM's profile permalink only resolves via its own internal routing when permalinks are pretty — under Plain, there's no `/user/{username}` route at all on this install.

**`/members` — FAIL, and worse than a 404.**
`https://recrewt.app/reliant/members` does not 404. It silently redirects to `https://recrewt.app/members/` (HTTP 200) — dropping the `/reliant/` prefix entirely and landing on a **separate, independent WordPress installation at the site root**, not the staging site. Confirmed this is a genuinely different install, not a path quirk of the same one: the root has its own separate wp-admin login (my staging session doesn't carry over), and none of the assets on the resulting page load from `/reliant/` — they all load from site root (`/wp-content/...`, `/wp-includes/...`). The root install happens to have its own page with the slug "members" and (unlike staging) pretty permalinks enabled, so it resolves and redirects cleanly — which is exactly what makes this worse than a plain 404: someone clicking a `/members` link would land on what looks like a working page, on a site that isn't the one being worked on, with no error to notice.

I did not dig into the server-level cause (likely the `/reliant/` staging subdirectory not having its own working rewrite rules, so unmatched requests fall through to the root install's routing) — that's infrastructure-level and out of scope for what was asked. Flagging it as a hypothesis, not a confirmed root cause. `/dashboard` and `/user/talent-p1-test` didn't trigger the same fallthrough, most likely because the root site has no page/user matching those specific slugs — so the escape is silent and slug-dependent, not something that would show up unless you happened to test a path the root site also has.

## What this means for Sprint 1's verification and Sprint 3

Sprint 1's redirect-collision fix was verified against the paths in `field-spec-v1.md` / `architecture.md` (`/profiles` → `/user/{username}`, etc.) as if they were live, resolvable URLs. They aren't, under this site's actual permalink setting — and one of them actively leaks to a different site. That's a real gap in what was verified, not just a Sprint 3 planning wrinkle, per your framing.

For Sprint 3: any tier button or link should use the `?page_id=`/`?p=` query-string form (as I already did for Extended Profile), not a path-based URL, unless permalinks get switched to "Post name" first — and if that switch happens, the `/members` cross-site leak should get retested too, since a working rewrite rule on `/reliant/` might change or fix that behavior, or might not.
