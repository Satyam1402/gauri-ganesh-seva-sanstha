# Database Architecture
## Gauri Ganesh Seva Sanstha — MySQL Schema Design (Phase 3)

**Document Version:** 1.0
**Status:** Draft for Stakeholder Review
**Depends On:** [SRS.md](SRS.md), [UI-UX-BLUEPRINT.md](UI-UX-BLUEPRINT.md)
**Scope:** Database architecture only — no SQL, no migrations, no models, no PHP.

---

## 0. Design Ground Rules

Before the table-by-table design, five decisions shape the entire schema. Understanding *why* now prevents "why did they do it this way?" confusion later:

1. **Target normalization is 3NF, with two deliberate, documented denormalizations** (a cached `raised_amount` on campaigns, and a cached `views_count` on blog posts) — both are read-heavy, write-rare aggregates where recomputing via `JOIN`/`SUM` on every homepage load would be wasteful. Every other table is fully normalized.
2. **Polymorphic tables are used for three cross-cutting concerns — Media, SEO, and Audit Logs** — instead of repeating `image_id`, `meta_title`, or per-module log tables everywhere. This is the single biggest driver of the "future-proof without restructuring" goal: adding a new content type (e.g., Scholarships in Phase 6) automatically gets media, SEO, and audit support for free.
3. **Financial data (`donations`) is treated as an append-only ledger**, per SRS Section 14 — no soft deletes, status transitions only, immutable once `success`.
4. **Lookup values that admins should be able to edit (categories, tags) are real tables; fixed, rarely-changing bounded sets (status flags) are `ENUM`** — explained per-table where relevant, with the trade-off made explicit rather than applying one rule blindly everywhere.
5. **One module in this document — Help Requests — was not in the module list provided but is carried over from SRS FR-06/FR-22/Section 14.** Flagging this explicitly rather than silently dropping a committed requirement.

---

## 1. Naming Conventions

| Element | Convention | Example |
|---|---|---|
| Table names | `snake_case`, plural | `donation_campaigns`, `volunteer_applications` |
| Primary key | Always `id`, `BIGINT UNSIGNED AUTO_INCREMENT` | `id` |
| Foreign key column | `singular_table_name_id` | `campaign_id`, `activity_category_id` |
| Foreign key constraint name | `fk_{table}_{column}` | `fk_donations_campaign_id` |
| Pivot tables | Two related singular table names, alphabetical, underscore-joined | `permission_role`, `blog_post_tag` |
| Boolean columns | Prefixed `is_` / `has_` | `is_active`, `is_featured`, `has_registration` |
| Timestamp columns | Suffixed `_at` | `published_at`, `donated_at`, `deleted_at` |
| Status/enum columns | Named `status`, values lower_snake_case | `status = 'in_review'` |
| Index name | `idx_{table}_{column(s)}` | `idx_donations_status` |
| Unique constraint name | `uq_{table}_{column(s)}` | `uq_users_email` |
| Slug columns | Always `slug`, `VARCHAR(191)`, unique per relevant scope | `slug` |
| Money columns | `DECIMAL(12,2)` — never `FLOAT`/`DOUBLE` | `amount`, `goal_amount` |
| Soft-delete column | `deleted_at`, nullable `DATETIME` | — |

**Global conventions applied to every table unless explicitly noted otherwise:**
- Primary key: `id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`
- Timestamps: `created_at`, `updated_at` (both `TIMESTAMP`, Laravel-managed)
- Engine: `InnoDB` (row-level locking, FK support, transaction safety — required for financial data integrity)
- Charset/Collation: `utf8mb4` / `utf8mb4_unicode_ci` (mandatory, not optional — supports emoji in testimonials/blog and correct sorting for future Hindi/Marathi content per SRS Section 5 i18n note)

---

## 2. Module-Wise Database Design

### Module A — Identity & Access Control

Handles admin authentication and fine-grained authorization. Roles and Permissions are modeled many-to-many in both directions (a role has many permissions; a user can hold many roles) rather than a single `role_id` column on `users` — this is intentionally more flexible than the minimum needed today (e.g., Super Admin / Content Manager / Finance Viewer per SRS FR-20) because access needs for an NGO admin team tend to grow organically (e.g., a "Volunteer Coordinator" role added in year 2 without a schema change).

#### `users`
Purpose: admin/staff accounts (public visitors never get a row here — donors/volunteers are separate entities, not users).

| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | — | PK |
| name | VARCHAR(150) | No | — | |
| email | VARCHAR(191) | No | — | login identifier |
| email_verified_at | TIMESTAMP | Yes | NULL | |
| password | VARCHAR(255) | No | — | hashed |
| phone | VARCHAR(20) | Yes | NULL | |
| avatar_media_id | BIGINT UNSIGNED | Yes | NULL | FK → media.id |
| status | ENUM('active','suspended') | No | 'active' | login gate independent of soft delete |
| last_login_at | TIMESTAMP | Yes | NULL | |
| remember_token | VARCHAR(100) | Yes | NULL | |

Meta: PK `id` · FK `avatar_media_id → media.id` (SET NULL) · Unique: `email` · Indexes: `idx_users_status` · Soft Delete: Yes (retain historical audit-log attribution after offboarding staff) · Timestamps: created_at, updated_at
Sample Data: `{id:1, name:"Suresh Patil", email:"suresh@ggss.org", status:"active"}`

#### `roles`
Purpose: named access levels (Super Admin, Content Manager, Finance Viewer, etc.).

| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | — | PK |
| name | VARCHAR(100) | No | — | e.g. "Finance Viewer" |
| slug | VARCHAR(100) | No | — | e.g. "finance-viewer" |
| description | VARCHAR(255) | Yes | NULL | |

Meta: PK `id` · Unique: `slug` · Soft Delete: No (roles are system config, delete = hard, guarded at app level if in use) · Timestamps: created_at, updated_at
Sample Data: `{id:2, name:"Finance Viewer", slug:"finance-viewer"}`

#### `permissions`
Purpose: atomic capability flags grouped by module, assigned to roles.

| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | — | PK |
| name | VARCHAR(150) | No | — | e.g. "View Donations" |
| slug | VARCHAR(150) | No | — | e.g. "donations.view" |
| module | VARCHAR(100) | No | — | e.g. "donations" — groups permissions in the admin UI |

Meta: PK `id` · Unique: `slug` · Indexes: `idx_permissions_module` · Soft Delete: No · Timestamps: created_at, updated_at
Sample Data: `{id:5, name:"Export Donations", slug:"donations.export", module:"donations"}`

#### `permission_role` (pivot — Many-to-Many)
Purpose: which permissions each role grants.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| permission_id | BIGINT UNSIGNED | No | FK → permissions.id |
| role_id | BIGINT UNSIGNED | No | FK → roles.id |

Meta: PK: composite (`permission_id`, `role_id`) · FKs: both CASCADE on delete · Soft Delete: No · Timestamps: created_at only
Sample Data: `{permission_id:5, role_id:2}`

#### `role_user` (pivot — Many-to-Many)
Purpose: which roles each user holds (supports multi-role staff).

| Column | Type | Nullable | Notes |
|---|---|---|---|
| role_id | BIGINT UNSIGNED | No | FK → roles.id |
| user_id | BIGINT UNSIGNED | No | FK → users.id |

Meta: PK: composite (`role_id`, `user_id`) · FKs: both CASCADE on delete · Soft Delete: No · Timestamps: created_at only
Sample Data: `{role_id:2, user_id:1}`

#### `audit_logs`
Purpose: immutable trail of sensitive admin actions (SRS Section 14). See Section 10 for the full strategy.

| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | — | PK |
| user_id | BIGINT UNSIGNED | Yes | NULL | FK → users.id, nullable so log survives even if user later deleted |
| action | VARCHAR(50) | No | — | e.g. `created`, `updated`, `deleted`, `status_changed` |
| auditable_type | VARCHAR(150) | No | — | polymorphic class/entity reference, e.g. `Donation` |
| auditable_id | BIGINT UNSIGNED | No | — | polymorphic target row id |
| old_values | JSON | Yes | NULL | pre-change snapshot |
| new_values | JSON | Yes | NULL | post-change snapshot |
| ip_address | VARCHAR(45) | Yes | NULL | IPv4/IPv6 |
| user_agent | VARCHAR(255) | Yes | NULL | |

