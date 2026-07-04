# Coding Standards & Project Foundation
## Gauri Ganesh Seva Sanstha — Phase 1 Sign-Off

**Depends On:** [SRS.md](SRS.md), [UI-UX-BLUEPRINT.md](UI-UX-BLUEPRINT.md), [DATABASE-ARCHITECTURE.md](DATABASE-ARCHITECTURE.md)
**Scope:** What was built in Phase 1 (Project Foundation), and the standards every subsequent module must follow.

---

## 1. What Phase 1 Delivered

- Laravel 12 application scaffolded (PHP 8.2.12 confirmed on this host — see Section 7 for the 8.3 note), MySQL database `ggss_db` created and migrated.
- Packages installed and configured: `spatie/laravel-permission`, `spatie/laravel-medialibrary`, `intervention/image`, `spatie/laravel-sitemap`, `laravel/breeze` (package only — install deferred), `barryvdh/laravel-debugbar` (dev only).
- `.env` / `.env.example` configured: timezone (`Asia/Kolkata`), locale (`en`/`en_IN`), MySQL connection, `public` filesystem disk, database-backed queue/cache/session, daily-rotating logs (14-day retention).
- `app/` layered folder architecture created: `Services`, `Repositories`, `Actions`, `DTOs`, `Enums`, `Traits`, `Helpers`, `Interfaces`, `Jobs`, `Events`, `Listeners`, `Notifications`, `Observers`, `Policies`, `View/Components`, `View/Composers`.
- A generic `RepositoryInterface` + `BaseRepository`, and a `HasSlug` trait (needed by nearly every content model in the DB architecture) — the two pieces of cross-cutting infrastructure worth building before any single module, rather than duplicating per-module.
- Tailwind CSS v4 configured via `@theme` in `resources/css/app.css` with the full design-token set from `UI-UX-BLUEPRINT.md` (colors, radius, shadows, typography) — self-hosted Inter + Fraunces fonts (no third-party font requests).
- Alpine.js + focus-trap plugin wired into `resources/js/app.js`.
- Blade layout hierarchy: `layouts/app.blade.php` (public site), `layouts/guest.blade.php` (auth pages), `layouts/admin.blade.php` (admin shell) + matching `partials/frontend` and `partials/admin`.
- A reusable UI component library in `resources/views/components/ui/`: `button`, `card`, `badge`, `alert`, `input`, `select`, `breadcrumbs`, `modal`, `table`, `container`, `section-heading` — all built as **anonymous** Blade components (see Section 4).
- Error/maintenance pages: `404`, `403`, `419`, `500`, `503` (the last also serves as the `php artisan down` maintenance page) — verified rendering end-to-end.
- Laravel Pint configured (`pint.json`, Laravel preset + stricter import/quote rules) — codebase currently passes `vendor/bin/pint --test` with zero violations.
- One placeholder route/view (`/ → frontend.home`) exists solely to prove the layout/component/token pipeline renders correctly. **It is not the real homepage** — that ships in the Home Content module phase, per the explicit "do not build all pages yet" instruction.

---

## 2. Package Decisions & Why

| Package | Why | Note |
|---|---|---|
| `spatie/laravel-permission` | Industry-standard, battle-tested RBAC with Blade directives (`@role`, `@can`), middleware, and permission caching built in. | **Supersedes** the bespoke `roles`/`permissions`/`role_user`/`permission_role` tables designed in `DATABASE-ARCHITECTURE.md` Module A. Spatie's own migration (`model_has_roles`, `model_has_permissions`, polymorphic) is what will actually ship — reinventing RBAC by hand would be strictly worse for no benefit. `audit_logs` and the `users` table itself remain exactly as designed in Phase 3. |
| `spatie/laravel-medialibrary` | Its `media` table is *already* polymorphic (`model_type`/`model_id`/`collection_name`) — nearly identical in spirit to the bespoke `media` table in Module B — plus it gives WebP/thumbnail conversions, responsive images, and queued processing for free, directly serving the SRS's image-performance goals. | **Supersedes** the custom `media` table design. The `alt_text` `NOT NULL` requirement from Phase 3 is enforced via `custom_properties->alt` validation at the Form Request layer instead of a dedicated column, since Spatie's schema doesn't have one. |
| `intervention/image` | Available for any one-off image manipulation *outside* the media pipeline (e.g., generating a one-time preview thumbnail in a controller/action) — Spatie Media Library's own conversions use `spatie/image` (GD/Imagick) internally, not this package directly. The two don't conflict; they serve different call sites. | Driver confirmed available: PHP `gd` extension is enabled on this host. |
| `spatie/laravel-sitemap` | Directly serves the SRS's SEO strategy (dynamic `sitemap.xml` across Campaigns/Blog/Activities) without hand-rolling XML generation. | Config published to `config/sitemap.php`. |
| `laravel/breeze` | Package installed now (`composer require` only). **`php artisan breeze:install` is deliberately deferred** to the Authentication phase — see Section 6. | Do not run the install command yet. |
| `barryvdh/laravel-debugbar` | Query/route/view profiling during development. | Installed with `--dev`, so it is entirely absent from `composer install --no-dev` in production — no risk of it leaking to production regardless of `APP_DEBUG`. |

