From: Code
To: ARC
Topic: gender/languages key rename — cause confirmed, downstream fix + docs shipped

## TL;DR
Confirmed the exact UM error that forced the `gender`/`languages` → `talent_gender`/`talent_languages` rename (real UM constraint, not a workaround for something else). Fixed the stale `get_user_meta()` lookups in `ajax-handlers.php` and corrected both docs. PR open against `dev`, not merged: [NetworkGrey/recrewt#3](https://github.com/NetworkGrey/recrewt/pull/3). Nothing blocking.

---

## 1. Confirmed cause

When Form 2 was being built and a custom Checkbox field with meta key `languages` was submitted via UM's "Add a New Field" modal, UM rejected it with this exact error banner:

> "meta key is a predefined reserved key and cannot be used"

Same result for `gender`. This isn't UM guessing or a soft validation — it's a hard reject tied to UM's own predefined-field registry. I confirmed this by reading the actual list of predefined-field links in the Fields Manager modal (each carries a `data-arg1` attribute with its reserved key): UM ships built-in "Gender" (`key=gender`) and "Languages" (`key=languages`) fields as part of its Predefined Fields set, alongside things like `first_name`, `user_email`, `birth_date`, etc. Trying to create a *custom* field with either of those exact keys collides with UM's own reservation, regardless of field type (Select vs. Checkbox didn't matter — the key itself is what's blocked).

So: real UM constraint, confirmed against the actual rejection message and the actual reserved-key list, not an assumption.

## 2. Downstream fix applied

`plugins/recrewt-core/includes/ajax-handlers.php` — `recrewt_ajax_get_discover_profiles()`, lines 144 and 146:

```diff
-            'gender'            => get_user_meta( $user->ID, 'gender', true ),
+            'gender'            => get_user_meta( $user->ID, 'talent_gender', true ),
             'height_cm'         => (int) get_user_meta( $user->ID, 'height_cm', true ),
-            'languages'         => (array) get_user_meta( $user->ID, 'languages', true ),
+            'languages'         => (array) get_user_meta( $user->ID, 'talent_languages', true ),
```

Only the `get_user_meta()` lookup key changed. The array keys in the JSON response shape (`'gender'`, `'languages'`) are unchanged, per your instruction — the frontend contract doesn't shift, only where the value is actually read from.

## 3. Docs updated

- `docs/field-spec-v1.md` — line 62 (Languages) and line 82 (Gender) now show `talent_languages` / `talent_gender` as the meta key, each with an inline note explaining why it differs from the field label (UM reserves the plain name).
- `docs/architecture.md` — the "Key WP meta keys" table (~line 109, ~line 111) updated the same way, same inline reasoning so a future reader doesn't hit the same surprise.

## 4. Delivery

New branch `fix/gender-languages-key-lookup` off `dev`, single commit, pushed. PR opened against `dev`: **[NetworkGrey/recrewt#3](https://github.com/NetworkGrey/recrewt/pull/3)** — not merged, per standing convention (feature branch + PR + your go-ahead, same as the can_view_field fix).

## What's NOT done / out of scope here

Sprint 3's `rc_get_discover_profiles` isn't live yet (no real talent data exists to query against), so this fix is unverified against real data — noted as a test-plan item on the PR itself. Nothing else in `ajax-handlers.php` touches these two fields.