Meta: PK `id` · FK `user_id → users.id` (SET NULL) · Indexes: `idx_audit_logs_auditable` (`auditable_type`,`auditable_id`), `idx_audit_logs_user_id` · Soft Delete: No (audit logs must never be deletable, even soft — enforce via no delete route in app layer) · Timestamps: `created_at` only (logs are never updated)
Sample Data: `{id:88, user_id:1, action:"status_changed", auditable_type:"HelpRequest", auditable_id:14, old_values:{"status":"new"}, new_values:{"status":"resolved"}}`

---

### Module B — Media, SEO & Global Content Infrastructure

These three tables are the backbone that every other content module plugs into. Designed once, used everywhere — see Sections 8 and 9 for full rationale.

#### `media`
Purpose: single polymorphic media library for every image/file in the system (campaign photos, blog featured images, gallery items, documents, receipts).

| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | — | PK |
| mediable_type | VARCHAR(150) | Yes | NULL | polymorphic owner class, nullable until attached |
| mediable_id | BIGINT UNSIGNED | Yes | NULL | polymorphic owner id |
| collection_name | VARCHAR(100) | No | 'default' | e.g. `featured_image`, `gallery`, `documents` — namespaces multiple media on one owner |
| disk | VARCHAR(50) | No | 'public' | Laravel filesystem disk name |
| file_name | VARCHAR(255) | No | — | stored filename (hashed/randomized, not user-supplied) |
| original_name | VARCHAR(255) | No | — | original upload filename, kept for admin UI display |
| mime_type | VARCHAR(100) | No | — | |
| size | INT UNSIGNED | No | — | bytes |
| width | SMALLINT UNSIGNED | Yes | NULL | for images only |
| height | SMALLINT UNSIGNED | Yes | NULL | for images only |
| alt_text | VARCHAR(255) | No | — | **NOT NULL by design** — enforces SRS Section 17 accessibility requirement at the schema level, not just UI convention |
| order_column | SMALLINT UNSIGNED | No | 0 | manual drag-to-reorder support |
| uploaded_by | BIGINT UNSIGNED | Yes | NULL | FK → users.id |

Meta: PK `id` · FK `uploaded_by → users.id` (SET NULL) · Indexes: `idx_media_mediable` (`mediable_type`,`mediable_id`,`collection_name`) · Soft Delete: Yes (recoverable if accidentally removed from an in-use gallery/campaign) · Timestamps: created_at, updated_at
Sample Data: `{id:42, mediable_type:"DonationCampaign", mediable_id:3, collection_name:"featured_image", file_name:"a1b2c3.webp", alt_text:"Volunteers distributing food packets in Pune"}`

#### `seo_meta`
Purpose: polymorphic SEO metadata attachable to any content type (blog posts, campaigns, activities, events, static pages) — see Section 9.

| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | — | PK |
| seo_metable_type | VARCHAR(150) | No | — | polymorphic owner class |
| seo_metable_id | BIGINT UNSIGNED | No | — | polymorphic owner id |
| meta_title | VARCHAR(70) | Yes | NULL | length-capped to SEO-safe display limit |
| meta_description | VARCHAR(160) | Yes | NULL | length-capped |
| canonical_url | VARCHAR(255) | Yes | NULL | |
| og_title | VARCHAR(70) | Yes | NULL | falls back to meta_title if null (app-layer logic) |
| og_description | VARCHAR(200) | Yes | NULL | |
| og_image_media_id | BIGINT UNSIGNED | Yes | NULL | FK → media.id |
| schema_type | VARCHAR(50) | Yes | NULL | e.g. `Article`, `Event`, `NGO` — drives JSON-LD template selection |
| structured_data | JSON | Yes | NULL | extra schema.org fields not covered by standard columns |

Meta: PK `id` · FK `og_image_media_id → media.id` (SET NULL) · Unique: `uq_seo_meta_owner` (`seo_metable_type`,`seo_metable_id`) — one SEO record per content item · Soft Delete: No · Timestamps: created_at, updated_at
Sample Data: `{seo_metable_type:"BlogPost", seo_metable_id:12, meta_title:"How Your ₹500 Feeds a Family for a Week", schema_type:"Article"}`

#### `settings`
Purpose: global key-value site configuration (see Section 11 for the single-vs-multiple-table decision).

| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | — | PK |
| group | VARCHAR(50) | No | — | e.g. `general`, `seo_defaults`, `mail`, `payment` |
| key | VARCHAR(100) | No | — | e.g. `contact_phone` |
| value | TEXT | Yes | NULL | scalar or JSON-encoded value |
| type | ENUM('string','number','boolean','json') | No | 'string' | drives cast on read |

Meta: PK `id` · Unique: `uq_settings_group_key` (`group`,`key`) · Soft Delete: No · Timestamps: created_at, updated_at
Sample Data: `{group:"general", key:"contact_whatsapp", value:"+91XXXXXXXXXX", type:"string"}`

---

### Module C — Navigation & Layout

#### `menus`
Purpose: named menu containers (Primary Header, Admin Sidebar future-extension, etc.).

| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | — | PK |
| name | VARCHAR(100) | No | — | |
| slug | VARCHAR(100) | No | — | |
| location | VARCHAR(50) | No | — | e.g. `header`, `footer` |

Meta: PK `id` · Unique: `slug` · Soft Delete: No · Timestamps: created_at, updated_at
Sample Data: `{name:"Primary Navigation", slug:"primary-nav", location:"header"}`

#### `menu_items`
Purpose: individual links within a menu, self-referencing for dropdown/mega-menu nesting (UI-UX-BLUEPRINT Section 15).

| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | — | PK |
| menu_id | BIGINT UNSIGNED | No | — | FK → menus.id |
| parent_id | BIGINT UNSIGNED | Yes | NULL | FK → menu_items.id (self-referencing, one level of nesting used today) |
| label | VARCHAR(100) | No | — | |
| url | VARCHAR(255) | Yes | NULL | used when not linking to internal content |
| linkable_type | VARCHAR(150) | Yes | NULL | polymorphic — link directly to an Activity/BlogPost/Campaign |
| linkable_id | BIGINT UNSIGNED | Yes | NULL | |
| icon | VARCHAR(50) | Yes | NULL | Heroicon name |
| order_column | SMALLINT UNSIGNED | No | 0 | |
| is_active | BOOLEAN | No | true | |

Meta: PK `id` · FKs: `menu_id → menus.id` (CASCADE), `parent_id → menu_items.id` (CASCADE) · Indexes: `idx_menu_items_menu_id`, `idx_menu_items_linkable` · Soft Delete: No · Timestamps: created_at, updated_at
Sample Data: `{menu_id:1, parent_id:NULL, label:"Programs", linkable_type:NULL, order_column:2}`

#### `footer_columns`
Purpose: structured footer link groups (UI-UX-BLUEPRINT Section 16), deliberately relational rather than jammed into `settings` since it's a growing collection, not a scalar.

| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | — | PK |
| title | VARCHAR(100) | No | — | e.g. "Explore" |
| order_column | SMALLINT UNSIGNED | No | 0 | |
| is_active | BOOLEAN | No | true | |

Meta: PK `id` · Soft Delete: No · Timestamps: created_at, updated_at
Sample Data: `{title:"Get Involved", order_column:2}`

#### `footer_links`
Purpose: individual links within a footer column.

| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | — | PK |
| footer_column_id | BIGINT UNSIGNED | No | — | FK → footer_columns.id |
| label | VARCHAR(100) | No | — | |
| url | VARCHAR(255) | No | — | |
| order_column | SMALLINT UNSIGNED | No | 0 | |
| is_active | BOOLEAN | No | true | |

Meta: PK `id` · FK `footer_column_id → footer_columns.id` (CASCADE) · Indexes: `idx_footer_links_column_id` · Soft Delete: No · Timestamps: created_at, updated_at
Sample Data: `{footer_column_id:2, label:"Volunteer", url:"/get-involved/volunteer"}`

#### `social_links`
Purpose: brand social profiles shown in header/footer.

| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | — | PK |
| platform | VARCHAR(50) | No | — | e.g. `instagram`, `facebook` |
| url | VARCHAR(255) | No | — | |
| icon | VARCHAR(50) | Yes | NULL | overrides default platform icon if set |
| order_column | SMALLINT UNSIGNED | No | 0 | |
| is_active | BOOLEAN | No | true | |

Meta: PK `id` · Soft Delete: No · Timestamps: created_at, updated_at
Sample Data: `{platform:"instagram", url:"https://instagram.com/ggss.org"}`