**No dedicated "SEO package" (e.g., artesaos/seotools) was installed.** The bespoke polymorphic `seo_meta` table designed in Phase 3 already covers meta title/description/canonical/OG image/structured data better than a generic package would, since it's tailored to this schema. Installing one would fragment where SEO data lives for no gain.

---

## 3. Where Business Logic Lives

Laravel's MVC gives you exactly three built-in places to put code (Model, View, Controller) — and none of them is where business logic belongs once an application grows past a few screens. The layering below exists so that *every* piece of logic has exactly one obvious home:

| Layer | Responsibility | Example |
|---|---|---|
| **Controller** | Receive the HTTP request, delegate to a Service/Action, return a response. Nothing else. | `DonationController@store` calls `RecordDonationAction::execute($dto)` and returns a redirect. |
| **Form Request** (`app/Http/Requests`) | Validation rules and authorization for a single request. | `StoreDonationRequest` validates amount/campaign/donor fields. |
| **Action** (`app/Actions`) | A single, named business operation — the "verb" of the domain (`RecordDonation`, `PromoteVolunteerApplication`, `GenerateDonationReceipt`). Preferred for one-shot operations that don't need the full surface of a service. | `PromoteVolunteerApplicationAction` turns a `VolunteerApplication` into a `Volunteer` row. |
| **Service** (`app/Services`) | Coordinates multiple steps/models for a cohesive domain area, often orchestrating several Actions, Repositories, and external integrations (payment gateway, mail). Preferred when the operation has real internal complexity or reusable sub-steps. | `DonationService` handles the full donate → charge → receipt → notify flow. |
| **Repository** (`app/Repositories`, contracted via `app/Interfaces`) | All Eloquent query logic for a model, behind an interface. Keeps query construction out of Services/Controllers and swappable in tests. | `DonationRepository::successfulByCampaign($campaignId)`. |
| **DTO** (`app/DTOs`) | Typed, immutable data passed between layers instead of raw arrays — makes Action/Service signatures self-documenting. | `DonationData` carrying amount/donor/campaign fields from Request to Action. |
| **Model** | Relationships, casts, scopes, accessors/mutators — data shape, not business rules. | `DonationCampaign::activeCampaigns()` scope is fine; calculating whether a campaign *should* auto-close is not. |
| **Observer** (`app/Observers`) | Reacts to model lifecycle events for cross-cutting concerns — this is specifically how the `audit_logs` writes and the `donation_campaigns.raised_amount` cache update (both flagged in `DATABASE-ARCHITECTURE.md` Section 0/9) get wired, without polluting the Donation model or the Action that created it. | `DonationObserver::updated()` recalculates the parent campaign's cached total inside a DB transaction. |
| **Event + Listener** (`app/Events`, `app/Listeners`) | Decouple side effects from the primary action — "a donation succeeded" is an event; "send a receipt email," "notify the admin," "update analytics" are independent listeners, some queued. | `DonationSucceeded` event → `SendDonationReceipt`, `NotifyAdminOfDonation` listeners. |
| **Job** (`app/Jobs`) | Queued, retryable units of work — anything slow or unreliable (payment webhook processing, PDF receipt generation, email sending) per the SRS's queue-based-retry requirement. | `ProcessPaymentWebhookJob`. |
| **Notification** (`app/Notifications`) | Multi-channel (mail/database/future SMS) user-facing messages. | `DonationReceiptNotification`. |
| **Policy** (`app/Policies`) | Authorization rules for a model — "can this user view/edit/delete this record." | `HelpRequestPolicy::view()` restricts to authorized roles, enforcing the SRS Section 14 privacy requirement at the authorization layer. |
| **View Composer** (`app/View/Composers`) | Injects data that many views need (nav menu, footer settings) without repeating it in every controller. | `FooterComposer` binds `footer_columns`/`social_links` to the footer partial. |

