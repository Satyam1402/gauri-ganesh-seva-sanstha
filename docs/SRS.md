# Software Requirement Specification (SRS)
## Gauri Ganesh Seva Sanstha — NGO Web Platform

**Document Version:** 1.0
**Status:** Draft for Stakeholder Review
**Prepared By:** Technical Architecture Team
**Date:** July 2026

---

## 1. Project Vision

Gauri Ganesh Seva Sanstha (GGSS) requires a digital platform that does more than describe the organization — it must actively convert visitor trust into donations, volunteer sign-ups, and requests for help, while giving the organization's admin staff a reliable back-office to run day-to-day operations (campaigns, beneficiaries, volunteers, receipts).

The platform is being built as a **long-lived institutional asset**, not a marketing brochure. It will:

- Be the primary trust anchor for donors deciding whether to give money to an unfamiliar NGO.
- Serve as an operational system of record for campaigns, donations, and volunteer activity — not just a public-facing site.
- Be architected so that new modules (e.g., a donor portal, a mobile app API, a CRM integration) can be added over the next 5+ years without rewriting the core.
- Meet the bar of a regulated fundraising entity: auditable donation records, receipt generation, and data protection — because NGOs handling money are held to a higher scrutiny standard than typical brochure sites.

**Why this framing matters:** every architectural decision below (service-layer separation, admin audit trails, receipt numbering, SEO structure) is derived from this vision. A demo-quality site would optimize for "looks good in a screenshot"; this SRS optimizes for "still maintainable and trustworthy in year 3."

---

## 2. Project Goals

| # | Goal | Success Signal |
|---|------|-----------------|
| G1 | Build donor trust quickly for first-time visitors | Low bounce rate on homepage, high scroll depth on Impact/Transparency sections |
| G2 | Maximize completed donations | High donation-form completion rate, low drop-off at payment step |
| G3 | Recruit and manage volunteers | Volunteer applications submitted, admin able to triage them |
| G4 | Provide a dignified channel for people seeking help | "Request Help" submissions handled without public exposure of applicants' personal data |
| G5 | Rank organically for local + cause-based searches | Search visibility for terms like "NGO in [city]", "food donation [city]", "Ganesh Utsav seva" |
| G6 | Give non-technical admin staff full control of content | Admin can publish a campaign or update impact numbers without developer involvement |
| G7 | Be fast and usable on low-end Android devices / weak networks | Good Core Web Vitals on 3G/4G, since much of the target donor/volunteer base is mobile-first in India |
| G8 | Be secure and compliant with data-handling expectations | No plaintext sensitive data, proper auth, audit logs on financial actions |

---

## 3. User Personas

### 3.1 Donor — "Anita, 34, Working Professional"
Wants to donate for a specific cause (e.g., Ganesh Utsav, food drive) quickly, sees a website once via WhatsApp/Instagram, decides in under 2 minutes whether the NGO is legitimate. Needs: proof of legitimacy (registration numbers, real photos, transparent fund usage), a fast one-page donation flow, and an email receipt for tax purposes (80G).

### 3.2 Volunteer — "Rohit, 22, College Student"
Wants to contribute time, not money. Browses on mobile. Needs: a clear list of ongoing programs, a simple application form, and confirmation that someone will follow up.

### 3.3 Person Seeking Help — "Sunita, 45, Local Resident"
May have limited digital literacy, potentially using a shared/basic phone. Needs: a simple, low-friction "Request Assistance" form (food/medical/education/clothes), reassurance of privacy and dignity, and a phone number/WhatsApp fallback for those who can't use forms confidently.

### 3.4 Organization Admin — "Suresh, 50, Trustee / Office Coordinator"
Not a developer. Manages campaigns, uploads photos of distribution drives, reviews help requests and volunteer applications, and needs to produce donation reports for trustee meetings and compliance filings. Needs: a dashboard that is simpler than "generic Laravel admin," with plain-language labels and safe guardrails against accidental data loss.