#### `banners`
Purpose: homepage hero slides / promotional strips, time-boundable (e.g., seasonal Ganesh Utsav campaign banner).

| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | — | PK |
| title | VARCHAR(150) | No | — | |
| subtitle | VARCHAR(255) | Yes | NULL | |
| media_id | BIGINT UNSIGNED | No | — | FK → media.id |
| link_url | VARCHAR(255) | Yes | NULL | |
| button_text | VARCHAR(50) | Yes | NULL | |
| position | VARCHAR(50) | No | 'homepage_hero' | placement key |
| starts_at | DATETIME | Yes | NULL | |
| ends_at | DATETIME | Yes | NULL | |
| order_column | SMALLINT UNSIGNED | No | 0 | |
| is_active | BOOLEAN | No | true | |

Meta: PK `id` · FK `media_id → media.id` (RESTRICT — a banner cannot exist without its image) · Indexes: `idx_banners_position_active` · Soft Delete: Yes · Timestamps: created_at, updated_at
Sample Data: `{title:"Ganesh Utsav Seva Drive 2026", position:"homepage_hero", starts_at:"2026-08-15", ends_at:"2026-09-10"}`

---

### Module D — Organization Identity

`org_profile` and `organization_statements` are intentionally designed as **singleton tables** (application logic enforces exactly one active row; the schema doesn't hard-block it since a `UNIQUE` constraint on a constant would be awkward in MySQL, but it's a documented invariant, not a free-for-all table).

#### `org_profile`
Purpose: the organization's own legal/contact identity — powers About page, footer trust badges, and JSON-LD `Organization` schema.

| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | — | PK — singleton, always id = 1 |
| legal_name | VARCHAR(200) | No | — | |
| short_name | VARCHAR(100) | No | — | |
| registration_no | VARCHAR(100) | Yes | NULL | Trust registration number |
| registration_date | DATE | Yes | NULL | |
| pan_no | VARCHAR(20) | Yes | NULL | |
| trust_deed_no | VARCHAR(100) | Yes | NULL | |
| section_80g_no | VARCHAR(100) | Yes | NULL | |
| section_12a_no | VARCHAR(100) | Yes | NULL | |
| established_year | YEAR | Yes | NULL | |
| description | TEXT | Yes | NULL | "Our Story" content |
| address | VARCHAR(255) | Yes | NULL | |
| city | VARCHAR(100) | Yes | NULL | |
| state | VARCHAR(100) | Yes | NULL | |
| pincode | VARCHAR(10) | Yes | NULL | |
| phone | VARCHAR(20) | Yes | NULL | |
| email | VARCHAR(150) | Yes | NULL | |
| whatsapp_number | VARCHAR(20) | Yes | NULL | |
| map_embed_url | VARCHAR(500) | Yes | NULL | |

Meta: PK `id` · Soft Delete: No · Timestamps: created_at, updated_at
Sample Data: `{legal_name:"Gauri Ganesh Seva Sanstha", registration_no:"MH/1234/PUNE/2015", section_80g_no:"AABTG1234C..."}`

#### `organization_statements`
Purpose: Mission and Vision content blocks.

| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | — | PK |
| type | ENUM('mission','vision') | No | — | |
| content | TEXT | No | — | |

Meta: PK `id` · Unique: `uq_org_statements_type` (one row per type) · Soft Delete: No · Timestamps: created_at, updated_at
Sample Data: `{type:"mission", content:"To restore dignity through food, education, medical care, and seva."}`

#### `core_values`
Purpose: repeatable value cards on Mission & Vision page (UI-UX-BLUEPRINT).

| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | — | PK |
| title | VARCHAR(100) | No | — | |
| description | VARCHAR(255) | Yes | NULL | |
| icon | VARCHAR(50) | Yes | NULL | |
| order_column | SMALLINT UNSIGNED | No | 0 | |
| is_active | BOOLEAN | No | true | |

Meta: PK `id` · Soft Delete: No · Timestamps: created_at, updated_at
Sample Data: `{title:"Compassion", icon:"heart"}`

#### `team_members`
Purpose: trustees/staff profiles for About page.

| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | — | PK |
| name | VARCHAR(150) | No | — | |
| designation | VARCHAR(100) | No | — | |
| bio | TEXT | Yes | NULL | |
| photo_media_id | BIGINT UNSIGNED | Yes | NULL | FK → media.id |
| order_column | SMALLINT UNSIGNED | No | 0 | |
| is_active | BOOLEAN | No | true | |

Meta: PK `id` · FK `photo_media_id → media.id` (SET NULL) · Soft Delete: Yes · Timestamps: created_at, updated_at
Sample Data: `{name:"Suresh Patil", designation:"Founder & Trustee"}`

---

### Module E — Programs (Activities)

One-to-Many: an `activity_category` has many `activities`.

#### `activity_categories`
Purpose: the 5 core service areas (Food, Education, Medical, Clothes, Welfare) — kept as an editable table, not an ENUM, since a 6th program area (e.g., "Scholarship Programs," Section 12) is an explicit future-roadmap item.

| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | — | PK |
| name | VARCHAR(100) | No | — | |
| slug | VARCHAR(100) | No | — | |
| description | VARCHAR(255) | Yes | NULL | |
| icon | VARCHAR(50) | Yes | NULL | |
| order_column | SMALLINT UNSIGNED | No | 0 | |
| is_active | BOOLEAN | No | true | |

Meta: PK `id` · Unique: `slug` · Soft Delete: No · Timestamps: created_at, updated_at
Sample Data: `{name:"Medical Help", slug:"medical-help", icon:"heart"}`

#### `activities`
Purpose: detail pages for each program.

| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | — | PK |
| activity_category_id | BIGINT UNSIGNED | No | — | FK → activity_categories.id |
| title | VARCHAR(200) | No | — | |
| slug | VARCHAR(200) | No | — | |
| short_description | VARCHAR(255) | Yes | NULL | |
| description | LONGTEXT | Yes | NULL | rich content |
| icon | VARCHAR(50) | Yes | NULL | |
| status | ENUM('draft','published') | No | 'draft' | |
| is_featured | BOOLEAN | No | false | |
| order_column | SMALLINT UNSIGNED | No | 0 | |

Meta: PK `id` · FK `activity_category_id → activity_categories.id` (RESTRICT) · Unique: `slug` · Indexes: `idx_activities_status` · Soft Delete: Yes · Timestamps: created_at, updated_at
Sample Data: `{activity_category_id:3, title:"Free Medical Camps", slug:"free-medical-camps", status:"published"}`
Related polymorphic attachments: `media` (`mediable_type = 'Activity'`), `seo_meta` (`seo_metable_type = 'Activity'`).

---

### Module F — Gallery

One-to-Many: a `gallery_category` (album/event) has many `gallery_items`.

#### `gallery_categories`
Purpose: photo albums, typically per event/drive (UI-UX-BLUEPRINT Admin > Gallery Management).

| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | — | PK |
| name | VARCHAR(150) | No | — | e.g. "Ganesh Utsav Drive 2026" |
| slug | VARCHAR(150) | No | — | |
| description | VARCHAR(255) | Yes | NULL | |
| event_date | DATE | Yes | NULL | |
| order_column | SMALLINT UNSIGNED | No | 0 | |
| is_active | BOOLEAN | No | true | |

Meta: PK `id` · Unique: `slug` · Soft Delete: Yes · Timestamps: created_at, updated_at
Sample Data: `{name:"Food Distribution Drive - March 2026", slug:"food-drive-march-2026"}`

#### `gallery_items`
Purpose: individual images within an album; the actual file lives in `media`, this table stores album membership + caption + order.

| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | — | PK |
| gallery_category_id | BIGINT UNSIGNED | No | — | FK → gallery_categories.id |
| media_id | BIGINT UNSIGNED | No | — | FK → media.id |
| caption | VARCHAR(255) | Yes | NULL | |
| order_column | SMALLINT UNSIGNED | No | 0 | |
| is_active | BOOLEAN | No | true | |

Meta: PK `id` · FKs: `gallery_category_id → gallery_categories.id` (CASCADE), `media_id → media.id` (CASCADE) · Indexes: `idx_gallery_items_category_id` · Soft Delete: No (deletion cascades cleanly from parent album) · Timestamps: created_at, updated_at
Sample Data: `{gallery_category_id:5, media_id:120, caption:"Volunteers packing meal kits"}`

---

### Module G — Events

