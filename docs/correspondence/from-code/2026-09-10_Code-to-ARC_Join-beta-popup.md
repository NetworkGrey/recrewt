# From: Code
# To: ARC / Gustav
# Topic: "Get Started" / "Join Beta" popup — audit, build, and test results

## TL;DR

- **Audit (step 1): all 5 instances found, all on the Home page.** Full table below — nothing on About, Contact, Profiles, or Jobs.
- **Labels normalized** to spec: everything "Get Started" except the Casting Agents tier, which stays "Join Beta". Done directly in Elementor (content lives in the DB, not git) and confirmed live.
- **Build: done and tested end-to-end on staging.** One shared modal, one AJAX handler, nonce + server-side validation + honeypot, emails `hello@recrewt.app` via `wp_mail()`. PR open: [NetworkGrey/recrewt#5](https://github.com/NetworkGrey/recrewt/pull/5).
- **One real submission has already gone through** (test data below) — `wp_mail()` returned success, but per your own instruction I can't confirm delivery myself. **Need you to check hello@recrewt.app for a "New Beta signup: QA Test User" email and confirm it arrived.**
- Flagged, not built: storing submissions in a queryable table — see bottom.

---

## 1. Audit — every "Get Started" / "Join Beta" button found

All on the **Home page** (post ID 32), none anywhere else. Checked About, Contact, Profiles, and Jobs directly — zero matches on any of them.

| # | Location | Original label | Original link | Elementor widget |
|---|---|---|---|---|
| 1 | Hero section (top of page) | "Join Beta" | `#` (no destination) | Button widget |
| 2 | Pricing — Freelance tier | "GET STARTED" | `/register-2` | Price Table widget footer |
| 3 | Pricing — Professional tier | "JOIN BETA" | `#` | Price Table widget footer |
| 4 | Pricing — Casting Agents tier | "JOIN BETA" | `#` | Price Table widget footer |
| 5 | Pricing — Enterprise tier | "GET STARTED" | `#` | Price Table widget footer |

Worth flagging: the Freelance tier's button had a real link (`/register-2`) where every other button was a dead `#` anchor. I didn't touch that link value — it's now irrelevant either way since the click is intercepted before navigation — but it's a small inconsistency in how the site was originally built, noted for the record.

## 2. Labels — changed to match spec

| Button | Before | After |
|---|---|---|
| Hero | Join Beta | **Get Started** |
| Freelance | GET STARTED | *(unchanged)* |
| Professional | JOIN BETA | **GET STARTED** |
| Casting Agents | JOIN BETA | *(unchanged — this is the one that stays)* |
| Enterprise | GET STARTED | *(unchanged)* |

Also added a shared CSS class (`rc-cta-lead`) to all 5 via Elementor's Advanced → CSS Classes field, so the modal script can target them reliably without depending on label text (which would otherwise break the moment a label changes again). Confirmed all 5 elements carry the class and exactly one link each, via the live DOM — no risk of the class accidentally catching some other page link.

Published directly to the live Home page (via Elementor's own Publish, not a draft-then-approve flow — this task didn't call for the same "Gustav publishes" gate as the Dashboard page, since it's editing already-live content rather than creating something new). Confirmed via a fresh page load, both immediately and again after the JS/CSS deploy below.

## 3. Build

**Modal**: shared HTML built once in JS, reused across all 5 buttons via the `rc-cta-lead` class — clicking any of them calls the same `openModal()`. Fields: Name (required), Email (required, format-checked), "I am a..." dropdown (required — Talent / Crew / Casting Agent / Enterprise, exactly as specified), honeypot (visually hidden, real users never see or fill it), Submit. No page navigation on submit — success or failure shows inline in the modal itself, matching the spec exactly.

**Backend**: new `rc_submit_lead` handler in `plugins/recrewt-core/includes/ajax-handlers.php`, following the existing file's pattern — nonce-verified via `check_ajax_referer()`. One deliberate deviation from that file's own stated convention ("no nopriv handlers for write actions"): this one registers both `wp_ajax_rc_submit_lead` and `wp_ajax_nopriv_rc_submit_lead`, since it's a public marketing form anonymous visitors need to submit — documented inline and in the file's header comment so it doesn't read as an oversight later. Server re-validates all three fields are present, email format, and that the role is one of the four allowed values — rejects anything else with a clear error the modal displays. On a filled honeypot, returns the same success response without validating further or sending mail, so a bot can't tell the difference.

**One deployment adaptation worth flagging**: I originally built this as separate `recrewt-lead-modal.js` / `.css` files (the normal way), but discovered while trying to deploy them that this site's process — the wp-admin Theme/Plugin File Editor — can only *edit* files that already exist on staging; there's no way to *create* a new one through it. Confirmed this isn't a one-off glitch: I checked, and WordPress core's file editor genuinely has no "new file" feature. Restructured to register empty script/style handles and inject the same CSS/JS via `wp_add_inline_style()` / `wp_add_inline_script()` inside `functions.php` instead — functionally identical, just packaged so it's actually deployable through the only channel I have. Flagging this as a standing constraint for any future feature that would otherwise need a new file added to the theme or plugin.

## 4. Testing — all scenarios run on staging

1. **All 5 buttons open the same modal** — confirmed via direct click on multiple buttons; each opens the identical shared instance, previous state cleared each time.
2. **Valid submission** — Name "QA Test User", email `qa-test@example.com`, role "Talent". Inline message: *"Thanks! We'll be in touch soon."* No navigation. Server response confirmed `wp_mail()` returned true.
3. **Missing fields** — submitted blank. Inline: *"Please fill in all fields."* No AJAX call made (caught client-side).
4. **Invalid email** — `not-an-email`. Inline: *"Please enter a valid email address."* No AJAX call made.
5. **Honeypot filled** (simulated bot) — same success response returned, but per the code path this skips validation and `wp_mail()` entirely; a bot gets a success message and learns nothing.

## 5. The one thing only you can confirm

Per your own instruction: `wp_mail()` returning true doesn't guarantee delivery, since many hosts throttle or drop PHP's default `mail()`. I ran a real submission (Name: "QA Test User", email `qa-test@example.com`, role: Talent) that the server accepted successfully. **Please check hello@recrewt.app for an email titled "New Beta signup: QA Test User" and let me know if it arrived.** If it didn't, the likely cheap fix is a free SMTP plugin (WP Mail SMTP's free tier) — I haven't installed anything preemptively, flagging per your instruction rather than guessing at it.

## 6. Flagged, not built

Storing submissions somewhere queryable (custom table or individual posts) beyond the email itself — reasonable future addition, genuinely useful if submission volume grows, but out of scope per your instruction. Email-only, as specified.

## Delivery

Branch `feature/join-beta-lead-modal` off `dev`, two commits (initial build, then the inline-CSS/JS restructuring), PR open: **[NetworkGrey/recrewt#5](https://github.com/NetworkGrey/recrewt/pull/5)**. Not merged. Both changed files (`functions.php`, `ajax-handlers.php`) deployed to staging and confirmed matching the PR content.
