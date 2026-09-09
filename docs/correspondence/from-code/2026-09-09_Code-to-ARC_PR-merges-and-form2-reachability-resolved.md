# From: Code
# To: ARC
# Topic: PR #2 + #3 merged, Form 2 reachability fixed, redirect-collision test run — collision confirmed real

## TL;DR

1. **PR #2 and PR #3 both merged** into `dev`. Confirmed `um-hooks.php` on `dev` has the correct 2-argument `recrewt_um_can_view_field()` signature.
2. **Your "Profile Form assignment" hypothesis did not hold** — the Talent role edit screen has no such field (full list below). The actual blocker was a basic role capability: **"Can edit their profile?" was unchecked for the Talent role**, which meant UM offered no edit control anywhere, on any form, for any Talent user. You found and fixed this yourself in the browser session — I verified it saved, then re-tested reachability.
3. **Form 2 is now reachable and editable.** With the capability fixed, `/profile-setup/?um_action=edit` (page_id=450) renders Form 2 (`form_id=449`) as a real editable form with all its configured fields.
4. **Ran the original redirect-collision test. The collision is real and confirmed, not just hypothetical.** Saving Form 2 as `talent-p1-test` redirected back to `/profile-setup` (page_id=450) instead of the form's configured `/dashboard` redirect. Root cause found in `theme/hello-elementor-child/functions.php`: `um_after_user_account_updated` fires on *every* profile save, not just registration, and the registration-redirect handler doesn't distinguish the two cases — it unconditionally resets `rc_profile_setup_complete` to `0` and `exit()`s on a redirect before the "mark complete" handler (hooked at a later priority on the same event) ever runs. Net effect: `rc_profile_setup_complete` can never actually become `1` through this code path. Details and exact lines below. **Not fixed — reporting per your sequencing instruction, awaiting your call.**

---

## 1. PR merges

- PR #2 (`fix/can-view-field-fatal` → `dev`): merged, commit `773fe6786200ca9558fef2dba38dfe273391fee2`.
- PR #3 (`fix/gender-languages-key-lookup` → `dev`): merged, commit `856642af987a5accd6fdd167f5d2289710397dfd`.
- Verified on `dev` post-merge:
  ```
  32:function recrewt_um_can_view_field( $can_view, $data ) {
  59:add_filter( 'um_can_view_field', 'recrewt_um_can_view_field', 10, 2 );
  ```
  Correct 2-arg signature confirmed in the repo, not just on staging.

## 2. Your hypothesis, checked

Full field/section list on Users → User Roles → Talent (`admin.php?page=um_roles&tab=edit&id=talent`), as requested if the hypothesis didn't pan out:

- **Publish**: Role Priority
- **Administrative Permissions**: Can access wp-admin?, Force hiding adminbar in frontend?, Can edit other member accounts?, Can delete other member accounts?
- **General Permissions**: Can edit their profile?, Can delete their account?
- **Profile Access**: Can view other member profiles?, Can make their profile private?, Can view/access private profiles?, Avoid indexing profile by search engines
- **Homepage Options**: Can view default homepage?
- **Registration Options**: Registration Status, Action to be taken after registration, Personalize the custom message, Login user after validating the activation link?, URL redirect after email activation
- **Login Options**: Action to be taken after login
- **Logout Options**: Action to be taken after logout
- **Delete Options**: Action to be taken after account is deleted

No "Profile Form" assignment field anywhere on this screen. Confirmed against the actual UM install, not docs.

## 3. What was actually blocking Form 2 — found via the role's own General Permissions section

Checked the three checkboxes that looked most relevant. Before any change:

```
Can edit other member accounts?  → false
Can edit their profile?          → false   ← this one
Can view other member profiles?  → false
```

**"Can edit their profile?" was unchecked for the entire Talent role.** That's a basic capability gate — with it off, UM has no reason to ever render an edit control for a Talent user, on *any* profile page, regardless of which form is assigned to it. This explains everything I found in the earlier investigation: no edit link on the canonical User page, no edit link on `/profile-setup` — it wasn't a form-routing problem at all, it was a permissions problem underneath the routing question.

You checked "Can edit their profile?" and "Can delete their account?" yourself in the shared browser session and clicked Update Role. I verified via a fresh page load (not stale JS state) that both saved:

```json
{"role__um_can_edit_profile": true, "role__um_can_delete_profile": true}
```

## 4. Form 2 reachability — confirmed fixed

Logged in as `talent-p1-test` (session already active, admin bar hidden by role config — confirmed via the `logged-in` body class rather than the admin bar, since Talent role hides it).

- `?page_id=52&um_action=edit` (canonical User page): now renders an *editable* form, but it's still `form_id=8` (stock Default Profile — cover photo, profile photo, bio only). Not Form 2. This appears to be expected/unchanged — the canonical page was never wired to Form 2, only `/profile-setup` was.
- `?page_id=450&um_action=edit` (`/profile-setup`): now renders `form_id=449` — **Form 2** — with all its real fields as live inputs: `full_name-449`, `stage_name-449`, `talent_categories[]`, `city-449`, `province`, `talent_languages[]`, `date_of_birth-449`, `talent_gender`, `height_cm-449`, `bio_short`. Matches the spec in `field-spec-v1.md` exactly.

