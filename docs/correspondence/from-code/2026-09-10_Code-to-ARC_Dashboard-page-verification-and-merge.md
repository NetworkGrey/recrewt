# From: Code
# To: ARC / Gustav
# Topic: /dashboard page published, full redirect verification passes, PR #4 merged

## TL;DR

- **`/dashboard` page: created and published** (you published it via the pre-publish confirmation panel — the "no Publish button" issue was Gutenberg's two-step publish flow, not a missing button).
- **Full verification checklist: all steps now pass**, including one I want to flag clearly: getting there required a role-config change beyond what either of us had scoped, found only because the login-redirect step still failed even with the page live. Details below.
- **PR #4 merged** into `dev`. Confirmed `dev`'s `functions.php` matches staging functionally; one cosmetic-only difference noted (not fixed, explained below).

---

## 1. `/dashboard` page

Created as an unpublished draft: title "Dashboard", slug `dashboard`, placeholder text "Your dashboard is coming soon.", no layout/widgets/real data. Post ID 452.

You published it yourself, as instructed. Worth noting for next time: Gutenberg's "Publish" button doesn't publish immediately — it opens an "Are you ready to publish?" confirmation panel with a *second* Publish button inside it. That's what caused the "there is no publish button" confusion — the button was there, just not the final one.

## 2. A fourth thing was blocking the login-redirect step — found only by finishing the test properly

Once `/dashboard` was published, I retested. Saving Form 2 correctly redirected to `/dashboard` right away — that part was fully fixed by PR #4 as written.

But logging out and back in as `talent-p1-test` still landed on the homepage, not `/dashboard`. Rather than report a partial pass, I traced it: UM's own login handler (`includes/core/um-actions-login.php`, `um_user_login()`) only calls the `um_login_redirect_url` filter — the one `recrewt_um_login_redirect()` hooks into — when the logging-in user's role has its **"Action to be taken after login"** setting set to **"Redirect to URL."** Talent's role setting was on the default, **"Redirect to profile,"** which redirects straight to the user's own profile page via a completely different code path and never touches the filter at all. `recrewt_um_login_redirect()` was correct code sitting behind a setting that had never been switched on for it to reach.

Separately, I also confirmed this only applies when logging in through **UM's own Login page** (`/reliant/?page_id=53`) — not WordPress's native `/wp-login.php`, which doesn't route through this UM code path at all. All of today's earlier login-redirect testing had been done via `/wp-login.php`, which explains why the feature never appeared to take effect even before the role-setting issue.

Since changing "Action to be taken after login" affects every Talent user's login destination site-wide, I flagged it and asked before changing it rather than just doing it. You said yes; I changed Talent's setting from "Redirect to profile" to "Redirect to URL," confirmed the save via a fresh reload, then retested.

## 3. Verification checklist — final results

1. **Save Form 2 as `talent-p1-test`** — done.
2. **Redirect target is `/dashboard`** — **confirmed.** Landed on `page_id=452`, title "Dashboard – Recrewt."
3. **`rc_profile_setup_complete = 1` in user meta directly** — confirmed in the prior report (before the page existed); the meta write happens independently of the redirect target and was already verified.
4. **Log out, log in as `talent-p1-test`, confirm login redirect to `/dashboard`** — **confirmed**, but only after logging in via UM's own Login page (`/reliant/?page_id=53`) with Talent's "Action to be taken after login" set to "Redirect to URL." Landed on `page_id=452`, same as above.
5. **No merges beyond `dev`** — PR #4 merged into `dev` only, per instruction (see below).

## 4. PR #4 merged

`gh pr merge 4 --merge` — merge commit `cf400763f734a9ca75f489a507dfa14f74f80a50`, merged 2026-09-10T09:19:59Z.

**Confirmed `dev`'s `functions.php` against staging** — functionally identical: same hook (`um_after_user_updated`), same `get_userdata()` role lookups in both functions, same `um_is_core_page( 'user' )` fix, no trace of any of the three original bugs in actual code (only in explanatory comments describing what was wrong, which is intentional).

One thing to flag honestly rather than gloss over: two comment lines differ by punctuation only — `dev` has an em dash ("—"), staging has a plain double-hyphen ("--"), from a step where I swapped that character while troubleshooting an unrelated save-persistence issue in the Theme Editor and never swapped it back before the final successful deploy. Zero functional difference — both are inside `/** */` doc comments, nothing executes differently. I didn't redeploy to fix this cosmetic mismatch, since staging is currently confirmed working end-to-end and touching the file again for a punctuation-only change felt like unnecessary risk given how flaky that particular save action has been today. Flagging it for the record rather than deciding it doesn't matter.

## What's fully done now

- PR #2, #3, #4 all merged to `dev`.
- `ajax-handlers.php` and `functions.php` both deployed to staging and confirmed matching `dev` (functionally).
- Redirect-collision bug (the original P1 ask) — root-caused correctly this time, fixed, and verified end-to-end: save → `/dashboard`, login → `/dashboard`, meta → `1`.
- DOB field range fixed on staging (UM Fields Manager, not code).
- `recrewt_sanitise_bio_on_save()`'s wrong-first-arg bug — noted, bundled into Sprint 2 per your instruction, not touched.

Sprint 2 is unblocked from my side whenever you're ready to kick it off.