**Rule of thumb:** if you're about to write an `if` statement about *what the business allows*, stop and ask which of the above layers it belongs in — it is never the Controller and never the Blade file.

---

## 4. What Never Belongs in a Controller (or a Blade File)

- **Query building beyond a single, obvious `find()`/`findOrFail()`.** Anything with `where`, joins, or aggregation belongs in a Repository.
- **Payment gateway calls, mail sending, or any external API call.** These belong in a Service/Action/Job, both for testability (mock the Service, not HTTP) and so retries/queueing are possible.
- **Validation logic beyond calling a Form Request.** No manual `if (!$request->amount) ...` in a controller method.
- **Authorization checks written as raw conditionals** (`if ($user->id !== $donation->user_id)`) — these belong in a Policy so they're testable and reusable across web/API/admin contexts.
- **Multi-step orchestration** ("create the donation, then update the campaign total, then send a receipt, then notify the admin") — this is exactly what Services/Actions + Events/Listeners exist for; a Controller doing all four steps inline is untestable and unreusable from a future API/mobile client.
- **Business logic in Blade** — no `@if ($donation->amount > 10000)` style domain rules in a view. If a template needs a computed/derived value, compute it in the Controller/Service/Model accessor and pass a plain value or use a View Composer — the template should only make *presentational* decisions (show/hide, formatting), never *domain* decisions (is this donor a "major donor," should this campaign be considered "urgent").
- **Direct `DB::` facade queries scattered in Controllers/Blade** — if raw query builder is ever needed, it lives inside a Repository method with a name that explains *why*, not inline where it's called.

This is the direct, practical consequence of SOLID here: Controllers depend on Service/Repository **interfaces**, not concrete implementations or raw Eloquent — so a future change (swap the payment gateway, add caching to a repository) never requires touching a controller.

---

## 5. Folder Architecture Reference

```
app/
├── Actions/          One-shot business operations (verbs)
├── DTOs/              Typed data carriers between layers
├── Enums/             PHP 8.1+ native enums for bounded domain values
├── Events/            Domain events (DonationSucceeded, VolunteerPromoted...)
├── Helpers/           Global helper functions (autoloaded via composer.json "files")
├── Http/
│   ├── Controllers/   Thin — delegate only
│   ├── Requests/      Form Request validation/authorization
│   └── Resources/     API/JSON transformation layer
├── Interfaces/        Contracts for Repositories/Services (enables DI + testing)
├── Jobs/               Queued work
├── Listeners/          Event side-effects
├── Notifications/      Mail/database/SMS-ready user messages
├── Observers/           Model lifecycle side-effects (audit log, cached totals)
├── Policies/            Authorization rules per model
├── Repositories/        Eloquent query logic behind Interfaces
├── Services/            Multi-step domain orchestration
├── Traits/              Cross-cutting reusable behaviour (HasSlug, etc.)
└── View/
    ├── Components/      Class-based Blade components (only when logic is needed)
    └── Composers/       Inject shared data into views (nav, footer)

resources/views/
├── layouts/             app.blade.php, guest.blade.php, admin.blade.php
├── partials/
│   ├── frontend/        header, footer
│   └── admin/           sidebar, topbar
├── components/ui/       Anonymous Blade components — the design system
├── frontend/             Public-site pages (built module by module)
├── admin/                 Admin-panel pages (built module by module)
└── errors/                404/403/419/500/503
```

---

## 6. Class-Based vs. Anonymous Blade Components — the Rule Applied

`app/View/Components/UI` was created but left empty in this phase. That's intentional, not an oversight: every component built so far (`button`, `card`, `badge`, `alert`, `input`, `select`, `breadcrumbs`, `modal`, `table`, `container`, `section-heading`) is **pure markup with prop-driven variants** — no PHP computation is needed beyond picking a CSS class string, which Blade's `@props`/`$attributes->class()` already handles inside the view itself. Promoting these to class-based components would add a PHP class per component for zero benefit.