#### `events`
Purpose: upcoming/past org events (distinct from Donation Campaigns — an Event is a date-bound gathering, a Campaign is a fundraising goal; they may reference each other but are not merged, since a future Medical/Blood Donation Camp — Section 12 — is structurally an Event with a specialized sub-type, not a Campaign).

| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | — | PK |
| title | VARCHAR(200) | No | — | |
| slug | VARCHAR(200) | No | — | |
| event_type | VARCHAR(50) | No | 'general' | future-proof discriminator: `general`, `medical_camp`, `blood_donation_camp` (Section 12) |
| description | LONGTEXT | Yes | NULL | |
| venue | VARCHAR(200) | Yes | NULL | |
| address | VARCHAR(255) | Yes | NULL | |
| event_date | DATE | No | — | |
| start_time | TIME | Yes | NULL | |
| end_time | TIME | Yes | NULL | |
| registration_required | BOOLEAN | No | false | |
| status | ENUM('upcoming','ongoing','completed','cancelled') | No | 'upcoming' | |

Meta: PK `id` · Unique: `slug` · Indexes: `idx_events_status_date` (`status`,`event_date`), `idx_events_type` · Soft Delete: Yes · Timestamps: created_at, updated_at
Sample Data: `{title:"Free Health Checkup Camp", event_type:"medical_camp", event_date:"2026-09-20", status:"upcoming"}`

#### `event_registrations`
Purpose: RSVP/sign-up capture for events requiring registration.

| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | — | PK |
| event_id | BIGINT UNSIGNED | No | — | FK → events.id |
| name | VARCHAR(150) | No | — | |
| email | VARCHAR(150) | Yes | NULL | |
| phone | VARCHAR(20) | No | — | |
| status | ENUM('registered','attended','cancelled') | No | 'registered' | |

Meta: PK `id` · FK `event_id → events.id` (CASCADE) · Indexes: `idx_event_registrations_event_id` · Soft Delete: No · Timestamps: created_at, updated_at
Sample Data: `{event_id:9, name:"Rohit Deshmukh", phone:"9876543210", status:"registered"}`

---

### Module H — Blog

One-to-Many: `blog_category` → `blog_posts`. Many-to-Many: `blog_posts` ↔ `blog_tags` via `blog_post_tag`.

#### `blog_categories`
| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | — | PK |
| name | VARCHAR(100) | No | — | |
| slug | VARCHAR(100) | No | — | |
| order_column | SMALLINT UNSIGNED | No | 0 | |
| is_active | BOOLEAN | No | true | |

Meta: PK `id` · Unique: `slug` · Soft Delete: No · Timestamps: created_at, updated_at
Sample Data: `{name:"Impact Stories", slug:"impact-stories"}`

#### `blog_posts`
| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | — | PK |
| blog_category_id | BIGINT UNSIGNED | No | — | FK → blog_categories.id |
| author_id | BIGINT UNSIGNED | Yes | NULL | FK → users.id |
| title | VARCHAR(200) | No | — | |
| slug | VARCHAR(200) | No | — | |
| excerpt | VARCHAR(255) | Yes | NULL | |
| body | LONGTEXT | No | — | |
| status | ENUM('draft','published','scheduled') | No | 'draft' | |
| published_at | DATETIME | Yes | NULL | |
| views_count | INT UNSIGNED | No | 0 | **denormalized cache** — incremented async, not recomputed via join per view (Section 0, item 1) |

Meta: PK `id` · FKs: `blog_category_id → blog_categories.id` (RESTRICT), `author_id → users.id` (SET NULL) · Unique: `slug` · Indexes: `idx_blog_posts_status_published` (`status`,`published_at`) · Soft Delete: Yes · Timestamps: created_at, updated_at
Sample Data: `{blog_category_id:1, title:"How Your ₹500 Feeds a Family for a Week", status:"published", published_at:"2026-06-01 09:00:00"}`
Related polymorphic attachments: `media` (featured_image), `seo_meta`.

#### `blog_tags`
| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | — | PK |
| name | VARCHAR(50) | No | — | |
| slug | VARCHAR(50) | No | — | |

Meta: PK `id` · Unique: `slug` · Soft Delete: No · Timestamps: created_at, updated_at
Sample Data: `{name:"Food Drive", slug:"food-drive"}`

#### `blog_post_tag` (pivot — Many-to-Many)
| Column | Type | Nullable | Notes |
|---|---|---|---|
| blog_post_id | BIGINT UNSIGNED | No | FK → blog_posts.id |
| blog_tag_id | BIGINT UNSIGNED | No | FK → blog_tags.id |

Meta: PK: composite (`blog_post_id`,`blog_tag_id`) · FKs: both CASCADE · Soft Delete: No · Timestamps: created_at only
Sample Data: `{blog_post_id:12, blog_tag_id:3}`

---

### Module I — Volunteering

Deliberate two-table design: `volunteer_applications` is the raw inbound form (SRS FR-05), `volunteers` is the promoted, ongoing-relationship record created once an application is accepted. This split is what allows the future **Volunteer Attendance** module (Section 12) to attach to a stable `volunteers.id` without being polluted by rejected/duplicate applications.

#### `volunteer_applications`
| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | — | PK |
| full_name | VARCHAR(150) | No | — | |
| email | VARCHAR(150) | No | — | |
| phone | VARCHAR(20) | No | — | |
| city | VARCHAR(100) | Yes | NULL | |
| activity_category_id | BIGINT UNSIGNED | Yes | NULL | FK → activity_categories.id — area of interest |
| availability | VARCHAR(255) | Yes | NULL | |
| message | TEXT | Yes | NULL | |
| status | ENUM('new','in_review','contacted','onboarded','rejected') | No | 'new' | |
| reviewed_by | BIGINT UNSIGNED | Yes | NULL | FK → users.id |
| internal_notes | TEXT | Yes | NULL | admin-only, never public |

Meta: PK `id` · FKs: `activity_category_id → activity_categories.id` (SET NULL), `reviewed_by → users.id` (SET NULL) · Indexes: `idx_volunteer_applications_status` · Soft Delete: No (retained as a permanent inbound record for pipeline reporting) · Timestamps: created_at, updated_at
Sample Data: `{full_name:"Rohit Deshmukh", email:"rohit@example.com", status:"new"}`

#### `volunteers`
| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | — | PK |
| volunteer_application_id | BIGINT UNSIGNED | Yes | NULL | FK → volunteer_applications.id — traceability to origin |
| full_name | VARCHAR(150) | No | — | |
| email | VARCHAR(150) | No | — | |
| phone | VARCHAR(20) | No | — | |
| address | VARCHAR(255) | Yes | NULL | |
| photo_media_id | BIGINT UNSIGNED | Yes | NULL | FK → media.id |
| joined_date | DATE | No | — | |
| status | ENUM('active','inactive') | No | 'active' | |

Meta: PK `id` · FKs: `volunteer_application_id → volunteer_applications.id` (SET NULL), `photo_media_id → media.id` (SET NULL) · Unique: `uq_volunteers_email` · Soft Delete: Yes · Timestamps: created_at, updated_at
Sample Data: `{full_name:"Rohit Deshmukh", status:"active", joined_date:"2026-04-01"}`

---

### Module J — Fundraising (Donation Campaigns & Donations)

One-to-Many: a `donation_campaign` has many `donations`. `campaign_id` on `donations` is **nullable** to represent "Where Most Needed" general-fund giving (UI-UX-BLUEPRINT Donate page, Step 1).

#### `donation_campaigns`
| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | — | PK |
| activity_category_id | BIGINT UNSIGNED | Yes | NULL | FK → activity_categories.id — links campaign to a program area |
| title | VARCHAR(200) | No | — | |
| slug | VARCHAR(200) | No | — | |
| short_description | VARCHAR(255) | Yes | NULL | |
| description | LONGTEXT | Yes | NULL | |
| goal_amount | DECIMAL(12,2) | No | — | |
| raised_amount | DECIMAL(12,2) | No | 0.00 | **denormalized cache**, recalculated by a DB transaction/event whenever a linked donation flips to `success` (Section 0, item 1) — never trust this column alone for financial reconciliation; `SUM(donations.amount)` is the source of truth |
| start_date | DATE | No | — | |
| end_date | DATE | Yes | NULL | NULL = ongoing/no end date |
| status | ENUM('active','completed','paused','closed') | No | 'active' | |
| is_featured | BOOLEAN | No | false | |
| created_by | BIGINT UNSIGNED | Yes | NULL | FK → users.id |

