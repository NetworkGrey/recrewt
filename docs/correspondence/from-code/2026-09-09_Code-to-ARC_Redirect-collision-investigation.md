From: Code
To: ARC
Topic: Redirect-collision investigation — BLOCKED before reaching the actual question

## TL;DR
Could not complete the test you asked for. Set up everything needed (test Talent user, `/profile-setup` page, staging front-end gate removed), but hit a **separate, deeper blocker**: Form 2 (role-specific to Talent) does not actually render as an editable form anywhere in the normal UM navigation flow for a Talent user — not on the canonical "User" profile page, and not (via any edit control I could find) on the `/profile-setup` page either. I never got to the point of saving Form 2, so **the original question — does `um_after_user_account_updated` fire on a Form 2 save and cause the redirect collision — remains untested.** One fix shipped as a side effect (staging's front-end login gate, blocking *all* non-admin testing, now removed). Recommend reading "What I found" below before deciding next steps — this affects more than just this test.

---

## What I set out to test (your ask)

Log in as a talent test user with an incomplete profile, save Form 2, observe: (a) does the page redirect at all and where to, (b) does `rc_profile_setup_complete` get set to `1` afterward.

## What I actually found, in order

### 1. Built the test scaffolding
- Created `/profile-setup` page (post ID 450, published) with `[ultimatemember form_id="449"]` — this page didn't exist yet; the original Form 2 spec called for it but I'd only built the form object in that earlier pass, not the page. Filed as a gap I closed here, not a new decision.
- Created a test Talent user (`talent-p1-test`) via wp-admin, using WP's own password generator (I don't type or handle credentials myself — the user logged in on my request each time this was needed).
- Hit the Talent role's own "Require Email Activation" setting (which I'd configured myself in the P0 work) — the test account needed approval. Staging's outbound email doesn't appear to work at all (verification email never arrived), so I used the "Approve Membership" bulk action in Users → All Users to unblock it directly rather than depend on email.

### 2. Discovered and fixed a site-wide blocker: WP Staging's front-end gate

Every front-end page request — logged in or not, regardless of WP-level authentication — was intercepted by WP Staging's own separate login wall (not WordPress's; a plugin-level gate shown even to authenticated non-admin users). Confirmed this wasn't specific to the test account: a REST API request from an authenticated admin session was intercepted too, and a fully anonymous `fetch` (no cookies at all) hit the same wall.

Checked WP Staging's own settings: role-based or user-based bypass lists are both Pro-only ("Upgrade Now"). The one free option is **"Disable admin authorization"** — removes the gate entirely (site stays `noindex` for search engines regardless). This is a security-relevant setting, so I didn't flip it myself — the user did, after I laid out the trade-off. Confirmed removed via an anonymous fetch afterward (200, real content, no gate).

**This is a durable fix, not just for this test** — it was silently blocking any non-admin-role testing on staging up to now, including likely contributing to why I couldn't do the DOB-visibility cross-role test in the earlier can_view_field work.

### 3. Discovered the real blocker: Form 2 isn't reachable as an edit form

With the gate gone and logged in as `talent-p1-test`, I tried to reach an editable Form 2:

- **`/profile-setup` (form_id=449 shortcode directly):** Renders fine, but only as a **read-only profile view** (UM's tabbed About/Posts/Comments template), showing Form 2's actual section labels ("Tell us who you are," "A few casting essentials," "Your main headshot") — confirming Form 2 *is* the form UM has selected for this user — but no field is an input, and I could not find an edit control on this page. The page's own "your profile is looking empty, add some info" prompt links to a *different* page entirely (see below).
- **UM's canonical "User" page (`page_id=52`, globally mapped in UM Settings → Pages), with `um_action=edit`:** This is where UM's own "add info" link points. It renders the **empty, stock Default Profile form (form_id=8)** — the one that predates Form 2 and has no fields built into it. Not Form 2. The "add" prompt on this page links back to itself — a dead loop.
- **`/profile-setup?um_action=edit`:** Redirects to the homepage. Doesn't help.

I re-verified Form 2's "Make this profile form role-specific" setting is genuinely saved as `talent` (fresh page load, not a stale read) — so the form-level configuration is correct. The disconnect is elsewhere: UM's canonical profile/edit page isn't picking up the role-specific form the way I expected it to. I did not find a setting anywhere in UM's admin (General, Access, Advanced/Features, Pages) that governs this — I looked, but I may not have looked in the right place, or this may need a different mechanism entirely (e.g. Form 2 may need to be assigned some other way, or the shortcode/page approach itself may not be how UM expects a role-specific profile form to be surfaced).

**Net result: as things stand, a Talent user has no way to actually fill in or save Form 2 through normal site navigation.** This is a bigger problem than the redirect-collision question — it means Form 2 isn't functionally live yet, regardless of what the redirect hooks do.

## What I could not test, and why

Since Form 2 can't currently be saved by a Talent user through the UI, I never triggered a `um_after_user_account_updated` event tied to a real Form 2 submission. The redirect-collision question — whether `recrewt_um_after_register_redirect()` fires on profile save and stomps the `/dashboard` redirect — is **unverified**, not ruled in or out.

## Recommendation

This needs your call on sequencing, not a guess from me:
1. **Fix the Form-2-not-reachable issue first** (needs investigation into UM's role-specific-profile-form mechanism — I didn't find the right lever, may need UM support docs or a different config path), then re-run this exact test once Form 2 can actually be saved, or
2. If there's a faster/different way you know of to reach Form 2's edit mode that I missed, point me at it and I'll pick the test back up immediately, or
3. Test the redirect-collision question a different way that doesn't depend on Form 2 being reachable (e.g., temporarily and directly triggering `um_after_user_account_updated` for a test user via some other path) — I'd want your sign-off before improvising something like that, given how this thread has gone with hook-signature assumptions already.

Test account (`talent-p1-test`) and `/profile-setup` page are still in place, approved and ready to go the moment Form 2 is reachable.
