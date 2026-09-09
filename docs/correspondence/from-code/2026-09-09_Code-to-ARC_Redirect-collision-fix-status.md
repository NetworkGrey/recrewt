# From: Code
# To: ARC / Gustav
# Topic: Redirect-collision fix — code done and PR'd, DOB range fixed on staging, but a correction is needed before I can call this verified

## TL;DR

- **DOB min/max fix: done and confirmed on staging.** Form 2's `date_of_birth` field now accepts 100 years before today to today (was ±25 years symmetric around today). Confirmed field-config only, no eligibility logic anywhere touches it.
- **Redirect-collision code fix: written exactly as specified, committed, PR open — not yet merged.** [NetworkGrey/recrewt#4](https://github.com/NetworkGrey/recrewt/pull/4)
- **I cannot complete the verification checklist yet, and I need to correct something I told you last time.** While preparing to deploy the fix to staging for testing, I discovered `theme/hello-elementor-child/functions.php` — the file containing the entire redirect-collision code, on both sides — **has never been deployed to staging at all.** Not the buggy two-function version, not anything. Staging is running the theme's stock auto-generated file with zero `recrewt_` functions in it.
- **This means my last report's claim "root cause confirmed in code (not inferred)" was wrong.** I read the repo file and assumed — incorrectly, without checking — that it matched staging, the way `um-hooks.php` did. It didn't. I'm sorry for the bad information; here's what I now know and what I think actually happened, plus what I need from you before I finish this.

---

## 1. DOB range fix — done

Ultimate Member → Talent Basic Profile form → Date of birth field → Date Range settings:
- Changed `_years_x` (before/after mode) from `equal` to `past`
- Changed `_years` (year count) from `50` to `100`

Result: min = 100 years before today, max = today, past-only. Confirmed via a fresh reload of the form editor (not stale state) — both values persisted.

Confirmed before making this change: nothing in the codebase gates on `date_of_birth` for eligibility. The only consumer is `recrewt_age_range_from_dob()` in `functions.php`, which just formats a display bracket (e.g. "25-30") — no age check, no rejection logic, anywhere. Safe as a pure field-config change.

## 2. Redirect-collision code fix — written and PR'd

`theme/hello-elementor-child/functions.php`: deleted `recrewt_um_after_register_redirect()` and `recrewt_mark_profile_setup_complete()` in full, including both `add_action()` lines and the stale hook-version comment. Replaced with exactly the single function you specified:

```php
function recrewt_um_profile_setup_done( $user_id ) {
    if ( um_user( $user_id, 'role' ) === 'talent' ) {
        update_user_meta( $user_id, 'rc_profile_setup_complete', 1 );
        $dashboard = get_permalink( get_page_by_path( 'dashboard' ) );
        if ( $dashboard ) {
            exit( wp_redirect( esc_url( $dashboard ) ) );
        }
    }
}
add_action( 'um_after_user_account_updated', 'recrewt_um_profile_setup_done' );
```

Did not touch `recrewt_um_login_redirect()` or `recrewt_age_range_from_dob()`, per instruction. Branch `fix/redirect-collision-merge` off `dev`, pushed, PR open against `dev`: **[NetworkGrey/recrewt#4](https://github.com/NetworkGrey/recrewt/pull/4)**. Not merged.

## 3. What I found when I went to deploy it for testing

To run your verification checklist (steps 1-5) I needed the fix live on staging, same as I did for `um-hooks.php` during the P0 emergency. Before pasting the new function in, I checked what was currently there — and staging's `functions.php` for the Hello Elementor Child theme is **the stock Child Theme Configurator boilerplate**: `chld_thm_cfg_locale_css()`, `child_theme_configurator_css()`, nothing else. 973 characters. Zero occurrences of `recrewt_`.

I checked two other recently-touched files to see how isolated this was:

| File | Repo (`dev`) state | Staging state | Match? |
|---|---|---|---|
| `plugins/recrewt-core/includes/um-hooks.php` | 2-arg fixed signature (PR #2) | 2-arg fixed signature | ✅ matches |
| `plugins/recrewt-core/includes/ajax-handlers.php` | `talent_gender`/`talent_languages` (PR #3) | still `gender`/`languages` | ❌ does not match |
| `theme/hello-elementor-child/functions.php` | full redirect/login-redirect/utility code | stock boilerplate only | ❌ does not match at all |

The difference: `um-hooks.php` is the one file I personally hand-patched into staging during the P0 fatal-error emergency, outside of any git merge — merging a PR into `dev` has never, on its own, updated staging. I did that patch step for `um-hooks.php` and evidently never did it for `ajax-handlers.php` (PR #3) or for the theme's `functions.php` (which as far as I can tell was never live on staging even before this — I have no record of ever deploying it, going back to the original Sprint 1 build).

## 4. So what actually caused the redirect you originally asked me to test?

Not a collision between two functions — neither function was running. I went back and checked Form 2's own settings screen in full for any native "redirect after save" option, since that's the only other thing that could explain it. Ultimate Member's Profile-type forms have exactly one redirect-related setting — **"Redirection after Login"** — which only overrides post-*login* behavior and was set to "Default" (i.e., inactive). There is no native "redirect after save" setting on a Profile form at all.

With no custom code and no native setting active, UM's default behavior after a successful edit-mode save is simply to reload the same profile page — which is exactly the `page_id=450` redirect I reported. Not a bug interaction; just the complete absence of the redirect feature Sprint 1's spec called for (`field-spec-v1.md`'s "Post-save redirect: /dashboard" line was always describing intended behavior to be built via this custom hook, not a UM setting to configure).

Net effect on the actual question you asked me to answer: **the redirect-collision "bug" I confirmed last time wasn't real in the form I described it** — but the underlying need (a working post-save redirect to `/dashboard`, and a working `rc_profile_setup_complete` flag) genuinely doesn't exist on staging yet either way. The fix you and Gustav specified is still the right one — it just hasn't been tested against real behavior yet, because the "before" state I tested against was never what I thought it was.

## 5. What I need from you before I can finish the verification checklist

Deploying the merged function to staging now means deploying the *entire* `functions.php` file for the first time — not swapping a fix into running code. That file also contains `recrewt_um_login_redirect()` (role-based login redirect for talent/casting_pro/production/admin — currently inactive on staging) and conditional JS enqueues for the profile pages. Turning all of that on for the first time is a bigger, site-wide behavior change than "merge two functions," and it's not something I want to flip live without you knowing that's what deploying this file actually does right now.

I'd like your go-ahead on one of:
1. **Deploy the whole file now** (it's the version already in the PR — nothing extra, nothing untested-by-you) and run the full verification checklist, including the login-redirect retest in step 5, or
2. You review/adjust first, then I deploy, or
3. Something else you'd rather sequence differently, given ajax-handlers.php has the same gap and may be worth bundling into one deployment pass.

I also haven't independently confirmed `rc_profile_setup_complete` in raw user meta via any method other than inference from page behavior — once the file's live I'll need a way to check it directly as you asked; happy to hear if you have a preferred method (I don't have DB/SSH access, only wp-admin).

## What's confirmed vs. not yet

- ✅ DOB range — fixed and confirmed on staging.
- ✅ Redirect-collision code — written exactly to spec, PR #4 open.
- ✅ Root cause of the *original* observed symptom — now correctly understood (no native UM redirect setting + no custom code active, not a function collision).
- ❌ Verification checklist (steps 1-5) — not run. Blocked on the staging-deployment question above.
- ⚠️ Correction: my prior report's "confirmed in code, not inferred" line for the redirect collision was inaccurate — flagging it here rather than letting it stand uncorrected.
