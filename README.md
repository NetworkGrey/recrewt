# reCREWt

AI-assisted talent sourcing and casting management platform for the African entertainment industry.

**Live site:** https://recrewt.com (update when domain is confirmed)
**Staging:** https://staging.recrewt.com (update when staging is set up)
**WP admin:** https://recrewt.com/wp-admin

---

## What this repo is

This repo tracks all **custom code** for the reCREWt WordPress build. It does not contain:

- WordPress core files
- Plugin files (Ultimate Member, Elementor Pro, WP Job Manager)
- The WP database or any user data
- Media uploads
- Elementor page layouts (these live in the database, not files)

If you are looking for page layout changes, those are made in the Elementor editor inside WP admin, not here.

---

## Repo structure

```
recrewt/
├── README.md                          ← you are here
├── .gitignore                         ← excludes vendor, node_modules, uploads
│
├── docs/                              ← project documentation
│   ├── field-spec-v1.md               ← talent profile field specification (Sprint 1)
│   ├── sprint-log.md                  ← decisions made, options considered, outcomes
│   └── architecture.md                ← stack overview, plugin roles, data flow
│
├── theme/
│   └── hello-elementor-child/         ← child theme (mirrors /wp-content/themes/hello-elementor-child/)
│       ├── style.css                  ← theme overrides and custom CSS
│       ├── functions.php              ← hooks, enqueues, redirects, UM config
│       └── js/
│           ├── recrewt-swipe.js       ← swipe/discovery feature (Sprint 3)
│           └── recrewt-profile.js     ← profile page interactions (Sprint 2)
│
└── plugins/
    └── recrewt-core/                  ← custom plugin for backend logic
        ├── recrewt-core.php           ← plugin bootstrap, constants, autoload
        ├── includes/
        │   ├── ajax-handlers.php      ← WP AJAX endpoints (swipe saves, favourites)
        │   ├── um-hooks.php           ← Ultimate Member filter and action hooks
        │   └── roles.php              ← custom role registration and capabilities
        └── README.md                  ← plugin-specific notes
```

---

## Team

| Person | Role | Access |
|---|---|---|
| Gustav | Lead engineer + architect | Repo owner, WP admin |
| Elouise | Web designer | Repo collaborator (read + push to dev), WP admin |

---

## Branch strategy

| Branch | Purpose | Who touches it |
|---|---|---|
| `main` | Stable, tested code only | Merge from dev after review |
| `dev` | Active development | Gustav + Elouise |

**Rules:**
- Never commit directly to `main`
- Every merge to `main` must be tested on staging first
- Commit messages use present tense, sentence case: `Add swipe gesture handler`, `Fix UM redirect on email confirm`

---

## Local setup (for Gustav)

This repo is not a self-contained WP install. The files here are deployed into an existing WP installation. To work locally:

1. Set up a local WP environment (LocalWP is recommended — free, runs on Mac and Windows)
2. Clone this repo
3. Symlink or copy `theme/hello-elementor-child/` into your local `/wp-content/themes/`
4. Symlink or copy `plugins/recrewt-core/` into your local `/wp-content/plugins/`
5. Activate the child theme and the recrewt-core plugin in WP admin
6. Import a DB snapshot from staging (do not work against live DB)

For Elouise (designer, no local setup needed):
- Edit child theme files directly in this repo
- Push to `dev`
- Gustav deploys to staging for review

---

## Deployment

There is no automated CI/CD pipeline at this stage. Deployment is manual:

1. Merge `dev` into `main` after staging review
2. Connect to the live server via SFTP (credentials in the shared password manager — do not commit credentials here)
3. Upload changed files from `theme/` and `plugins/` to the corresponding paths in `/wp-content/`
4. Test on live immediately after deploy

**SFTP paths on live server:**
- Child theme: `/wp-content/themes/hello-elementor-child/`
- Custom plugin: `/wp-content/plugins/recrewt-core/`

---

## Sprint overview

| Sprint | Scope | Status |
|---|---|---|
| Sprint 1 | Registration form + basic talent profile form | In progress |
| Sprint 2 | Extended profile fields + directory page styling | Not started |
| Sprint 3 | Swipe / discovery feature + favourites | Not started |
| Sprint 4 | Casting calls (WP Job Manager) | Not started |
| Sprint 5 | Messaging + notifications | Not started |
| Sprint 6 | AI matching (phase 2 scope) | Not started |

Full sprint details and decisions: see `docs/sprint-log.md`

---

## Key plugins in use

| Plugin | Version | Role |
|---|---|---|
| Elementor | 4.0.1 | Page builder and UI layer |
| Elementor Pro | 3.29.2 | Advanced widgets, theme builder, nav |
| Ultimate Member | see WP admin | Registration, profiles, roles, directory |
| WP Job Manager | see WP admin | Casting calls (Sprint 4) |

Do not update plugins without testing on staging first. Elementor and Ultimate Member updates have historically broken custom field configurations.

---

## Conventions

- **CSS class prefix:** `rc-` for all custom classes (e.g. `rc-swipe-card`, `rc-profile-header`)
- **Meta key prefix:** no prefix (UM stores meta directly, see field spec for exact keys)
- **JS function prefix:** `rc` in camelCase (e.g. `rcInitSwipe`, `rcSaveFavourite`)
- **PHP function prefix:** `recrewt_` (e.g. `recrewt_register_roles`)
- **Hook prefix:** `recrewt/` for custom actions and filters

---

## Security notes

- Do not commit API keys, database credentials, or SFTP passwords to this repo
- Use a shared password manager (e.g. 1Password or Bitwarden) for credentials
- The `.gitignore` excludes `.env` files — use `.env.example` with placeholder values if environment config is needed
- POPIA compliance notes: see `docs/field-spec-v1.md`