Meta: PK `id` · FKs: `activity_category_id → activity_categories.id` (SET NULL), `created_by → users.id` (SET NULL) · Unique: `slug` · Indexes: `idx_campaigns_status`, `idx_campaigns_featured` · Soft Delete: Yes · Timestamps: created_at, updated_at
Sample Data: `{title:"Ganesh Utsav Food Seva 2026", slug:"ganesh-utsav-food-seva-2026", goal_amount:500000.00, raised_amount:187500.00, status:"active"}`
Related polymorphic attachments: `media`, `seo_meta`.

#### `donations`
Purpose: the financial ledger. Per SRS Section 14: append-only, no hard/soft delete, status transitions only.

| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | — | PK |
| campaign_id | BIGINT UNSIGNED | Yes | NULL | FK → donation_campaigns.id — NULL = general fund |
| donor_name | VARCHAR(150) | No | — | |
| donor_email | VARCHAR(150) | No | — | |
| donor_phone | VARCHAR(20) | Yes | NULL | |
| donor_pan | VARCHAR(20) | Yes | NULL | for 80G certificate eligibility |
| amount | DECIMAL(12,2) | No | — | |
| currency | CHAR(3) | No | 'INR' | future multi-currency readiness |
| payment_gateway | VARCHAR(50) | No | — | e.g. `razorpay` |
| gateway_order_id | VARCHAR(100) | Yes | NULL | |
| gateway_transaction_id | VARCHAR(100) | Yes | NULL | |
| payment_status | ENUM('pending','success','failed','refunded') | No | 'pending' | |
| receipt_number | VARCHAR(50) | Yes | NULL | generated only on `success` |
| is_anonymous | BOOLEAN | No | false | suppress donor name from any public donor-wall display |
| message | VARCHAR(255) | Yes | NULL | optional donor message |
| donated_at | TIMESTAMP | Yes | NULL | set when status becomes `success` |

Meta: PK `id` · FK `campaign_id → donation_campaigns.id` (RESTRICT — a campaign must never be hard-deleted while donations reference it) · Unique: `uq_donations_receipt_number`, `uq_donations_gateway_transaction_id` (prevents duplicate webhook processing — SRS FR-18) · Indexes: `idx_donations_status`, `idx_donations_campaign_id`, `idx_donations_donor_email` · Soft Delete: **No** (financial ledger — see Section 0) · Timestamps: created_at, updated_at
Sample Data: `{campaign_id:3, donor_name:"Anita Sharma", amount:1000.00, payment_status:"success", receipt_number:"GGSS-RCPT-2026-000123", is_anonymous:false}`
Related polymorphic attachments: `media` (`mediable_type='Donation'`, `collection_name='receipt_pdf'`) — receipt PDFs stored via the same central media table rather than a bespoke `donation_receipts` table, keeping file-handling logic in one place.

---

### Module K — Public Engagement (Help Requests, Contact, Testimonials, FAQ)

#### `help_requests`
*(Carried over from SRS FR-06/FR-22 — not in the original Phase 3 module list but required for continuity; flagged per Section 0.)*
Purpose: assistance requests from people seeking help — privacy-sensitive, never publicly exposed (SRS Section 14).

| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | — | PK |
| full_name | VARCHAR(150) | No | — | |
| phone | VARCHAR(20) | No | — | |
| email | VARCHAR(150) | Yes | NULL | |
| category | ENUM('food','education','medical','clothes','other') | No | — | |
| address | VARCHAR(255) | Yes | NULL | |
| description | TEXT | Yes | NULL | |
| status | ENUM('new','in_review','resolved','rejected') | No | 'new' | |
| handled_by | BIGINT UNSIGNED | Yes | NULL | FK → users.id |
| internal_notes | TEXT | Yes | NULL | admin-only |

Meta: PK `id` · FK `handled_by → users.id` (SET NULL) · Indexes: `idx_help_requests_status`, `idx_help_requests_category` · Soft Delete: No (retained for reporting; visibility restricted by role, not by deletion) · Timestamps: created_at, updated_at
Sample Data: `{full_name:"Sunita Jadhav", category:"medical", status:"in_review"}`

#### `contact_enquiries`
| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | — | PK |
| name | VARCHAR(150) | No | — | |
| email | VARCHAR(150) | No | — | |
| phone | VARCHAR(20) | Yes | NULL | |
| subject | VARCHAR(200) | Yes | NULL | |
| message | TEXT | No | — | |
| status | ENUM('new','read','replied','archived') | No | 'new' | |
| replied_by | BIGINT UNSIGNED | Yes | NULL | FK → users.id |

Meta: PK `id` · FK `replied_by → users.id` (SET NULL) · Indexes: `idx_contact_enquiries_status` · Soft Delete: No · Timestamps: created_at, updated_at
Sample Data: `{name:"Media Inquiry", subject:"CSR Partnership", status:"new"}`

#### `testimonials`
| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | — | PK |
| name | VARCHAR(150) | No | — | |
| role | ENUM('donor','volunteer','beneficiary','partner') | No | — | |
| photo_media_id | BIGINT UNSIGNED | Yes | NULL | FK → media.id |
| content | TEXT | No | — | |
| rating | TINYINT UNSIGNED | Yes | NULL | 1-5, optional |
| is_featured | BOOLEAN | No | false | |
| is_active | BOOLEAN | No | true | |
| order_column | SMALLINT UNSIGNED | No | 0 | |

Meta: PK `id` · FK `photo_media_id → media.id` (SET NULL) · Soft Delete: Yes · Timestamps: created_at, updated_at
Sample Data: `{name:"Anita Sharma", role:"donor", content:"Seeing the impact update after my donation made all the difference.", rating:5}`

#### `faq_categories`
| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | — | PK |
| name | VARCHAR(100) | No | — | |
| order_column | SMALLINT UNSIGNED | No | 0 | |
| is_active | BOOLEAN | No | true | |

Meta: PK `id` · Soft Delete: No · Timestamps: created_at, updated_at
Sample Data: `{name:"Donations"}`

#### `faqs`
| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | — | PK |
| faq_category_id | BIGINT UNSIGNED | Yes | NULL | FK → faq_categories.id |
| question | VARCHAR(255) | No | — | |
| answer | TEXT | No | — | |
| order_column | SMALLINT UNSIGNED | No | 0 | |
| is_active | BOOLEAN | No | true | |

Meta: PK `id` · FK `faq_category_id → faq_categories.id` (SET NULL) · Soft Delete: No · Timestamps: created_at, updated_at
Sample Data: `{question:"Is my donation tax-deductible?", answer:"Yes, under Section 80G..."}`

---

### Module L — Partnerships

#### `partners`
Purpose: CSR partners and campaign sponsors modeled as one table with a `type` discriminator, since both are "an external organization displayed with a logo," differing only in whether they're tied to a specific campaign.

| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | — | PK |
| name | VARCHAR(150) | No | — | |
| logo_media_id | BIGINT UNSIGNED | No | — | FK → media.id |
| website_url | VARCHAR(255) | Yes | NULL | |
| type | ENUM('partner','sponsor') | No | 'partner' | |
| donation_campaign_id | BIGINT UNSIGNED | Yes | NULL | FK → donation_campaigns.id — set only when `type='sponsor'` and tied to one campaign |
| order_column | SMALLINT UNSIGNED | No | 0 | |
| is_active | BOOLEAN | No | true | |

Meta: PK `id` · FKs: `logo_media_id → media.id` (RESTRICT), `donation_campaign_id → donation_campaigns.id` (SET NULL) · Indexes: `idx_partners_type` · Soft Delete: Yes · Timestamps: created_at, updated_at
Sample Data: `{name:"ABC Foundation", type:"sponsor", donation_campaign_id:3}`

---

### Module M — Notifications

#### `notifications`
Purpose: internal admin-facing notification feed (new donation, new volunteer application, new help request — SRS FR-27) plus future SMS/email queue readiness (Section 12). Follows the standard Laravel polymorphic notifiable pattern.

| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | CHAR(36) | No | — | PK, UUID (avoids sequential-ID guessing on a user-facing notification feed) |
| type | VARCHAR(150) | No | — | notification class/type identifier |
| notifiable_type | VARCHAR(150) | No | — | polymorphic recipient class, typically `User` |
| notifiable_id | BIGINT UNSIGNED | No | — | polymorphic recipient id |
| data | JSON | No | — | notification payload (message, link, icon) |
| read_at | TIMESTAMP | Yes | NULL | |