### 3.5 Visitor / Media / Government Body
May be a journalist, CSR partner from a corporate, or government official verifying registration/compliance. Needs: an About/Transparency/Registration page with certificate numbers (Trust Registration, 80G, 12A, FCRA if applicable), and clear organizational leadership info.

---

## 4. Functional Requirements

### 4.1 Public Website

| ID | Requirement |
|----|-------------|
| FR-01 | Visitors can browse programs (Food, Education, Medical, Clothes, Welfare) each as its own detail page |
| FR-02 | Visitors can view and filter active/past Donation Campaigns |
| FR-03 | Visitors can donate to a specific campaign or a general fund via an online payment gateway |
| FR-04 | Donors receive an automated email receipt/acknowledgment after successful donation |
| FR-05 | Visitors can submit a Volunteer Application with basic details and area of interest |
| FR-06 | Visitors can submit a "Request Help" form specifying category (food/medical/education/clothes/other) |
| FR-07 | Visitors can view an Impact/Gallery section with photos and stats from past drives |
| FR-08 | Visitors can read Blog/News/Events updates |
| FR-09 | Visitors can contact the org via a Contact form, phone, WhatsApp link, and physical address/map |
| FR-10 | Visitors can subscribe to a newsletter (optional module) |
| FR-11 | All forms must have client + server-side validation and spam protection (honeypot/Turnstile) |

### 4.2 Donation Module (Core)

| ID | Requirement |
|----|-------------|
| FR-12 | Support one-time and (future-ready) recurring donations |
| FR-13 | Support donation to a specific campaign or "Where Most Needed" |
| FR-14 | Integrate a payment gateway suitable for Indian NGOs (e.g., Razorpay) supporting UPI, cards, netbanking |
| FR-15 | Generate a unique, sequential, auditable Donation Receipt Number per transaction |
| FR-16 | Store donor details (name, email, phone, PAN optional for 80G) linked to each transaction |
| FR-17 | Admin can view, search, and export all donation records with filters (date range, campaign, status) |
| FR-18 | Handle payment gateway webhooks idempotently to avoid duplicate/failed-state receipts |
| FR-19 | Failed/pending transactions must be visibly distinguished from successful ones in reporting |

### 4.3 Admin Panel

| ID | Requirement |
|----|-------------|
| FR-20 | Role-based access: Super Admin, Content Manager, Finance/Donation Viewer |
| FR-21 | CRUD for Campaigns, Programs, Gallery items, Blog posts, Team/Trustee profiles, Testimonials |
| FR-22 | Review queue for Volunteer Applications and Help Requests with status workflow (New → In Review → Resolved/Rejected) |
| FR-23 | Donation dashboard: totals by campaign, by month, exportable to CSV/Excel |
| FR-24 | Media library with automatic image optimization (WebP conversion, resizing) on upload |
| FR-25 | Audit log for all admin actions on financial and sensitive data |
| FR-26 | Site settings management (contact info, social links, SEO defaults, homepage stats) editable without code deploys |

### 4.4 Notifications

| ID | Requirement |
|----|-------------|
| FR-27 | Email notification to admin on new: donation, volunteer application, help request, contact message |
| FR-28 | Email/PDF receipt to donor after successful donation |
| FR-29 | (Future) SMS/WhatsApp notification integration |

---

## 5. Non-Functional Requirements

| Category | Requirement |
|----------|-------------|
| Performance | Homepage LCP < 2.5s on 4G; all pages pass Core Web Vitals "Good" threshold |
| Scalability | Stateless app layer so it can scale horizontally behind a load balancer if traffic grows (e.g., viral campaign) |
| Maintainability | PSR-12, SOLID, service/repository separation, no business logic in Blade or Controllers directly |
| Availability | Target 99.5% uptime; graceful error pages; queue-based retry for email/payment webhook processing |
| Security | OWASP Top 10 mitigations, encrypted secrets, rate-limited forms, signed webhook verification |
| Data Integrity | Donation ledger must be append-only/auditable; no hard-deletes on financial records (soft deletes + audit log) |
| Accessibility | WCAG 2.1 AA target |
| SEO | Full technical SEO compliance (see Section 16) |
| Portability | Deployable on any standard Linux/Nginx/PHP-FPM/MySQL stack; no vendor lock-in beyond payment gateway |
| Internationalization | English + Marathi/Hindi content-ready architecture (even if only English ships in Phase 1) |
| Browser Support | Latest 2 versions of Chrome, Safari, Edge, Firefox; graceful support for older Android WebView |

