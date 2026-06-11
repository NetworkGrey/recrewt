# reCREWt — Talent profile field specification
## Ultimate Member build reference (Sprint 1)
### Prepared for: Elouise (web designer) | Reviewed by: Gustav

---

## How to use this document

Each table covers one form in Ultimate Member. Build them in this order:

1. Registration form (new users)
2. Basic profile form (post-registration, guided setup)
3. Extended profile form (optional enrichment, deferred to Sprint 2)

Column definitions:
- **Field label** — the text the user sees
- **UM field type** — the field type to select in the UM form builder
- **Key / meta key** — the unique key UM stores this under (use exactly as written)
- **Required** — whether the field blocks form submission if empty
- **Options / notes** — allowed values, character limits, or build notes

---

## Form 1: Registration form

> UM admin path: Ultimate Member → Forms → Add New → select "Registration"
> Role to assign on registration: **Talent**
> Post-registration redirect: **/profile-setup** (the basic profile form page)
> Email verification: **On** (UM Settings → Email → Enable email activation)

| # | Field label | UM field type | Meta key | Required | Options / notes |
|---|---|---|---|---|---|
| 1 | Email address | Email | user_email | Yes | Native WP field, always present |
| 2 | Password | Password | user_password | Yes | Native WP field, show strength meter |
| 3 | Confirm password | Password (confirm) | confirm_user_password | Yes | Native WP field |

**Build notes:**
- Keep this form to these three fields only. No name, no phone. Friction here causes drop-off.
- Add a single checkbox below the button: "I agree to the [Terms of Service] and [Privacy Policy]" — required, no UM field needed, add as custom HTML element in the form footer.
- Button label: **Create my account** (not "Register" or "Submit")

---

## Form 2: Basic profile form (guided setup)

> UM admin path: Ultimate Member → Forms → Add New → select "Profile"
> Assign to role: **Talent**
> Page: Create a WP page called "Profile Setup", drop in the UM profile shortcode
> Post-save redirect: **/dashboard** (or the UM account page)

### Section A — Identity

> Add a UM "Section divider" above this group. Label: **Tell us who you are**

| # | Field label | UM field type | Meta key | Required | Options / notes |
|---|---|---|---|---|---|
| 1 | Full name | Text | full_name | Yes | Max 80 chars. This is the display name. |
| 2 | Stage name | Text | stage_name | No | Max 80 chars. Helper text: "Leave blank if same as full name" |
| 3 | Talent categories | Multi-checkbox | talent_categories | Yes | See options list A below |
| 4 | City | Text | city | Yes | Max 60 chars. E.g. "Cape Town" |
| 5 | Province / region | Select (dropdown) | province | Yes | See options list B below |
| 6 | Languages | Multi-checkbox | languages | Yes | See options list C below. Min 1 selection. |

**Options list A — talent_categories:**
Actor, Extra / background, Model, Voice artist, Stunt performer, Dancer, Musician, Influencer / content creator, Production crew

**Options list B — province:**
Eastern Cape, Free State, Gauteng, KwaZulu-Natal, Limpopo, Mpumalanga, North West, Northern Cape, Western Cape, Outside South Africa

**Options list C — languages:**
Afrikaans, English, isiZulu, isiXhosa, Sesotho, Setswana, Sepedi, Xitsonga, Tshivenda, Siswati, isiNdebele, Other

---

### Section B — Castable basics

> Add a UM "Section divider" above this group. Label: **A few casting essentials**

| # | Field label | UM field type | Meta key | Required | Options / notes |
|---|---|---|---|---|---|
| 7 | Date of birth | Date | date_of_birth | Yes | UM date picker. Do not display the calculated age publicly — only casting professionals with verified accounts should see DOB. Display age range on public profile instead. |
| 8 | Gender | Select (dropdown) | gender | Yes | Man, Woman, Non-binary, Prefer not to say |
| 9 | Height (cm) | Number | height_cm | No | Min 100, max 230. Helper text: "Enter in centimetres, e.g. 175" |
| 10 | Short bio | Textarea | bio_short | No | Max 300 chars. Helper text: "A short intro casting directors will read first. Keep it punchy." |

---

### Section C — Headshot

> Add a UM "Section divider" above this group. Label: **Your main headshot**

| # | Field label | UM field type | Meta key | Required | Options / notes |
|---|---|---|---|---|---|
| 11 | Profile photo | Profile photo (UM native) | profile_photo | Yes | Use UM's built-in profile photo field — it includes the cropper. Accepted: JPG, PNG. Max file size: 5 MB. Recommended dimensions note: "Minimum 600×600px, square crop works best." |

**Build notes for Section C:**
- The UM image cropper library is already loaded on the site (confirmed in the HTML audit). No extra plugin needed.
- Set crop ratio to 1:1 (square) in UM settings for consistency across the directory.
- This is the only media field in the basic profile. Gallery and showreel are deferred to the extended form.

---

### Form 2 footer

- Button label: **Save and view my profile**
- Below button, small text: "You can add your portfolio, showreel, and more from your dashboard."
- Do NOT add a skip button. The form should feel completeable in under 5 minutes.

---

## Form 3: Extended profile form (Sprint 2 — do not build yet)

> Listed here for planning. Elouise to build after Sprint 1 is live and tested.

Fields deferred to this form:

| Group | Fields |
|---|---|
| Physical attributes | Weight (kg), eye colour, hair colour, clothing sizes (top, bottom, shoe) |
| Professional | Union membership status, years of experience, special skills (free text + taxonomy tags), training / qualifications |
| Media — gallery | Photo gallery (up to 10 images) |
| Media — showreel | Showreel video URL (YouTube / Vimeo embed, not direct upload for POC) |
| Media — voice reel | Voice reel audio file OR URL |
| Availability | Available from / to dates, travel willingness (local only / national / international), current projects (free text) |
| Verification | ID upload field — file, PDF or JPG, private (admin-only visibility). Flag field: verified (boolean, admin-set only) |
| Ethnicity | Optional, sensitive. Label: "Ethnic background (optional — used only for casting matching)". Free text, not dropdown. Visible to verified casting professionals only. POPIA consent note required adjacent to this field. |

---

## UM role and directory settings

| Setting | Value |
|---|---|
| Role slug | talent |
| Default account status | Awaiting email confirmation |
| Profile permalink | /profiles/{username} |
| Directory page | /profiles |
| Directory default sort | Recently joined |
| Who can view profiles | Everyone (public) |
| Who can view DOB field | Members with role: casting_pro, production, admin |
| Who can edit profile | Owner + admin |

---

## POPIA note (for Gustav to review before go-live)

The following fields collect personal information as defined under the Protection of Personal Information Act (POPIA):
- Date of birth
- Profile photo
- Location (city + province)
- Ethnicity (extended form)
- ID document (extended form)

A POPIA-compliant privacy notice must be linked from the registration form and the profile setup form before go-live. The consent checkbox on registration covers collection. Gustav to confirm with legal counsel whether a separate data processing notice is needed for casting professionals who view profiles.

---

*Document version: 1.0 — Sprint 1*
*Next review: after Form 2 is live in staging*