Meta: PK `id` (UUID) · Indexes: `idx_notifications_notifiable` (`notifiable_type`,`notifiable_id`) · Soft Delete: No · Timestamps: created_at, updated_at
Sample Data: `{type:"NewDonationReceived", notifiable_type:"User", notifiable_id:1, data:{"donation_id":501,"amount":1000}, read_at:null}`

---

### Module N — Documents & Reports

#### `documents`
Purpose: downloadable legal/compliance/policy files (Trust Deed, 80G Certificate, Annual Reports) — supports SRS Transparency page.

| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | — | PK |
| title | VARCHAR(200) | No | — | |
| category | ENUM('legal','annual_report','certificate','policy','other') | No | — | |
| media_id | BIGINT UNSIGNED | No | — | FK → media.id |
| is_public | BOOLEAN | No | true | |
| published_date | DATE | Yes | NULL | |
| order_column | SMALLINT UNSIGNED | No | 0 | |

Meta: PK `id` · FK `media_id → media.id` (RESTRICT) · Indexes: `idx_documents_category` · Soft Delete: Yes · Timestamps: created_at, updated_at
Sample Data: `{title:"Annual Report 2025-26", category:"annual_report", is_public:true}`

#### `report_exports`
Purpose: audit trail of financial/operational report exports (UI-UX-BLUEPRINT Admin > Reports) — accountability for who exported what donation data and when.

| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | — | PK |
| user_id | BIGINT UNSIGNED | Yes | NULL | FK → users.id |
| report_type | VARCHAR(100) | No | — | e.g. `donation_summary`, `campaign_performance` |
| filters | JSON | Yes | NULL | applied filter snapshot |
| file_media_id | BIGINT UNSIGNED | Yes | NULL | FK → media.id, generated export file |
| generated_at | TIMESTAMP | No | — | |

Meta: PK `id` · FKs: `user_id → users.id` (SET NULL), `file_media_id → media.id` (SET NULL) · Indexes: `idx_report_exports_user_id` · Soft Delete: No · Timestamps: created_at only
Sample Data: `{user_id:1, report_type:"donation_summary", filters:{"date_from":"2026-06-01","date_to":"2026-06-30"}}`

---

### Module O — Homepage CMS Content

#### `home_sections`
Purpose: editable homepage block content (Hero, Trust Strip, How-It-Works, Final CTA — UI-UX-BLUEPRINT Home Page breakdown) as data rows rather than one bespoke table per section — adding a new homepage section in the future is a new row, not a schema migration.

| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | — | PK |
| section_key | VARCHAR(50) | No | — | e.g. `hero`, `how_it_works`, `final_cta` |
| heading | VARCHAR(200) | Yes | NULL | |
| subheading | VARCHAR(255) | Yes | NULL | |
| content | TEXT | Yes | NULL | |
| media_id | BIGINT UNSIGNED | Yes | NULL | FK → media.id |
| cta_text | VARCHAR(50) | Yes | NULL | |
| cta_url | VARCHAR(255) | Yes | NULL | |
| order_column | SMALLINT UNSIGNED | No | 0 | |
| is_active | BOOLEAN | No | true | |

Meta: PK `id` · FK `media_id → media.id` (SET NULL) · Unique: `uq_home_sections_key` · Soft Delete: No · Timestamps: created_at, updated_at
Sample Data: `{section_key:"hero", heading:"Together, We Restore Dignity.", cta_text:"Donate Now", cta_url:"/donate"}`

#### `impact_stats`
Purpose: the animated stat counters (Meals Served, Families Supported...) reused across Home and Mission & Vision pages.

| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | No | — | PK |
| label | VARCHAR(100) | No | — | |
| value | INT UNSIGNED | No | 0 | |
| suffix | VARCHAR(10) | Yes | NULL | e.g. `+` |
| icon | VARCHAR(50) | Yes | NULL | |
| order_column | SMALLINT UNSIGNED | No | 0 | |
| is_active | BOOLEAN | No | true | |

Meta: PK `id` · Soft Delete: No · Timestamps: created_at, updated_at
Sample Data: `{label:"Meals Served", value:12500, suffix:"+"}`

---

## 3. Relationships — Full Explanation

### One-to-One
| Relationship | Explanation |
|---|---|
| `donation_campaigns` ↔ its featured `seo_meta` row | Enforced via the unique composite (`seo_metable_type`,`seo_metable_id`) — one SEO record per content item, even though the underlying table is technically polymorphic-shared. |
| `org_profile` (singleton) | Conceptually 1:1 with "the organization itself" — no FK needed since there is exactly one row, application-enforced. |

### One-to-Many
| Parent | Child | Explanation |
|---|---|---|
| `activity_categories` | `activities` | Each program category (e.g., Medical Help) contains many detail activities/sub-programs. |
| `activity_categories` | `donation_campaigns` | A campaign optionally belongs to one program area. |
| `donation_campaigns` | `donations` | Each campaign receives many donations; `NULL` campaign_id = general fund. |
| `gallery_categories` | `gallery_items` | Each album contains many photos. |
| `blog_categories` | `blog_posts` | Each category contains many posts. |
| `events` | `event_registrations` | Each event has many RSVPs. |
| `volunteer_applications` | `volunteers` | Each accepted application produces at most one ongoing volunteer record. |
| `menus` | `menu_items` | Each menu contains many items; `menu_items` is additionally self-referencing (`parent_id`) for one level of dropdown nesting. |
| `footer_columns` | `footer_links` | Each footer column contains many links. |
| `users` | `audit_logs` | Each admin user can generate many log entries. |
| `donation_campaigns` | `partners` (where `type='sponsor'`) | A campaign can have many sponsor logos attached. |

### Many-to-Many (via Pivot Tables)
| Table A | Pivot | Table B | Explanation |
|---|---|---|---|
| `roles` | `permission_role` | `permissions` | A role holds many permissions; a permission can belong to many roles (e.g., "view donations" granted to both Super Admin and Finance Viewer). |
| `users` | `role_user` | `roles` | A staff member can hold multiple roles simultaneously (e.g., Content Manager + Finance Viewer for a small team). |
| `blog_posts` | `blog_post_tag` | `blog_tags` | A post can carry multiple tags; a tag applies across many posts — supports blog filtering/related-post logic. |

### Polymorphic Relationships (the schema's core reuse mechanism)
| Table | Polymorphic Column Pair | Attaches To |
|---|---|---|
| `media` | `mediable_type` / `mediable_id` | Activities, Campaigns, Blog Posts, Gallery Items, Team Members, Banners, Testimonials, Donations (receipts), Documents |
| `seo_meta` | `seo_metable_type` / `seo_metable_id` | Activities, Campaigns, Blog Posts, Events, and any future static/dynamic page needing SEO control |
| `audit_logs` | `auditable_type` / `auditable_id` | Any model whose changes must be tracked — currently Donations, Campaigns, Users, Roles, Site Settings |
| `notifications` | `notifiable_type` / `notifiable_id` | Currently only `User`, structurally ready for a future `Volunteer`-targeted notification if a volunteer portal is added |
| `menu_items` | `linkable_type` / `linkable_id` | Optional direct binding of a nav item to an Activity/Campaign/BlogPost instead of a raw URL |

---

## 4. ER Diagram (Text Format)

### 4.1 High-Level Module Relationship Map

