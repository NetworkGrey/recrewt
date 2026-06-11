# reCREWt — Architecture overview

---

## Stack (POC phase)

| Layer | Technology | Notes |
|---|---|---|
| Hosting | WordPress (existing) | WP version: see WP admin |
| Page builder | Elementor Pro 3.29.2 | UI, layouts, theme builder |
| Theme | Hello Elementor (child) | All custom CSS and PHP hooks live in the child theme |
| Membership + profiles | Ultimate Member (Pro) | Registration, roles, profile forms, directory, file upload |
| Casting calls | WP Job Manager | Job listings mapped to casting calls |
| Custom logic | recrewt-core plugin | AJAX handlers, UM hooks, custom roles |
| Media (images) | WordPress media library | Standard WP upload for headshots and gallery |
| Media (video) | YouTube / Vimeo embed | URL-based for POC. Direct upload deferred. |
| Version control | GitHub (private repo) | Custom code only |
| Staging | TBD | LocalWP or subdomain — to be confirmed Sprint 1 |

---

## Future stack additions (post-POC)

| Layer | Technology | Trigger |
|---|---|---|
| Payments | PayFast or Stripe | Sprint 5 (subscriptions) |
| Video upload + CDN | Cloudflare R2 + transcoding worker | When URL embeds are insufficient |
| AI matching service | Node.js on Railway + Anthropic API | Sprint 6 |
| Vector search | pgvector or Pinecone | Sprint 6 |
| Push notifications | Pusher or Supabase Realtime | Sprint 5 |
| Native app | React Native or Flutter | Post-POC validation |

---

## User roles

| Role slug | Description | Key capabilities |
|---|---|---|
| `talent` | Actors, models, crew, extras | Create and edit own profile, apply to casting calls, use swipe discovery |
| `casting_pro` | Casting directors, agencies | Search talent, view full profiles (incl. DOB), create casting calls, shortlist |
| `production` | Production companies, directors | Create projects, post casting calls, view shortlists |
| `admin` | Platform operators | Full access, verify accounts, moderate, manage billing |

Roles are assigned by Ultimate Member on registration. The registration form (Sprint 1) assigns `talent` by default. Casting pro and production accounts will require a separate registration flow (Sprint 2 scope).

---

## Data flow — talent onboarding

```
Landing page (Freelance CTA)
        |
        v
/register  [UM registration form]
        |
        | assigns role: talent
        | sends verification email
        v
Email verification link
        |
        v
/profile-setup  [UM profile form — basic fields]
        |
        | saves to WP user meta
        v
/profiles/{username}  [public profile, live in directory]
        |
        v
/dashboard  [account page, completion prompt, quick actions]
```

---

## Data flow — swipe discovery (Sprint 3)

```
Casting pro / production user loads /discover
        |
        v
WP REST or AJAX query fetches talent profiles
(filtered by role = talent, paginated)
        |
        v
JS renders profile card stack in the DOM
        |
        v
User swipes right  ──────────────────────────────────────────┐
        |                                                     |
        v                                                     v
Swipe left: card dismissed,              AJAX POST to recrewt_save_favourite
next card loaded                         Saves talent user ID to
                                         favourites meta on casting pro's account
                                                             |
                                                             v
                                                   Visible in /favourites page
```

---

## Key WP meta keys (do not change once in production)

| Meta key | Stored on | Type | Description |
|---|---|---|---|
| `full_name` | User meta | string | Talent display name |
| `stage_name` | User meta | string | Optional stage name |
| `talent_categories` | User meta | array | Multi-select talent types |
| `city` | User meta | string | City of residence |
| `province` | User meta | string | Province / region |
| `languages` | User meta | array | Spoken languages |
| `date_of_birth` | User meta | date | Full DOB (restricted visibility) |
| `gender` | User meta | string | Gender identity |
| `height_cm` | User meta | int | Height in centimetres |
| `bio_short` | User meta | string | Short intro, max 300 chars |
| `rc_favourites` | User meta | array | Array of user IDs saved by casting pro |

---

## Plugin interaction map

```
Ultimate Member
    ├── manages: user registration, roles, profile forms, directory
    ├── stores: all talent profile data as WP user meta
    └── hooks into: recrewt-core (um-hooks.php) for custom redirects and visibility rules

WP Job Manager
    ├── manages: casting call listings (Sprint 4)
    └── maps to: productions, roles, application workflow

recrewt-core (custom plugin)
    ├── registers: custom AJAX endpoints
    ├── hooks into: UM actions for post-registration logic
    └── handles: swipe saves, favourites, role-based meta visibility

Elementor Pro
    ├── renders: all page layouts, profile cards, directory views
    └── does not: store any application data — display layer only
```

---

## Security considerations

- DOB field: UM role-conditional visibility. `talent` cannot see other users' DOB. `casting_pro` and `admin` can.
- ID documents (Sprint 2): stored as private WP media, not publicly accessible URLs. Admin-only visibility enforced via UM field permissions.
- POPIA: privacy policy linked from registration and profile setup forms. Consent captured at registration via checkbox.
- AJAX nonces: all custom AJAX endpoints in recrewt-core must use `wp_create_nonce` and `check_ajax_referer`. No exceptions.
- File uploads: UM handles headshot uploads. Extended gallery uploads must enforce file type and size limits server-side (not just client-side).