---

## 6. Website Features (Public)

- Modern responsive homepage (hero, impact stats counter, active campaigns, programs overview, testimonials, CTA blocks)
- Individual Program pages (Food / Education / Medical / Clothes / Welfare)
- Campaign listing + Campaign detail page with progress bar, donate button, gallery
- Donation flow (amount selection → donor details → payment → receipt/thank-you page)
- Volunteer application flow
- Request-help flow (privacy-respecting, not publicly listed)
- Gallery / Impact stories with lightbox, lazy-loaded WebP images
- Blog/News listing + detail (SEO-optimized, shareable)
- About Us (mission, history, trustees, registration/legal transparency)
- Contact Us (form + map + WhatsApp)
- Transparency page (financial summary, certificates: Trust Deed, 80G, 12A)
- Testimonials/Success stories
- FAQ page
- Privacy Policy, Terms of Use, Refund/Donation Policy (mandatory for payment gateway compliance)

---

## 7. Admin Features

- Secure authenticated dashboard (Laravel Breeze-based, extended with roles/permissions)
- Dashboard overview: total donations (day/month/year), active campaigns, pending help requests, pending volunteer applications
- Campaign management (create/edit/close, set goal amount, cover image, gallery)
- Program content management
- Donation records: list, filter, search, export, manual reconciliation notes
- Volunteer application management with status pipeline
- Help request management with status pipeline and internal notes (not public)
- Blog/News management (WYSIWYG editor, SEO fields per post: meta title, meta description, OG image, slug)
- Media library
- Testimonials management
- Team/Trustee profile management
- Site settings (global SEO defaults, contact details, social handles, footer content)
- User & role management (Super Admin only)
- Audit log viewer

---

## 8. User Journey

### 8.1 Donor Journey
Landing (social/search) → Homepage or Campaign page → Views impact/trust signals → Clicks Donate → Selects amount/campaign → Enters details → Pays via gateway → Sees Thank-You page → Receives email receipt → (Optional) Follow-up impact email later.

### 8.2 Volunteer Journey
Landing → Programs/Get Involved page → Volunteer form → Submits interest area & availability → Confirmation page/email → Admin reviews → Admin contacts volunteer offline (phone/email) → Status updated in admin.

### 8.3 Help-Seeker Journey
Landing (often via word-of-mouth/WhatsApp link) → "Need Help?" page → Selects category → Fills minimal-friction form (or calls/WhatsApps directly) → Confirmation → Admin reviews privately → Admin actions and closes request with internal notes.

### 8.4 Admin Journey
Login → Dashboard overview → Reviews pending items (help requests/volunteers/donations) → Manages content (campaigns/blog/gallery) → Exports donation report for trustee meeting → Manages users/roles as needed.

---

## 9. Complete Website Sitemap (Public)

```
/
├── /about-us
│   ├── /about-us/our-story
│   ├── /about-us/trustees-team
│   └── /about-us/transparency-legal
├── /programs
│   ├── /programs/food-distribution
│   ├── /programs/education-support
│   ├── /programs/medical-help
│   ├── /programs/clothes-distribution
│   └── /programs/social-welfare
├── /campaigns
│   └── /campaigns/{slug}              (Campaign detail + Donate CTA)
├── /donate
│   └── /donate/{campaign-slug?}       (Donation flow)
├── /donate/thank-you
├── /get-involved
│   └── /get-involved/volunteer        (Volunteer application form)
├── /request-help                       (Assistance request form)
├── /gallery
├── /blog
│   └── /blog/{slug}
├── /testimonials
├── /faq
├── /contact-us
├── /privacy-policy
├── /terms-of-use
├── /donation-refund-policy
└── /sitemap.xml, /robots.txt
```

