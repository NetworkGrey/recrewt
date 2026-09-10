# From: Code
# To: ARC / Gustav
# Topic: Deployment + full verification — done, with three more bugs found and fixed, plus one real blocker left (missing page, not code)

## TL;DR

- **Both deployments: done.** `ajax-handlers.php` (PR #3 content) and `functions.php` (PR #4 content) are both live on staging, confirmed via fresh page loads.
- **Deploying `functions.php` caused a brief live outage** (a pre-existing bug the deploy exposed, not something in PR #4's own diff) — found, root-caused, fixed, and the site was back up within the same session. Full account below.
- **Two further bugs, beyond the outage, were blocking the actual feature from ever working** — found only because I ran the verification checklist all the way through instead of stopping at "no fatal error." Both fixed, both confirmed against UM's actual source before fixing.
- **`rc_profile_setup_complete` is now confirmed set to `1` directly in user meta** after a Talent user saves Form 2 — checked directly, not inferred.
- **Redirect to `/dashboard` still doesn't happen — but the reason is that no `/dashboard` page exists anywhere on this site.** This is the one item I could not close out, and it needs your call, not a guess from me.
- Login-redirect retest: confirmed the code now runs correctly and falls through to default behavior (no error) for the same missing-page reason.
- PR #4 updated with everything below. Not merged beyond `dev`, per instruction.

---

## 1. Both deployments confirmed live

- `plugins/recrewt-core/includes/ajax-handlers.php` — deployed via Plugin Editor. Confirmed on a fresh reload: `talent_gender` / `talent_languages`, no trace of the old `gender` / `languages` keys.
- `theme/hello-elementor-child/functions.php` — deployed via Theme Editor. Confirmed `recrewt_um_profile_setup_done()` present, both old functions gone. (This file had never been deployed to staging before today, at all — confirmed in my last report.)

## 2. The outage: what happened, root cause, fix

Right after deploying `functions.php`, the front-end started returning 500 on every page (confirmed via network status, not just visual appearance). Root cause: `recrewt_enqueue_scripts()` calls `um_is_profile_page()`, which **does not exist** in Ultimate Member 2.13.0 (confirmed by grepping the actual installed UM plugin files — no such function anywhere). Since this runs on every `wp_enqueue_scripts` call, it fataled every front-end request the moment the file went live.

This is separate from PR #4's own diff — it was sitting dormant in the file since Sprint 1, never triggered because the file was never deployed until today. Not something introduced by the redirect-collision fix itself.

**Immediate response:** WordPress's Theme Editor safety check was refusing to accept any further save once the site was already fataling (a genuine catch-22 — it appears to test against the live site's current state before allowing a new save, so a broken site blocks the fix for itself). Worked around by temporarily activating the parent theme ("Hello Elementor"), which unloads the broken child `functions.php` without needing to save anything — confirmed the front-end came back up immediately. Fixed the actual bug in the file (replaced `um_is_profile_page()` with `um_is_core_page( 'user' )`, UM's real function for this, confirmed via source), redeployed, reactivated the child theme, confirmed the front-end stayed healthy.

Total site downtime: contained to this session, staging only, no live-site impact.

## 3. Two more bugs found while actually running the verification checklist

The outage fix alone wasn't enough — after redeploying, `rc_profile_setup_complete` still wouldn't get set when `talent-p1-test` saved Form 2. Rather than declare it "probably fine" once the fatal was gone, I kept going through your checklist and found two more real defects:

**Bug 2 — wrong hook entirely.** `um_after_user_account_updated` (what both the original buggy code and my first fix were hooked to) **only fires for UM's Account-settings form** — confirmed by reading `includes/core/class-account.php` (fires `um_submit_account_details` and friends, never this) versus `includes/core/um-actions-profile.php` (the actual Profile-form save handler, which fires `um_after_user_updated`). Form 2 is a Profile-type form, so the redirect code had never once fired for it, on any prior attempt, regardless of the merge-the-functions fix. Switched the hook to `um_after_user_updated`.

**Bug 3 — `um_user()` misuse.** Even after fixing the hook, the meta still wasn't setting. Added a temporary, admin-gated, staging-only debug snippet (plain `get_user_meta`/`wp_die`, nothing UM-specific, removed once done) to check directly rather than guess again, and found `um_user( 5, 'role' )` returns `false` for a real Talent user. Checked UM's source: `um_user()`'s actual signature is `um_user( $data, $attrs = null )` — **there is no user-ID parameter at all.** It only ever reads whichever user UM last "fetched" via `um_fetch_user()`. My code (in both `recrewt_um_profile_setup_done()` and `recrewt_um_login_redirect()`) was passing a user ID as the first argument, which silently returned false instead of erroring — so the `=== 'talent'` check never matched, for any user, regardless of the hook fix. Switched both functions to plain `get_userdata( $user_id )->roles`, which needs no UM fetch-context setup and is exercised directly by the same debug check that found the bug.

All three bugs (this session's outage fix plus these two) are in PR #4 now, each with its own commit and a code comment explaining what was wrong and how it was confirmed.

## 4. Verification checklist results

1. **Log in as `talent-p1-test`, save Form 2 again** — done, multiple times across the debugging above.
2. **Confirm redirect target is `/dashboard`** — **not achieved.** Lands on the profile-setup page instead. Root cause confirmed: **there is no page with slug or title "dashboard" anywhere on this site.** I checked the full Pages list (15 pages: Home, About, Contact, Jobs, Profiles, Profile Setup, Members, plus UM's own Login/Logout/Register/Account/User pages) — nothing named Dashboard. `get_page_by_path( 'dashboard' )` returns null, so the code's own `if ( $dashboard )` guard correctly declines to redirect rather than erroring. This is a missing-page gap, not a defect in the fix.
3. **Confirm `rc_profile_setup_complete = 1` in user meta directly** — **confirmed.** Used a temporary, admin-only, staging-only debug snippet added to `um-hooks.php` (not committed anywhere, not part of any PR) that read the meta via plain `get_user_meta()` and `wp_die()`'d the result. Read `rc_profile_setup_complete = '1'` for `talent-p1-test` (user ID 5) after the Bug 2 + Bug 3 fixes. Removed the snippet immediately after, confirmed removal via a fresh reload and confirmed the debug URL no longer responds.
4. **Log out, log back in — confirm `recrewt_um_login_redirect()` sends to `/dashboard`** — **not achieved, same missing-page reason.** Retested after the meta was confirmed `1`: login lands on the homepage (WP's own default fallback), because `get_permalink( get_page_by_path( 'dashboard' ) )` is false and the code correctly falls through to `$redirect_to` rather than erroring. Code is confirmed correct; there's just nowhere for it to send anyone yet.
5. **No merges beyond `dev`** — confirmed. Everything above lives only in commits on `fix/redirect-collision-merge`, pushed, PR #4 updated, not merged.

## 5. What's actually left

The redirect-collision fix and both newly-found bugs are all confirmed correct in isolation (the meta write proves the whole chain works up to the point of redirecting). The only remaining gap is that **`/dashboard` needs to exist** before steps 2 and 4 above can fully pass. This wasn't something I felt right deciding myself — whether it's a real new page Elouise needs to build, a placeholder, or an existing page (e.g. `/profile-setup` itself, post-setup) that should be reused under a different redirect target, changes what the actual fix looks like. Flagging for your call rather than guessing at scope.

## 6. Two things noticed in passing, not touched

- **`recrewt_sanitise_bio_on_save( $user_id )`** in `um-hooks.php` is hooked to `um_user_after_updating_profile` with the default single accepted arg — but that hook's real signature (confirmed in the same UM source read as Bug 2) is `do_action( 'um_user_after_updating_profile', $to_update, $user_id, $args )`, i.e. the first argument is `$to_update` (the changed-fields array), not `$user_id`. This function is very likely a silent no-op right now — `get_user_meta( $to_update, 'bio_short', true )` with an array where a user ID is expected. Same root cause class as Bug 3, pre-existing, unrelated to today's task, not fixed — flagging since I only found it by reading the same source file.
- **WordPress's Theme/Plugin Editor "Update File" silently failed to persist on 4 of the ~10 save attempts this session**, with no error message and no visible difference in the UI — the only way to tell was a genuinely fresh page navigation afterward. This cost real time today (see the outage-revert attempts in particular). Not something to fix, just flagging that every future staging deploy needs this same fresh-reload verification, every time, not just once.
- **The staging browser session is shared across all open tabs** (one cookie jar) — logging in as one user in any tab silently logs every other open tab in as that same user too. This caused several rounds of "wrong user logged in" confusion today. Not a bug, just an environment quirk worth remembering for any future multi-role testing.

## Cleanup confirmation

The temporary debug snippet added to `um-hooks.php` for step 3 above has been fully removed from staging and verified gone (fresh reload shows the file matches `dev` exactly; the debug URL no longer responds). Nothing temporary was left behind.