**The rule going forward:** start every component as an anonymous Blade view in `resources/views/components/ui/`. Promote it to a class-based component in `app/View/Components` only when it needs actual PHP logic in its constructor — e.g., a future `<x-ui.money>` component that needs to call `format_inr()` with currency-conversion logic, or a component that queries the database. Don't create a backing class "just in case."

---

## 7. Coding Standards

- **PSR-12**, enforced automatically by **Laravel Pint** (`pint.json` — Laravel preset plus stricter import ordering, no unused imports, single-quoted strings). Run `vendor/bin/pint` before every commit; `vendor/bin/pint --test` currently passes clean across the whole codebase.
- **PHP version:** this host runs **PHP 8.2.12** (Laravel 12 fully supports 8.2+). The SRS specified 8.3+ as an aspiration — if strict 8.3 is required, XAMPP's PHP needs a separate upgrade; nothing in this codebase requires 8.3-only features yet, so it isn't a blocker.
- **Strict typing intent:** all new classes should type-hint constructor/method parameters and return types (as already done in `RepositoryInterface`/`BaseRepository`/`HasSlug`). Prefer constructor property promotion for simple DI.
- **SOLID in practice for this codebase:**
  - *Single Responsibility* — a Controller talks HTTP, a Service/Action talks business rules, a Repository talks persistence. Never blend two of these in one class.
  - *Open/Closed* — new donation payment gateways or notification channels should be addable by implementing an interface, not by branching inside existing classes.
  - *Liskov* — any class implementing `RepositoryInterface` must honor its contract fully (e.g., `findOrFail` really throws, `delete` really returns a bool).
  - *Interface Segregation* — prefer small, focused interfaces per concern over one giant "God" contract.
  - *Dependency Inversion* — Controllers/Services depend on `App\Interfaces\*` contracts, bound to concrete implementations in a Service Provider, never on concrete Repository/Service classes directly.
- **Naming conventions:** mirror `DATABASE-ARCHITECTURE.md` Section 1 — `snake_case` for database columns, `StudlyCase` for classes, `camelCase` for methods/variables, `kebab-case` for Blade view file names and routes.
- **No business logic in Blade or Controllers** — Sections 3–4 above are binding for every future module, not just a suggestion.
- **Every new content-bearing model expected to use `media`/`seo_meta`-style polymorphic relations** (via Spatie Media Library's `InteractsWithMedia` and a future lightweight `HasSeo` trait) rather than one-off columns — consistent with the reuse strategy in `DATABASE-ARCHITECTURE.md` Sections 6/9.
- **Testing:** Pest/PHPUnit is present (`phpunit.xml`, `tests/`); each Action/Service introduced in future phases should get a corresponding Feature or Unit test — this isn't optional scope, it's part of "done" for a module.
- **Git:** this directory is **not yet a git repository**. Recommend initializing git and committing this foundation as the first commit before starting the next module, so every subsequent phase is reviewable as its own diff — happy to do this now if you'd like, but it wasn't assumed unprompted since commit/branch conventions are your call.

---

## 8. Deferred to the Next Phase (Authentication & Users)

- Running `php artisan breeze:install blade` and restyling its scaffolded views to use `layouts/guest.blade.php` and the `x-ui.*` component library instead of Breeze's defaults.
- Extending the `users` migration with the columns designed in `DATABASE-ARCHITECTURE.md` Module A (`phone`, `avatar_media_id`, `status`, `last_login_at`).
- Publishing and running the `spatie/laravel-permission` and `spatie/laravel-medialibrary` migrations is **already done** in this phase (tables exist), but seeding actual roles/permissions and wiring `HasRoles`/`InteractsWithMedia` onto the `User` model is Authentication-phase work.
- Building the admin panel's real navigation, controllers, and Policies referenced (as placeholder `href="#"` links) in `partials/admin/sidebar.blade.php`.

---

## Next Steps

Phase 1 is complete and verified end-to-end (migrations ran on MySQL, assets build, the placeholder page renders with real HTTP 200, error/maintenance pages render correctly, Pint passes clean). The next module, per your instruction to build "module by module," is **Authentication & User Management** — running `breeze:install`, extending the `users` table, and wiring Spatie Permission roles. Say the word and we'll start that phase.