---

## 10. Complete Admin Sitemap

```
/admin/login
/admin/dashboard
/admin/campaigns
├── /admin/campaigns/create
└── /admin/campaigns/{id}/edit
/admin/programs
/admin/donations
├── /admin/donations/{id}
└── /admin/donations/export
/admin/volunteers
├── /admin/volunteers/{id}
/admin/help-requests
├── /admin/help-requests/{id}
/admin/blog
├── /admin/blog/create
└── /admin/blog/{id}/edit
/admin/gallery
/admin/testimonials
/admin/team
/admin/settings
├── /admin/settings/general
├── /admin/settings/seo
└── /admin/settings/social
/admin/users
/admin/roles-permissions
/admin/audit-log
```

---

## 11. Dynamic Modules

| Module | Description |
|--------|-------------|
| Campaigns | Time-bound or ongoing fundraising initiatives with goal, raised amount, media, status |
| Donations | Transaction records linked to campaign + donor, with gateway status and receipt |
| Programs | The core service categories (Food, Education, Medical, Clothes, Welfare) |
| Volunteer Applications | Submitted interest forms with review pipeline |
| Help Requests | Assistance requests with privacy-protected internal handling |
| Blog/News | Articles with categories, tags, SEO metadata |
| Gallery/Media | Organized image sets per event/program, auto-optimized |
| Testimonials | Donor/beneficiary quotes, optionally linked to a campaign |
| Team/Trustees | Org leadership profiles |
| Site Settings | Key-value/config-driven content (contact info, social links, homepage stats, SEO defaults) |
| Users & Roles | Admin authentication and permission management |
| Audit Log | Immutable record of sensitive admin actions |

---

## 12. Static Pages

- Privacy Policy
- Terms of Use
- Donation & Refund Policy
- FAQ (content may be semi-dynamic via CMS but structurally a static page type)
- 404 / 500 error pages (custom-branded)

---

## 13. Future Features (Post Phase-1 Roadmap)

| Feature | Rationale |
|---------|-----------|
| Recurring/subscription donations | Increases donor lifetime value |
| Donor login portal (view own donation history, download 80G certificates) | Reduces admin support burden, builds trust |
| SMS/WhatsApp Business API notifications | Higher engagement in Indian market than email alone |
| Multi-language (Marathi/Hindi) | Broader local reach |
| CSR partner portal for corporates | Enables larger institutional donations |
| Mobile app (API-first backend readiness) | Long-term engagement channel |
| Event/Utsav-specific microsites (e.g., Ganesh Utsav campaign hub) | Seasonal high-traffic campaigns |
| Automated 80G certificate PDF generation | Compliance automation |
| Analytics dashboard with donor cohort insights | Data-driven fundraising strategy |

---

## 14. Security Considerations

- **Authentication:** Laravel Breeze with hashed passwords (bcrypt/argon2), enforced password policy for admin accounts, optional 2FA for Super Admin.
- **Authorization:** Policy/Gate-based role permissions (Super Admin, Content Manager, Finance Viewer) — never trust the frontend to hide unauthorized actions.
- **CSRF/XSS:** Laravel's built-in CSRF tokens on all forms; all Blade output escaped by default; sanitize any rich-text (blog) content on save.
- **SQL Injection:** Eloquent ORM / query builder exclusively; no raw string-concatenated queries.
- **Payment Security:** Never store card data (PCI scope avoided by using gateway-hosted checkout/tokenization); verify payment webhooks via signature verification; treat all payment state transitions as idempotent.
- **Rate Limiting:** Throttle login, donation, and public form submission endpoints to prevent abuse/brute force.
- **Spam/Bot Protection:** Honeypot fields + Cloudflare Turnstile (or equivalent) on public forms.
- **Data Privacy:** Help-request applicant data visible only to authorized admin roles; never rendered on public-facing pages.
- **File Uploads:** Strict MIME/type validation, re-encoding of images on upload, storage outside publicly executable paths.
- **Audit Logging:** All create/update/delete actions on donations, users, and roles logged with actor, timestamp, before/after state.
- **Secrets Management:** All credentials (DB, mail, payment gateway) via `.env`, never committed; environment separation for local/staging/production.
- **Transport Security:** Enforced HTTPS (HSTS), secure cookie flags, same-site cookie policy.
- **Backups:** Automated encrypted database backups with tested restore procedure.