```
                                   ┌───────────────┐
                                   │     media     │◄─────────────────────────────┐
                                   │ (polymorphic) │                              │
                                   └───────┬───────┘                              │
                                           │ attaches to                          │ attaches to
        ┌──────────────────────────────────────────────────────────────┐          │
        │                                                              │          │
┌───────▼────────┐   ┌──────────────┐   ┌──────────────┐   ┌──────────▼───────┐  │
│ activity_       │   │ donation_    │   │ blog_posts    │   │ gallery_items    │  │
│ categories 1─n  │──►│ campaigns    │   │  n──1 blog_   │   │  n──1 gallery_   │  │
│ activities      │   │  1─n         │   │  categories   │   │  categories      │  │
└───────┬────────┘   │  donations   │   └──────┬────────┘   └──────────────────┘  │
        │            │  (LEDGER)    │          │                                  │
        │            └──────┬───────┘          │ n─n (blog_post_tag)              │
        │                   │                  ▼                                  │
        │                   │           ┌──────────────┐                          │
        │                   │           │  blog_tags    │                          │
        │                   │           └──────────────┘                          │
        │                   │                                                      │
        │            ┌──────▼───────┐                                             │
        │            │  partners     │                                             │
        │            │ (sponsors)    │                                             │
        │            └──────────────┘                                             │
        │                                                                          │
        │      ┌───────────────┐        ┌──────────────────┐                      │
        └─────►│ volunteer_    │        │      events      │                      │
               │ applications  │        │  1─n event_       │                      │
               │  1─1 promoted │        │  registrations    │                      │
               │  volunteers   │        └──────────────────┘                      │
               └───────────────┘                                                  │
                                                                                    │
┌───────────────┐   ┌───────────────┐   ┌───────────────┐   ┌────────────────┐    │
│    users      │   │    roles      │   │  permissions   │   │  audit_logs    │    │
│  n─n (role_   │──►│               │──►│ (permission_   │   │ (polymorphic   │    │
│  user)        │   │               │   │  role)         │   │  auditable)    │    │
└───────┬───────┘   └───────────────┘   └───────────────┘   └────────────────┘    │
        │                                                                          │
        │ attaches SEO via seo_meta (polymorphic) ────────────────────────────────►│
        │                                                                          │
┌───────▼───────────────────────────────────────────────────────────────────────┐  │
│  Site Infrastructure: settings · menus → menu_items · footer_columns →       │  │
│  footer_links · social_links · banners · home_sections · impact_stats        │──┘
│  help_requests · contact_enquiries · testimonials · faqs · documents         │
│  report_exports · notifications                                              │
└───────────────────────────────────────────────────────────────────────────────┘
```

### 4.2 Detailed ER — Fundraising Core (highest-stakes subsystem)

```
┌────────────────────────┐        ┌───────────────────────────────┐
│  activity_categories    │        │            users              │
│ PK id                  │        │ PK id                          │
└──────────┬─────────────┘        └───────────────┬────────────────┘
           │ 1                                     │ 1
           │                                        │ created_by
           │ n                                      │ n
┌──────────▼─────────────────────────────────────────▼───────────┐
│                      donation_campaigns                         │
│ PK id                                                            │
│ FK activity_category_id → activity_categories.id (SET NULL)      │
│ FK created_by → users.id (SET NULL)                              │
│    title, slug, goal_amount, raised_amount(cached), status       │
└──────────┬────────────────────────────────────────────┬─────────┘
           │ 1                                            │ 1
           │ n                                            │ n
┌──────────▼─────────────┐                     ┌─────────▼─────────┐
│       donations         │                     │      partners      │
│ PK id                   │                     │ PK id               │
│ FK campaign_id (RESTRICT, nullable = general) │ FK campaign_id      │
│    donor_name, amount,  │                     │    (sponsors only)  │
│    payment_status,      │                     └────────────────────┘
│    receipt_number(UQ)   │
│    gateway_transaction_id (UQ) │
└──────────┬──────────────┘
           │ attaches (polymorphic)
           ▼
      ┌─────────┐
      │  media   │  (collection_name = 'receipt_pdf')
      └─────────┘
```

---

## 5. Table Relationship Diagram (Cardinality Summary Table)

| From | To | Cardinality | FK Location | On Delete |
|---|---|---|---|---|
| activity_categories | activities | 1:N | activities.activity_category_id | RESTRICT |
| activity_categories | donation_campaigns | 1:N | donation_campaigns.activity_category_id | SET NULL |
| activity_categories | volunteer_applications | 1:N | volunteer_applications.activity_category_id | SET NULL |
| donation_campaigns | donations | 1:N | donations.campaign_id | RESTRICT |
| donation_campaigns | partners | 1:N | partners.donation_campaign_id | SET NULL |
| volunteer_applications | volunteers | 1:N (practically 1:0..1) | volunteers.volunteer_application_id | SET NULL |
| gallery_categories | gallery_items | 1:N | gallery_items.gallery_category_id | CASCADE |
| blog_categories | blog_posts | 1:N | blog_posts.blog_category_id | RESTRICT |
| blog_posts | blog_tags | N:M | blog_post_tag | CASCADE both sides |
| events | event_registrations | 1:N | event_registrations.event_id | CASCADE |
| menus | menu_items | 1:N | menu_items.menu_id | CASCADE |
| menu_items | menu_items | 1:N (self) | menu_items.parent_id | CASCADE |
| footer_columns | footer_links | 1:N | footer_links.footer_column_id | CASCADE |
| roles | permissions | N:M | permission_role | CASCADE both sides |
| users | roles | N:M | role_user | CASCADE both sides |
| users | audit_logs | 1:N | audit_logs.user_id | SET NULL |
| media | (any content table) | 1:N polymorphic | media.mediable_type/id | N/A (app-managed) |
| seo_meta | (any content table) | 1:1 polymorphic | seo_meta.seo_metable_type/id | N/A (app-managed) |

---

## 6. Media Strategy

**Recommendation: a single central polymorphic `media` table, files stored on Laravel's `public` disk under `storage/app/public/`, symlinked to `public/storage`.** Not raw `uploads/`, not one media table per module.

**Why not `uploads/` (a bare public folder)?** It bypasses Laravel's filesystem abstraction entirely — no disk-swapping later (e.g., moving to S3-compatible object storage when traffic grows, per SRS Section 5 scalability goal), no per-file metadata (alt text, dimensions, ownership), and typically ends up with unpredictable folder structure as different developers add ad-hoc upload logic over time. This is a common "looks fine in a demo, becomes unmaintainable in production" trap.

**Why not per-module image columns (e.g., `activities.image_path`, `campaigns.image_path`)?** It duplicates upload/validation/optimization logic across every module, makes multi-image galleries per entity awkward (a campaign needs one featured image *and* a photo gallery), and can't answer "where else is this image used?" — relevant when an admin tries to delete a photo. It's also the reason most template-generated Laravel apps end up with inconsistent image handling across features.

**Why polymorphic over one-media-table-per-module?** A single `media` table with `mediable_type`/`mediable_id`/`collection_name`:
- Centralizes all upload validation, WebP conversion, and responsive-size generation (SRS Section 15) in one service, not fifteen.
- Lets any future module (Scholarship documents, Certificate images, Membership card photos — Section 12) attach media with zero new schema.
- Supports multiple named collections per entity (`featured_image` vs `gallery` vs `documents` on the same Campaign) via the `collection_name` column.
- Keeps an audit trail of who uploaded what (`uploaded_by`) and enforces `alt_text` as `NOT NULL` at the schema level — accessibility is structurally guaranteed, not just a UI reminder.

**Physical storage layout recommendation:**
```
storage/app/public/
├── media/
│   ├── 2026/07/{uuid-or-hash}.webp     ← date-partitioned to avoid one giant flat directory
├── documents/
│   └── {uuid}.pdf                       ← non-image files (annual reports, certificates)
```
Files are renamed to a random hash/UUID on upload (never trust user-supplied filenames — path traversal and collision risk), with `original_name` preserved only as a display-label column in `media`. Public disk is served via the standard `php artisan storage:link` symlink; production deployment (Nginx) should additionally set long-lived cache headers on the `/storage` path.

---

## 7. SEO Strategy

**Recommendation: a single polymorphic `seo_meta` table, not per-table meta columns.** Every SEO-relevant content type (Activities, Campaigns, Blog Posts, Events) gets exactly one `seo_meta` row via the composite unique constraint on (`seo_metable_type`,`seo_metable_id`).