Form 2 is reachable and editable by a Talent user. Reachability question closed.

**Side finding, worth a look separately:** the `date_of_birth-449` input's `min` attribute is `2001-09-09` (25 years before today) and `max` is `2051-09-09`. That range rejects any real birth date for an adult talent user — I had to use `2005-01-01` as a test value just to get past validation. Looks like a UM date-field range misconfiguration (probably set as "N years from today" rather than "N years before today"). Not touched, flagging for your sequencing call.

## 5. Redirect-collision test — run, collision confirmed

Filled Form 2 for `talent-p1-test` (full name, Actor category, Cape Town, Western Cape, English, DOB, Woman, bio) and submitted.

- First attempt blocked by UM's own required-field validation: **"Profile Photo is required."** — I can't do file uploads through this browser tool, so I asked you to upload one directly in the tab; you did, and I confirmed the file was staged (`profile_photo` input showed the uploaded filename) before resubmitting.
- Second submit succeeded. Data saved correctly (verified via the resulting view page — all fields present and correct).
- **Redirect target after save: `https://recrewt.app/reliant/?page_id=450`** — i.e., back to the profile-setup page itself, in view mode. **Not** `/dashboard`, which is what Form 2's own "post-save redirect" setting specifies.

### Root cause, confirmed in code (not inferred)

`theme/hello-elementor-child/functions.php`, lines 83–158:

```php
function recrewt_um_after_register_redirect( $user_id ) {
    if ( um_user( $user_id, 'role' ) === 'talent' ) {
        update_user_meta( $user_id, 'rc_profile_setup_complete', 0 );
        $setup_page = get_permalink( get_page_by_path( 'profile-setup' ) );
        if ( $setup_page ) {
            exit( wp_redirect( esc_url( $setup_page ) ) );
        }
    }
}
add_action( 'um_after_user_account_updated', 'recrewt_um_after_register_redirect' );
```
```php
function recrewt_mark_profile_setup_complete( $user_id ) {
    if ( um_user( $user_id, 'role' ) === 'talent' ) {
        update_user_meta( $user_id, 'rc_profile_setup_complete', 1 );
    }
}
add_action( 'um_after_user_account_updated', 'recrewt_mark_profile_setup_complete', 20 );
```

Both are hooked to the same event: **`um_after_user_account_updated`**. UM fires this on *every* profile save, not only at registration completion — there's no built-in distinction between "just registered" and "editing an existing profile" on this hook. The first handler (priority 10, default) doesn't check whether this is a first-time save; it unconditionally:
1. Resets `rc_profile_setup_complete` to `0`, then
2. Calls `exit( wp_redirect(...) )` — which terminates the PHP request immediately.

Because of that `exit()`, the second handler (priority 20, meant to set the flag to `1` after a successful save) **never runs** — not on registration, and not on any later save either. So `rc_profile_setup_complete` can never actually reach `1` through this code path. Every Form 2 save, first time or not, gets redirected back to `/profile-setup`, and the flag stays permanently stuck at `0`.

This also means the role-based login redirect (`recrewt_um_login_redirect()`, same file, lines 115–140) is affected downstream: since `rc_profile_setup_complete` never becomes `1`, a Talent user would be sent back to `/profile-setup` on every login too, never reaching `/dashboard` — even after genuinely completing their profile.

The theme file's own comment at line 99 already half-flags this: *"Note: UM uses 'um_registration_complete' for registration, not account update. ... If the above does not fire, try 'um_registration_complete'."* That note anticipated the wrong-hook risk but for the opposite symptom (hook not firing at all); what's actually happening is closer to the reverse — the hook fires correctly, just on more events than intended.

I have not touched this file. Per your sequencing instruction and my role, this needs your call on the fix approach — a few options exist (e.g., switching the registration redirect to `um_registration_complete`, or having `recrewt_um_after_register_redirect` check `rc_profile_setup_complete`'s current value before treating the save as "first time"), but I'd rather implement exactly what you specify than guess at which one fits your intended flow.

## What's confirmed vs. still open

- ✅ Form 2 reachability — fixed (role capability, not form assignment).
- ✅ Redirect-collision question — answered. It's real: `um_after_user_account_updated` conflates "first registration" and "later profile update," and the `exit()` on the first handler permanently blocks the flag from ever being set to `1`.
- ⏳ Awaiting your decision on the fix for the redirect-collision bug before I touch `functions.php`.
- ⏳ DOB field's min/max date range (2001–2051) — separate, smaller issue, flagged above, not yet actioned.

Test account (`talent-p1-test`) and `/profile-setup` page remain in place with a saved (if not fully "complete" per the broken flag) Form 2 profile, ready for retesting once a fix is instructed.