---

## 15. Performance Strategy

- Server-side: OPcache enabled, PHP-FPM tuned, route/config/view caching in production, queue workers (Redis/database) for email and webhook processing so requests aren't blocked.
- Database: Proper indexing on donation/campaign lookups, eager loading to avoid N+1 queries.
- Images: All uploads converted to WebP with responsive `srcset`, lazy-loaded below the fold, served via Laravel Storage with a CDN-ready structure.
- Assets: Tailwind purged/minified CSS, Alpine.js used sparingly (only for interactive islands, not app-wide JS framework overhead), Vite for asset bundling with cache-busted, versioned assets.
- Caching: Full-page or fragment caching for largely-static public pages (About, Programs); cache invalidation tied to admin content updates.
- Monitoring: Application performance monitoring hook-ready (e.g., Laravel Telescope in staging, lightweight APM in production).

---

## 16. SEO Strategy

- **Technical foundation:** Clean semantic URLs (`/campaigns/food-drive-2026`), server-rendered Blade (not SPA) for full crawlability, auto-generated `sitemap.xml`, proper `robots.txt`.
- **Per-page metadata:** Editable meta title/description/OG image per Campaign, Program, and Blog post via admin — never hardcoded.
- **Structured Data:** JSON-LD schema for `NGO`/`Organization`, `Event` (campaigns), `Article` (blog), `BreadcrumbList`.
- **Content architecture:** Keyword-aligned URL/heading structure per program (e.g., "Food Distribution NGO in [City]"), internal linking between related programs/campaigns/blog posts.
- **Local SEO:** Google Business Profile alignment, NAP (Name/Address/Phone) consistency, embedded map, city/locality landing content where relevant.
- **Performance-as-SEO:** Core Web Vitals directly impact ranking — covered by Section 15.
- **Sharability:** Open Graph + Twitter Card tags so campaign links look trustworthy when shared on WhatsApp/social (critical for donation virality).
- **Accessibility-as-SEO:** Proper heading hierarchy, alt text enforcement on all images (mandatory field in admin media upload).

---

## 17. Accessibility Strategy

- Target **WCAG 2.1 Level AA**.
- Semantic HTML5 landmarks (`header`, `nav`, `main`, `footer`), proper heading hierarchy (single H1 per page).
- All interactive elements keyboard-navigable and focus-visible; forms with proper `<label>` associations and error messaging tied via `aria-describedby`.
- Color contrast ratios validated against Tailwind palette choices before finalizing the design system.
- Alt text required (enforced at CMS/model-validation level) for all content images; decorative images marked `aria-hidden`.
- Reduced-motion support: AOS animations respect `prefers-reduced-motion`.
- Accessible, non-color-dependent status indicators (e.g., campaign "Closed" shown with text/icon, not just a red dot).

---

## 18. Technology Justification

| Choice | Why |
|--------|-----|
| **Laravel 12 / PHP 8.3+** | Mature, well-documented ecosystem; built-in queueing, validation, Eloquent ORM, and Breeze auth drastically reduce custom security-sensitive code; long-term maintainability for a project expected to live 5+ years. |
| **MySQL** | Reliable, well-supported on standard Linux VPS hosting common in India; strong tooling for backups/reporting needed for financial data. |
| **Blade (server-rendered) over SPA/React** | Public site is content- and SEO-driven, not app-like; server rendering guarantees crawlability without extra SSR complexity, and keeps time-to-interactive low on weak connections. |
| **Tailwind CSS** | Utility-first approach enables a fully custom, "not-a-template" visual identity while keeping CSS maintainable and consistent via a design-token approach (spacing/colors defined once). |
| **Alpine.js (sparingly)** | Provides interactivity (mobile nav, accordions, form steps) without the overhead/build complexity of a full JS framework — appropriate given the content-first nature of the site. |
| **Laravel Breeze** | Lightweight, unopinionated starter for auth — easy to extend with custom roles/permissions without fighting a heavier scaffold like Jetstream/Fortify's extra abstractions. |
| **Heroicons** | Consistent, MIT-licensed icon set that pairs natively with Tailwind, avoiding icon-font performance/accessibility issues. |
| **AOS (used sparingly)** | Adds premium feel to scroll-based reveals without becoming a performance or accessibility liability if scoped correctly. |
| **Linux VPS + Nginx + PHP-FPM** | Cost-effective, standard, well-understood production stack for a Laravel app of this scale; avoids vendor lock-in of a PaaS. |