| Requirement | Storage Approach |
|---|---|
| Meta Title | `seo_meta.meta_title`, capped at 70 chars (Google's practical display limit) — capped at the schema level, not just a UI hint |
| Meta Description | `seo_meta.meta_description`, capped at 160 chars |
| Slug | Lives on the **content table itself** (`activities.slug`, `blog_posts.slug`, etc.), not in `seo_meta` — a slug is a routing/identity concern of the entity, not metadata about it, and needs its own `UNIQUE` index scoped to that table for URL resolution performance |
| Canonical URL | `seo_meta.canonical_url` — needed for cases like a campaign that's also cross-posted, or paginated listing pages needing a canonical to page 1 |
| OG Image | `seo_meta.og_image_media_id` → FK to `media` — falls back to the entity's own featured image at the application layer if left blank, avoiding duplicate uploads |
| Structured Data | `seo_meta.schema_type` (e.g., `Article`, `Event`, `NGO`) selects the JSON-LD template; `seo_meta.structured_data` (JSON) holds any fields that don't map to a standard column, keeping the relational schema stable even as schema.org vocabulary needs vary per content type |

**Why not per-table meta columns (`activities.meta_title`, `blog_posts.meta_title`, ...)?** It works until the 6th content type needs SEO fields, at which point it's six near-identical column sets to maintain, six places to remember to add a new SEO field, and no single place to build a generic "SEO health checklist" admin widget across all content. The polymorphic approach trades a slightly less obvious join for long-term consistency — the right trade for a project explicitly designed to add modules over 5+ years (SRS Section 1).

---

## 8. Settings Strategy

**Recommendation: a hybrid — one generic key-value `settings` table for true scalar configuration, plus dedicated relational tables for anything that is structurally a collection.**

| Data | Storage | Why |
|---|---|---|
| Contact phone, WhatsApp number, default SEO title suffix, mail-from address, payment gateway mode (test/live) | `settings` table (`group`+`key`+`value`) | These are single scalar values — a dedicated table per setting would mean dozens of one-row tables, which is worse normalization theater, not better design |
| Footer link columns, navigation menu items, social links, homepage banners | **Dedicated relational tables** (`footer_columns`/`footer_links`, `menus`/`menu_items`, `social_links`, `banners`) | These are *collections that grow* (an admin adds a 5th footer link, a 3rd banner) — forcing them into a flat key-value table would require encoding array structure into a single `TEXT`/`JSON` value, losing the ability to index, order, or query them relationally. A generic settings table is the wrong tool once "how many rows" is itself dynamic. |

**Rule of thumb applied throughout this schema:** *if an admin will ever click "Add Another," it's a table; if there is exactly one instance of the value organization-wide, it belongs in `settings`.*

---

## 9. Audit Log Strategy

**Recommendation: a single polymorphic `audit_logs` table, populated automatically via model observers/events (application-layer concern, out of scope for this document) rather than manual logging calls scattered through controllers.**

- **What gets logged:** create/update/delete/status-change events on all financially or access-sensitive models — `donations`, `donation_campaigns`, `users`, `roles`, `permission_role`, `settings`, `help_requests` (status changes only, to avoid logging the sensitive content itself twice). Purely cosmetic content changes (a blog post typo fix) are lower priority and can be excluded or sampled if log volume becomes a concern.
- **What's captured per entry:** actor (`user_id`, nullable to survive user deletion), `action`, the polymorphic target (`auditable_type`/`auditable_id`), a JSON snapshot of changed fields only (`old_values`/`new_values` — not full-row dumps, to keep rows small and diffs readable), plus `ip_address`/`user_agent` for security-incident investigation.
- **Immutability:** `audit_logs` has no `updated_at`/`deleted_at` and no application route ever issues an `UPDATE` or `DELETE` against it — logs are insert-only by design, matching the SRS Section 14 requirement that audit trails must not themselves be tamperable.
- **Retention:** No hard automatic purge recommended for financial-adjacent logs (donations, users, roles); a future archival job (move rows older than N years to cold storage) is a Phase 6 operational concern, not a schema concern today.

---

## 10. Future Scalability — How This Schema Absorbs Each Roadmap Item

| Future Module (SRS Section 13 / this brief) | How the current schema accommodates it without restructuring |
|---|---|
| **Multiple Branches** | Add one new `branches` table + a nullable `branch_id` FK on `donation_campaigns`, `events`, `activities`, `volunteers`. Every existing query simply adds an optional filter — no existing table is restructured. |
| **Membership System** | New `membership_plans` (plan tiers) + `memberships` (member ↔ plan, start/end dates) tables, following the same pattern as `volunteer_applications` → `volunteers`. |
| **Online Membership Payments** | New `membership_payments` table modeled directly on the proven `donations` ledger pattern (append-only, gateway fields, receipt_number) — the financial-transaction design is already reusable, not donation-specific in spirit. |
| **Blood Donation Camps / Medical Camps** | Already anticipated: `events.event_type` discriminator (`medical_camp`, `blood_donation_camp`) plus `event_registrations` for sign-ups. A specialized `camp_details` extension table (blood group requirements, doctor names) can be added later as a 1:1 companion to `events` without touching the base table. |
| **Scholarship Programs** | New `scholarship_programs` + `scholarship_applications` tables, reusing the `activity_categories` linkage pattern and the polymorphic `media`/`seo_meta`/`audit_logs` infrastructure automatically. |
| **Volunteer Attendance** | New `volunteer_attendances` table (`volunteer_id` FK, `event_id` FK, `check_in_at`, `check_out_at`) — only possible cleanly because `volunteers` was already split from `volunteer_applications` (Module I rationale). |
| **SMS Notifications / Email Campaigns** | New `communication_campaigns` (message template, audience filter) + `communication_logs` (per-recipient send status) tables; the existing `notifications` table's polymorphic `notifiable` pattern extends naturally to donors/volunteers as recipient types, not just `users`. |
| **Online Certificates** | New `certificates` table, polymorphic `certifiable_type`/`certifiable_id` (issuable to a `Volunteer`, `Donor`, or event participant), `certificate_number` unique, linking to `media` for the generated PDF — same reuse pattern as receipts. |
| **Reports Dashboard** | Primarily a read/aggregation concern over existing tables (`donations`, `volunteer_applications`, `help_requests`); `report_exports` already provides the audit trail for generated reports. No new core tables anticipated beyond possibly a `report_templates` config table. |
| **API Integration / Mobile App** | Schema is already API-friendly: every table uses surrogate integer PKs, consistent naming, and soft-deletes/timestamps that translate directly to REST/JSON resource conventions. Recommend Laravel Sanctum-issued personal access tokens for admin-mobile use cases — this is additive (a `personal_access_tokens` table in the standard Sanctum package shape) and touches no existing table. |

---

## 11. Database Best Practices Applied

- **InnoDB + utf8mb4/utf8mb4_unicode_ci** everywhere — transactional integrity and correct multilingual sort/comparison (Section 1).
- **`DECIMAL(12,2)` for all money fields**, never `FLOAT`/`DOUBLE` — floating-point rounding errors are unacceptable in a donation ledger.
- **Every foreign key is indexed** (MySQL does this automatically for InnoDB FKs, but explicitly noted here as a requirement to verify at migration time, especially for composite polymorphic indexes).
- **`ON DELETE` behavior chosen deliberately per relationship**, not defaulted blindly to CASCADE: financial/legal-adjacent parents (`donation_campaigns`, `media` referenced by `documents`) use `RESTRICT` so a careless delete can't silently orphan or erase money-relevant history; pure UI-structure children (`menu_items`, `gallery_items`, pivot rows) use `CASCADE` since their parent's deletion legitimately means "these no longer make sense either."
- **ENUM used only for small, genuinely stable value sets** (`payment_status`, `status` workflow columns) where adding a new value is a rare, deliberate schema change anyone would want to review; anything an admin should be able to add themselves (categories, tags, roles) is a real table instead.
- **Soft deletes applied selectively, not blanket** — content/media tables get them (recoverable mistakes); pivot tables, logs, and the donations ledger explicitly do not (Section 0).
- **JSON columns reserved for genuinely variable-shape data** (`audit_logs.old_values`, `seo_meta.structured_data`, `settings.value` when `type='json'`) — never used as a substitute for proper columns/relations where the shape is actually fixed and known in advance.
- **All monetary and status-transition writes intended to run inside DB transactions** at the application layer (e.g., inserting a `donation` row and updating the parent campaign's `raised_amount` cache must succeed or fail together) — a schema-level note for the implementation phase, not enforceable in DDL itself, but critical enough to flag now.
- **No `SELECT *` assumption baked into design** — every table's column list above is deliberately narrow (no speculative "just in case" columns), keeping row size small and indexes effective.
- **Composite unique constraints used instead of application-only uniqueness checks** wherever correctness truly depends on it (`seo_meta` owner uniqueness, `donations.gateway_transaction_id`, `settings.group`+`key`) — race conditions under concurrent requests (e.g., a double-submitted payment webhook) are only safely prevented at the database constraint level, not in PHP.

---

## Next Steps

This document defines the full relational schema, reuse patterns (media/SEO/audit polymorphism), and future-proofing rationale — still no SQL or code. The logical next planning step is **Phase 4: API & Application Architecture Planning** — defining the Laravel-specific layering (Controllers → Form Requests → Service classes → Repositories/Eloquent, event/observer wiring for the audit log and campaign `raised_amount` cache, queue job design for payment webhooks and email/receipt delivery, and the route/module folder structure) that will consume this schema. That remains an architecture document, not implementation — actual migrations and models would follow only after that plan is reviewed and approved.
