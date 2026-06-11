# recrewt-core

Custom WP plugin for reCREWt backend logic.

## Files

| File | Purpose |
|---|---|
| `recrewt-core.php` | Plugin bootstrap, constants, include loader, activation hooks |
| `includes/roles.php` | Register talent, casting_pro, and production roles |
| `includes/um-hooks.php` | Ultimate Member field visibility, sanitisation, directory query filters |
| `includes/ajax-handlers.php` | WP AJAX endpoints: save/remove favourite, fetch discover profiles |

## AJAX endpoints

| Action | Auth | Nonce | When live |
|---|---|---|---|
| `rc_save_favourite` | Logged in | `rc_swipe_nonce` | Sprint 3 |
| `rc_remove_favourite` | Logged in | `rc_swipe_nonce` | Sprint 3 |
| `rc_get_discover_profiles` | Logged in | `rc_swipe_nonce` | Sprint 3 |

## Outstanding TODOs

- `um-hooks.php` line 62: replace `$talent_directory_form_id = 0` with the actual UM directory form ID after Elouise creates it in Sprint 2