---

## 19. Suggested Development Phases

| Phase | Scope | Outcome |
|-------|-------|---------|
| **Phase 0 — Foundation** | Project scaffolding, coding standards setup (PSR-12, Pint, static analysis), CI pipeline, environment configs, base auth (Breeze), role/permission scaffolding | Solid technical base, nothing user-facing yet |
| **Phase 1 — Core Public Site** | Homepage, About, Programs, Static pages, Contact, Blog, Gallery (content-only, no payments yet) | Marketing-ready site, deployable, SEO foundation live |
| **Phase 2 — Donation Engine** | Campaigns module, Donation flow, payment gateway integration, receipt generation, donor emails | Organization can start accepting online donations |
| **Phase 3 — Engagement Modules** | Volunteer application flow, Request-help flow, Testimonials | Full engagement funnel complete |
| **Phase 4 — Admin Panel Maturity** | Full admin CRUD for all modules, dashboard analytics, exports, audit log, roles/permissions UI | Admin fully self-sufficient without developer involvement |
| **Phase 5 — Hardening & Launch** | Security review, performance audit, accessibility audit, SEO audit, load testing, backup/restore drill, go-live | Production launch |
| **Phase 6 — Post-Launch Roadmap** | Items from Section 13 (recurring donations, donor portal, multi-language, etc.) prioritized by actual usage data | Continuous improvement |

---

## 20. Risks and Recommendations

| Risk | Impact | Recommendation |
|------|--------|-----------------|
| Payment gateway compliance/KYC delays (common for NGOs) | Could block Phase 2 launch | Start gateway (e.g., Razorpay) NGO account verification in parallel with Phase 0/1 development, not after |
| Admin staff have low technical literacy | Underused CMS, support burden on developer | Prioritize plain-language admin UI, provide a short admin training/video walkthrough post-launch |
| Legal/registration documents (80G/12A/Trust Deed) not yet digitized | Blocks Transparency page and donor trust content | Request scanned documents early; treat as a content dependency, not a dev blocker |
| Scope creep toward "just add this one more feature" | Delays core launch | Strictly gate new requests into the Phase 6 roadmap unless they block Phases 0–5 |
| Public exposure of help-seeker personal data | Reputational/legal risk, harms vulnerable users | Enforce role-based visibility at the query level (not just UI hiding) for Help Requests; covered in Section 14 |
| Single-developer/small-team bus factor | Knowledge loss risk | Maintain this SRS plus inline architectural decision records (ADRs) as the project evolves |
| Traffic spikes during seasonal campaigns (e.g., Ganesh Utsav) | Site slowdown/downtime during highest-value donation window | Load-test before major seasonal campaigns; ensure caching/queueing (Section 15) is in place before Phase 2 goes live seasonally |

---

## Next Steps

This SRS is the reference document for all subsequent architecture and implementation decisions. Recommended next step: review and confirm/adjust Sections 4 (Functional Requirements), 9–10 (Sitemaps), and 14 (Security) with stakeholders, since these three sections drive the database schema and module boundaries that Phase 0 scaffolding will be built around. Once confirmed, the next planning artifact should be the **Database Schema & Module Architecture Design**, followed by the **Development Environment Setup Plan** — still no code, but the concrete technical blueprint this SRS enables.
