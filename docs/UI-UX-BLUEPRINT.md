# UI/UX Design Blueprint
## Gauri Ganesh Seva Sanstha — Product Design & Experience Specification

**Document Version:** 1.0
**Status:** Draft for Stakeholder Review
**Depends On:** [SRS.md](SRS.md)
**Phase:** 2 — Product Design & UI/UX Planning (no code)

---

## 1. Design Philosophy

**Core principle: "Quiet Confidence."** A charity asking for money has to earn trust visually before it earns it emotionally. Loud, template-style NGO sites (stock-photo carousels, rainbow gradients, generic Bootstrap cards) actually *erode* trust because donors have learned to associate that look with low-effort or even fraudulent organizations. The design language here borrows from **modern corporate/fintech websites** (restrained color, generous whitespace, confident typography) and applies it to an emotional cause — the combination reads as "this organization is run competently AND cares deeply," which is precisely the dual signal a donor needs before entering payment details.

Five design principles govern every screen in this document:

| Principle | What it means in practice |
|---|---|
| **Clarity over decoration** | Every visual element must earn its place. No gradients, icons, or animations "because it looks nice" — each ties to a section's purpose. |
| **One idea per section** | Each homepage/landing section makes exactly one point (trust, impact, urgency, action). Mixing messages dilutes conversion. |
| **Emotion through photography, not illustration** | Real, high-quality photos of actual beneficiaries/drives carry more trust than stock illustrations or icon-heavy hero sections common in template sites. |
| **Generous whitespace = premium signal** | Cramped layouts read as "cheap template." Breathing room around content is a deliberate luxury/trust cue, same technique used by Charity: Water, Room to Read.
| **Frictionless path to Donate/Help/Volunteer** | Never more than one primary click away from any page, at any scroll position (via sticky header CTA). |

---

## 2. Color Palette

**Rationale:** Saffron/marigold is the natural accent for an Indian NGO with a Ganesh Utsav association — it signals warmth and cultural rootedness — but it must be used as an *accent*, not a dominant color, or the site reads as festive/decorative rather than trustworthy. The base palette is a deep, quiet teal-green (associated with growth, care, stability, and — importantly — distinct from the "charity red" and "corporate blue" overused by competitors) paired with warm neutrals instead of stark white/gray, which feels less clinical and more human.

| Role | Token | Hex | Usage |
|---|---|---|---|
| **Primary** | `primary-700` | `#0F5C4E` | Deep teal-green — header, primary buttons, links, brand anchor |
| Primary (hover/dark) | `primary-800` | `#0B473C` | Button hover/active states |
| Primary (light) | `primary-100` | `#DCEEE9` | Subtle backgrounds, selected states, tags |
| **Secondary** | `secondary-600` | `#8A5A2B` | Warm terracotta-brown — supports earthy/seva feel, used for secondary buttons/dividers |
| **Accent** | `accent-500` | `#E8912D` | Marigold/saffron — used sparingly for CTAs, highlights, donation progress bars, festival campaign badges |
| Accent (light) | `accent-100` | `#FDECD3` | Badge backgrounds, highlight chips |
| **Background** | `bg-base` | `#FBF9F6` | Warm off-white page background (not stark `#FFFFFF`) |
| **Surface** | `surface-white` | `#FFFFFF` | Cards, modals, form panels sitting above background |
| Surface (alt) | `surface-muted` | `#F3EFE8` | Alternating section backgrounds for visual rhythm |
| **Text (primary)** | `text-900` | `#1E2422` | Headings, high-emphasis body text |
| Text (secondary) | `text-600` | `#54615C` | Paragraph/body copy |
| Text (muted) | `text-400` | `#8B968F` | Captions, placeholders, metadata |
| Text (inverse) | `text-inverse` | `#FFFFFF` | Text on dark/primary backgrounds |
| **Success** | `success-600` | `#1E8A5F` | Successful donation, form success states |
| **Warning** | `warning-600` | `#C9821A` | Pending states (e.g., help request under review) |
| **Error** | `error-600` | `#C4442C` | Validation errors, failed payment states |
| Border | `border-subtle` | `#E4DFD6` | Card borders, dividers, input borders |

All pairings above are chosen to clear **WCAG AA contrast (4.5:1 for body text, 3:1 for large text/UI components)** — to be re-verified against final Tailwind config during implementation (see Section 17 of the SRS).

---

## 3. Typography

**Rationale:** A serif/humanist-sans pairing is used instead of an all-geometric-sans system (like default Bootstrap/Inter-everywhere templates) because a serif display font on headings signals editorial, institutional credibility — think annual-report or university-brochure quality — while a clean humanist sans keeps body copy highly legible on mobile.

| Role | Font | Fallback Stack | Notes |
|---|---|---|---|
| **Heading Font** | Fraunces (or Lora as alternative) | `"Fraunces", Georgia, serif` | Warm serif with character; used for H1–H3 and pull-quotes only |
| **Body Font** | Inter | `"Inter", -apple-system, sans-serif` | Body copy, UI labels, forms, buttons — optimized for screen legibility at small sizes |

### Font Size Scale (type scale ratio ~1.25, mobile-first with fluid clamp)

| Token | Size (mobile → desktop) | Usage |
|---|---|---|
| `text-xs` | 12px → 12px | Captions, metadata, badges |
| `text-sm` | 14px → 14px | Form labels, helper text |
| `text-base` | 16px → 16px | Body copy (never below 16px on mobile — prevents iOS auto-zoom) |
| `text-lg` | 18px → 20px | Lead paragraphs, card descriptions |
| `text-xl` | 20px → 24px | H4 / small section headings |
| `text-2xl` | 24px → 30px | H3 |
| `text-3xl` | 30px → 38px | H2 |
| `text-4xl` | 36px → 48px | H1 (inner pages) |
| `text-5xl` | 42px → 64px | Homepage Hero H1 |

### Font Weights
- Headings: 600 (Semibold) default, 500 (Medium) for large hero display text (serif at large sizes doesn't need heavy weight)
- Body: 400 (Regular); 500 (Medium) for emphasis/labels
- Buttons: 600 (Semibold)

### Line Heights
- Headings: 1.15–1.25 (tight, confident)
- Body copy: 1.6–1.7 (generous, easy reading for longer program/blog content)
- UI labels/buttons: 1.2

---

## 4. Spacing System

**Rationale:** An 8px base unit keeps all spacing decisions systematic rather than ad-hoc (a common template smell is inconsistent padding across sections). A slightly expanded scale at the top end supports the "generous whitespace = premium" principle from Section 1.

| Token | Value | Typical Use |
|---|---|---|
| `space-1` | 4px | Icon-to-text gaps |
| `space-2` | 8px | Tight inline spacing |
| `space-3` | 12px | Form field internal padding |
| `space-4` | 16px | Default component padding |
| `space-6` | 24px | Card padding |
| `space-8` | 32px | Gap between related components |
| `space-12` | 48px | Section internal spacing (mobile) |
| `space-16` | 64px | Section internal spacing (desktop) |
| `space-24` | 96px | Section-to-section vertical rhythm (desktop) |
| `space-32` | 128px | Major page-region separation (hero to next section, desktop) |

---

## 5. Border Radius

**Rationale:** A moderate, consistent radius (not the very rounded "bubbly" style typical of template sites, not sharp corporate rectangles either) supports the "premium but warm" middle ground.

| Token | Value | Usage |
|---|---|---|
| `radius-sm` | 6px | Badges, tags, inline chips |
| `radius-md` | 10px | Buttons, form inputs |
| `radius-lg` | 16px | Cards, panels |
| `radius-xl` | 24px | Large feature cards, modals, image containers |
| `radius-full` | 9999px | Avatar images, pill buttons, status dots |

---

## 6. Shadow System

**Rationale:** Shadows are used to convey elevation hierarchy only — never decoratively. Overuse of heavy drop-shadows is a common "cheap template" tell; this system uses soft, low-opacity, warm-toned shadows (not pure black) matching the warm neutral palette.

| Token | Value (approx.) | Usage |
|---|---|---|
| `shadow-xs` | `0 1px 2px rgba(30,36,34,0.04)` | Inputs, subtle separation |
| `shadow-sm` | `0 2px 6px rgba(30,36,34,0.06)` | Default cards at rest |
| `shadow-md` | `0 8px 20px rgba(30,36,34,0.08)` | Hover state on cards, dropdowns |
| `shadow-lg` | `0 16px 40px rgba(30,36,34,0.12)` | Modals, popovers |
| `shadow-focus` | `0 0 0 3px rgba(15,92,78,0.35)` | Keyboard focus ring (accessibility-critical, replaces default browser outline consistently) |

---

## 7. Button Styles

| Variant | Appearance | Usage |
|---|---|---|
| **Primary** | Solid `primary-700` bg, white text, `radius-md`, `shadow-sm` → `shadow-md` on hover, slight scale (1.0 → 1.02) on hover | Main CTAs: "Donate Now", "Submit Application" |
| **Accent (High-intent)** | Solid `accent-500` bg, `text-900` or white text depending on contrast check | Reserved exclusively for donation-related CTAs to create a consistent "this button = giving money" association site-wide |
| **Secondary** | Transparent bg, `primary-700` 1.5px border, `primary-700` text; fills solid on hover | "Learn More", secondary actions alongside a primary button |
| **Ghost/Tertiary** | No border/bg, `primary-700` text, underline on hover | Inline text-links-as-buttons, nav CTAs |
| **Danger** | Solid `error-600` bg, white text | Admin destructive actions only (delete, reject) |
| **Disabled** | 40% opacity, no hover/scale transform, `cursor-not-allowed` | Form submit while validating/loading |

**Sizing:** `sm` (36px height, for tables/compact UI), `md` (44px height, default — meets 44px minimum touch target), `lg` (52px height, for hero/donation CTAs).

All buttons: 600 font-weight, `radius-md`, transition `150ms ease-out` on hover/focus states, visible `shadow-focus` ring on keyboard focus (never `outline: none` without replacement — accessibility non-negotiable per SRS Section 17).

---

## 8. Card Design

**Rationale:** Cards are the primary content container across Programs, Campaigns, Blog, Gallery, and Admin — consistency here is what makes the whole site feel like "one system" rather than a template stitched from sections.

- **Structure:** `surface-white` background, `radius-lg`, `shadow-sm` at rest, `1px` `border-subtle` border, `space-6` internal padding.
- **Hover (interactive cards only):** elevate to `shadow-md`, image (if present) scales subtly (1.0 → 1.04) inside an `overflow-hidden` container — never the whole card jumps/scales, which feels cheap.
- **Image position:** Top of card, `radius-lg` matched only on top corners, fixed aspect ratio (16:10 for Campaigns/Blog, 1:1 for Team/Testimonial avatars) to prevent layout shift (CLS).
- **Campaign Card specific:** includes a donation progress bar (accent-500 fill on `bg-base` track), raised amount + goal amount in `text-sm` medium weight, category badge (top-left overlay on image, `accent-100` bg / `text-900`).
- **Content hierarchy inside card:** Category badge → Heading (`text-lg`, semibold) → 2-line clamped description (`text-sm`, `text-600`) → metadata row (date/location) → CTA (ghost button, bottom-aligned via flex, so all cards in a row align regardless of content length).

---

## 9. Form Design

**Rationale:** Forms (Donate, Volunteer, Request Help, Contact) are the highest-stakes UI in the product — every friction point here is a lost donor, volunteer, or a beneficiary who gives up. Design errs toward large touch targets, inline validation, and reassurance copy.

- **Field structure:** Label (`text-sm`, medium, `text-900`) above input — never placeholder-as-label (accessibility + usability failure common in template sites). Helper text (`text-xs`, `text-400`) below when needed.
- **Input style:** `surface-white` bg, `1px border-subtle`, `radius-md`, 44px min height, `space-4` horizontal padding. Focus state: border becomes `primary-700`, `shadow-focus` ring applied.
- **Validation:** Inline, on-blur (not only on-submit). Error state: `error-600` border + small error icon + message below field in `error-600` text, `aria-describedby` linked for screen readers.
- **Multi-step forms (Donation flow):** Stepper indicator at top (Amount → Details → Payment → Confirmation), progress persists on back navigation, never lose entered data on step-back.
- **High-stakes reassurance microcopy:** Under payment step: "🔒 Secure payment powered by [Gateway]. We never store your card details." Under Request-Help form: "Your information is kept confidential and only visible to our verification team."
- **Buttons:** Full-width on mobile, right-aligned auto-width on desktop; primary submit button always visually dominant over any "cancel/back" ghost button.
- **Select/Radio for donation amount:** Preset amount chips (e.g., ₹500 / ₹1,000 / ₹2,500 / ₹5,000) as large tappable pill buttons + one "Custom Amount" input — reduces decision friction versus a bare input field.

---

## 10. Icon Style

**Rationale:** Heroicons (per SRS tech stack) is used in **Outline** style for navigational/UI icons (lighter visual weight, matches the restrained aesthetic) and **Solid** style reserved for small high-emphasis moments (active nav state, filled badge icons, stat highlights) — this two-tier system prevents the "icon soup" look of template sites where every icon has identical heavy weight.

- Icons are always paired with text labels in navigation and forms (never icon-only for primary actions) — accessibility + clarity.
- Icon color always inherits from `text-600` or `primary-700` context — never introduces a new arbitrary color per icon.
- Stat/impact icons (e.g., "10,000 meals served") use a custom simple line-icon set consistent with Heroicons' stroke width (1.5px) for visual unity, housed in a soft circular `primary-100` badge behind them.

---

## 11. Image Style

**Rationale:** Photography is the single biggest lever for the "emotionally engaging yet premium" goal — see Section 1. Poor image treatment (mismatched aspect ratios, oversaturated stock photos, harsh crops) is the fastest way to look like a template.

- **Source:** Real photography from actual GGSS drives/events only for Hero, Impact, Gallery, and Testimonial sections. Generic stock photography explicitly avoided — donors increasingly recognize and distrust stock imagery on charity sites.
- **Treatment:** Consistent, subtle warm color grading applied across all photos (slightly lifted shadows, warm white balance) so images shot on different phones/cameras still feel like one cohesive brand — critical since NGO photo contributions are rarely professionally shot.
- **Aspect ratios:** Locked per placement (Hero: 16:9 or custom crop per breakpoint; Cards: 16:10; Avatars: 1:1) — always via CSS `aspect-ratio` + `object-fit: cover`, never stretched.
- **Format & performance:** All images served as WebP with responsive `srcset`, lazy-loaded below the fold (native `loading="lazy"`), blur-up low-quality placeholder while loading to avoid layout jank — ties directly to SRS Section 15 (Performance) and Section 16 (SEO).
- **Overlays:** Hero images use a subtle gradient overlay (`primary-900` at 30–50% opacity, bottom-to-top) to guarantee text legibility without flattening the photo — never a flat solid-color overlay, which looks template-generic.
- **Captions:** Gallery and impact photos include short human captions ("Food distribution drive, Pune — March 2026") — specificity signals authenticity.

---

## 12. Animation Guidelines

**Rationale:** Motion should feel like quiet confirmation, not spectacle. Per SRS Section 17, all motion respects `prefers-reduced-motion`.

- **Scroll reveals (AOS):** Used only for section-level entrances (fade-up, 400–600ms, subtle 16px translate) — applied once per section on first scroll into view, never re-triggering repeatedly, never on every individual card/icon (which creates a distracting "waterfall" effect common in template sites).
- **Micro-interactions:** Button hover (150ms ease), card hover elevation (200ms ease), input focus ring (instant/100ms) — these are functional, not decorative, so they're excluded from "sparingly" restriction.
- **Counters:** Impact stat numbers (e.g., "12,500 meals served") animate counting up once when scrolled into view — one of the few "delight" moments intentionally included, since it reinforces the trust/impact message.
- **Page transitions:** None (full-page transition libraries add complexity/perf cost disproportionate to benefit for a content site) — standard fast page loads instead.
- **Explicitly avoided:** Parallax scrolling, auto-playing carousels/sliders (accessibility + UX anti-pattern — visitors miss content that auto-advances), typewriter text effects, excessive AOS on every element.

---

## 13. Mobile Design Strategy

Given the personas (Section 3 of SRS — Rohit the student, Sunita the help-seeker) are heavily mobile-first, often on mid/low-end Android devices:

- **Mobile-first breakpoints:** Design and build from 360px viewport up; desktop is an enhancement, not the default.
- **Sticky mobile header:** Compact header with logo + hamburger + a persistent small "Donate" button always visible (never requires scrolling up to find it).
- **Bottom-anchored primary CTA (optional pattern for Campaign/Donate pages):** A slim sticky bottom bar with the Donate CTA, common in high-converting mobile donation flows — keeps the highest-intent action within thumb reach at all times.
- **Touch targets:** Minimum 44×44px per Section 7; adequate spacing between adjacent tappable elements (min 8px) to prevent mis-taps.
- **Simplified navigation:** Full nav collapses into a full-screen slide-in drawer (not a cramped dropdown), large tap-friendly menu items with icons.
- **Forms:** Single-column always on mobile, numeric keyboard triggered for phone/amount fields (`inputmode="numeric"`), no multi-column field layouts.
- **Images:** Smaller, art-directed crops on mobile (not just scaled-down desktop images) so subjects remain legible at small sizes.
- **Performance discipline:** Since this audience may be on 3G/4G, mobile view defers non-critical JS/animations and prioritizes LCP image preloading (ties to SRS Section 15).

---

## 14. Desktop Layout Strategy

- **Max content width:** 1280px–1360px centered container; text-heavy content (blog articles) constrained further to ~720px measure for optimal reading line-length.
- **Grid system:** 12-column grid, `space-8` gutters, section padding `space-24`–`space-32` vertical.
- **Asymmetric layouts over centered-everything:** Alternating left/right image-text splits (common in premium sites, avoided in most templates which center everything in uniform rows) — used for Mission/Vision, Program highlights, About sections.
- **Whitespace as hierarchy:** Wider desktop canvas is used for breathing room, not for cramming more columns of content — resist the "4 cards in a row because we have the width" template instinct where it dilutes focus.
- **Sticky elements:** Header sticky on scroll (compact/shrunk state after scroll past hero); on long-form pages (e.g., Campaign detail), a sticky donation summary card follows in the right rail on desktop.

---

## 15. Navigation Design

**Structure (desktop header):** Logo (left) — Primary nav links (center/left-of-center: About, Programs, Campaigns, Gallery, Blog, Contact) — Utility actions (right: "Get Involved" ghost button, "Donate Now" solid accent button).

- **Behavior:** Transparent/overlay on hero (if hero is full-bleed image) transitioning to solid `surface-white` with `shadow-sm` on scroll — adds polish without being disorienting.
- **Dropdown (Programs):** Mega-menu-lite pattern — a clean panel listing the 5 program pages with small icon + one-line description each, not a cramped plain-text dropdown list.
- **Active state:** Current page indicated via `primary-700` text color + small underline/dot indicator, not just bold text (bold-only fails color-blind/low-vision users a clear signal).
- **Mobile nav:** Full-screen drawer (Section 13), links stacked large, Donate CTA repeated prominently at the bottom of the drawer.
- **Admin nav:** Persistent left sidebar (Section on Admin Panel below) — distinct pattern from public site since it's a task-oriented tool, not a marketing surface.

---

## 16. Footer Design

**Rationale:** For an NGO, the footer is a secondary trust-signal zone (registration numbers, certifications) as much as a navigation aid — it's often where a skeptical donor looks to verify legitimacy before donating.

**Structure (4-column desktop, stacked accordion-free single column mobile):**

1. **Column 1 — Brand:** Logo, one-line mission statement, social icons (Heroicons/brand icons), registration/certification badges (Trust Reg. No., 80G, 12A) as small trust badges.
2. **Column 2 — Explore:** About, Programs, Campaigns, Gallery, Blog links.
3. **Column 3 — Get Involved:** Donate, Volunteer, Request Help, FAQ links.
4. **Column 4 — Contact:** Address, phone (click-to-call), email, WhatsApp link, embedded small map thumbnail (optional).

**Bottom bar:** Copyright line, Privacy Policy / Terms / Donation-Refund Policy links, all on `primary-800` dark background (footer is the one section allowed a dark background, providing visual closure/weight to the page) with `text-inverse`/muted variants for hierarchy.

---

# PAGE WIREFRAMES

For each page below: section-by-section layout order and purpose. (Full detailed breakdown of Home is in the dedicated section further down.)

## About

| Order | Section | Purpose |
|---|---|---|
| 1 | Page header banner | Small hero: "About Us" title + breadcrumb, background image strip (not full-height hero — inner pages use compact headers to keep focus on content) |
| 2 | Our Story | Asymmetric image-text split: founding story, narrative tone |
| 3 | Mission & Vision preview | Two-column cards linking to full Mission & Vision page |
| 4 | Trustees/Team grid | Photo + name + role cards, 3–4 per row desktop |
| 5 | Certifications/Transparency strip | Trust Reg./80G/12A badges with short explainer + link to Transparency page |
| 6 | CTA band | "Want to be part of our story?" → Volunteer + Donate buttons |

## Mission & Vision

| Order | Section | Purpose |
|---|---|---|
| 1 | Compact page header | Title + breadcrumb |
| 2 | Mission statement | Large serif pull-quote style statement, centered, generous whitespace |
| 3 | Vision statement | Similar treatment, alternating background (`surface-muted`) |
| 4 | Core values | 3–4 icon + short-text value cards in a row |
| 5 | Impact-to-date stats band | Animated counters (meals served, families helped, volunteers, etc.) |

## Activities (Programs)

| Order | Section | Purpose |
|---|---|---|
| 1 | Compact page header | Title + intro sentence |
| 2 | Program grid | 5 program cards (Food/Education/Medical/Clothes/Welfare), image + icon + short description + "Learn More" |
| 3 | Featured activity spotlight | Larger feature block on most active current program with photo story |
| 4 | CTA band | Volunteer/Donate |

*(Each Program detail page, e.g. `/programs/food-distribution`, follows: header → intro/description → photo gallery strip → "How we do it" steps → related campaign card(s) → CTA.)*

## Gallery

| Order | Section | Purpose |
|---|---|---|
| 1 | Compact page header | Title + optional category filter chips (All / Food / Education / Medical / Events) |
| 2 | Masonry/grid image gallery | Lightbox on click, lazy-loaded, captioned |
| 3 | Load more / pagination | Avoids infinite scroll performance issues |

## Events

| Order | Section | Purpose |
|---|---|---|
| 1 | Compact page header | Title |
| 2 | Upcoming events list | Card per event: date badge, title, location, short description, "Details/RSVP" |
| 3 | Past events archive | Collapsed/paginated list below, lighter visual weight |

## Blog

| Order | Section | Purpose |
|---|---|---|
| 1 | Compact page header | Title + search/category filter |
| 2 | Featured post | One large card at top for most recent/pinned article |
| 3 | Post grid | Standard blog cards, 3-column desktop, paginated |
| 4 | Newsletter signup band | Optional module per SRS FR-10 |

*(Blog detail page: header with title/date/author/category → hero image → article body (constrained reading width per Section 14) → share buttons → related posts → comments optional in future roadmap.)*

## Volunteer

| Order | Section | Purpose |
|---|---|---|
| 1 | Hero (compact) | "Give Your Time, Change a Life" — emotional framing distinct from donation hero |
| 2 | Why volunteer | 3 short benefit/impact points with icons |
| 3 | Current volunteer opportunities | Cards by program area |
| 4 | Application form | Multi-field form (Section 9 form design), name/contact/area of interest/availability |
| 5 | Confirmation/reassurance copy | "Our team will contact you within X days" |

## Donate

| Order | Section | Purpose |
|---|---|---|
| 1 | Compact header | "Your Contribution, Their Tomorrow" |
| 2 | Trust strip | Registration/80G badges + "secure payment" indicators immediately visible before any form field — critical for first-time donor confidence |
| 3 | Donation form (stepper) | Campaign select (or "Where Most Needed") → Amount chips + custom → Donor details → Payment |
| 4 | Impact preview sidebar (desktop) | "₹500 feeds a family for a week" style translation of amount to impact, updates live as amount changes |
| 5 | Thank-you/confirmation page | Warm confirmation message, receipt info, share prompt ("Invite others to give"), social proof of recent donors (optional, anonymized) |

## Contact

| Order | Section | Purpose |
|---|---|---|
| 1 | Compact header | Title |
| 2 | Two-column: Contact form + Contact details/map | Form left, address/phone/email/WhatsApp/embedded map right (reversed on mobile: details first, form below) |
| 3 | FAQ teaser | Short "Have questions? See our FAQ" link band |

## Privacy Policy / Terms / Donation-Refund Policy

Single-column static content template: compact header → constrained-width (~720px) legal text with clear heading hierarchy (H2/H3 per clause) → last-updated date → back-to-top affordance for long documents. No sidebar distractions — these pages prioritize legal clarity over marketing design.

## 404 Page

- Centered, minimal layout (no header banner). Friendly on-brand illustration or photo treatment (not a generic "404 robot" cliché graphic — keep consistent with real-photography brand style, e.g., a simple warm graphic mark).
- Short human copy: "This page has wandered off — much like we hope no one in need ever does." (tone: warm, on-brand, not jokey/flippant given the cause).
- Primary CTA back to Home, secondary links to Donate/Contact.

---

# HOME PAGE — Detailed Section Breakdown

| # | Section | Purpose |
|---|---|---|
| 1 | **Hero** | First-impression trust + emotional hook + primary conversion path |
| 2 | **Trust Strip** | Immediate credibility signals below the fold-line |
| 3 | **Impact Stats** | Quantify credibility with real numbers |
| 4 | **Our Programs** | Orient visitor to what the org actually does |
| 5 | **Featured Campaign** | Drive urgency toward an active fundraising need |
| 6 | **How It Works / Your Donation Journey** | Reduce donor uncertainty about "what happens after I give" |
| 7 | **Gallery/Impact Stories Preview** | Emotional proof via real photography |
| 8 | **Testimonials** | Social proof from donors/beneficiaries/volunteers |
| 9 | **Get Involved (Volunteer) Band** | Secondary conversion path for non-monetary contribution |
| 10 | **Blog/News Preview** | Freshness signal + SEO internal linking |
| 11 | **Final CTA Band** | Last-chance conversion before footer |
| 12 | **Footer** | Navigation + legal trust signals (Section 16) |

### 1. Hero
- **Purpose:** Establish emotional connection + immediate credibility + primary donate/volunteer path, all within first 3 seconds.
- **Heading:** "Together, We Restore Dignity."
- **Subheading:** "Gauri Ganesh Seva Sanstha provides food, education, medical care, and hope to families in need — because seva is action, not just intention."
- **Content:** Full-bleed real photograph (beneficiary or distribution drive, warm-graded per Section 11) with gradient overlay; small live impact counter chip overlapping hero bottom edge (e.g., "12,500+ Meals Served").
- **CTA Buttons:** Primary solid accent — "Donate Now"; Secondary ghost (inverse/white variant) — "See Our Work".
- **Suggested Images:** Genuine photo of a food/medical distribution drive with visible warmth (children, elders, volunteers in action) — not posed stock photography.
- **Suggested Icons:** None in hero itself (photography carries the emotional weight; icons would dilute it).
- **Background Style:** Full-bleed photo, `primary-900` gradient overlay (bottom-heavy, 20%→60% opacity) for text legibility.

### 2. Trust Strip
- **Purpose:** Answer "is this legitimate?" before asking for anything.
- **Heading:** (none — treated as a quiet utility band, not a content section)
- **Content:** Horizontal row of small trust markers: "Registered Trust", "80G & 12A Certified", "100% Transparent Fund Usage", "X Years of Seva" — each with a small Heroicon + label.
- **CTA Buttons:** None.
- **Suggested Icons:** `shield-check`, `document-check`, `chart-bar`, `calendar`.
- **Background Style:** `surface-muted`, thin band, sits directly under hero for immediate reassurance.

### 3. Impact Stats
- **Purpose:** Quantify credibility — numbers are more persuasive than adjectives for a first-time donor.
- **Heading:** "Our Impact So Far"
- **Subheading:** "Every number represents a real family, a real meal, a real moment of relief."
- **Content:** 4-stat grid with animated count-up (Section 12): Meals Served / Families Supported / Volunteers Active / Years of Service.
- **CTA Buttons:** None (this section builds credibility, not conversion — avoid diluting with a CTA here).
- **Suggested Icons:** Simple line icons per stat, housed in `primary-100` circular badges.
- **Background Style:** `bg-base`, generous vertical padding, centered.

### 4. Our Programs
- **Purpose:** Orient the visitor to the org's scope of work before asking them to commit to any one action.
- **Heading:** "How We Serve"
- **Subheading:** "Five core programs. One mission — to stand with those who need us most."
- **Content:** 5-card grid (Food Distribution, Education Support, Medical Help, Clothes Distribution, Social Welfare) — image + icon + 1-line description each, linking to individual program pages.
- **CTA Buttons:** Per-card ghost "Learn More"; no section-level CTA needed.
- **Suggested Images:** One representative real photo per program.
- **Suggested Icons:** Heroicons — `cake` (food), `academic-cap` (education), `heart` (medical), `sparkles`/custom (clothes), `users` (welfare).
- **Background Style:** `surface-white`.

### 5. Featured Campaign
- **Purpose:** Create urgency and a specific, tangible giving opportunity (vs. abstract "donate to us").
- **Heading:** "Where Your Help Is Needed Most Right Now"
- **Subheading:** (dynamic — pulls the active campaign's short pitch)
- **Content:** Large featured Campaign Card (Section 8 card design) with progress bar, raised/goal amounts, days remaining, short story excerpt.
- **CTA Buttons:** Primary accent — "Contribute to This Campaign".
- **Suggested Images:** Campaign-specific real photo.
- **Suggested Icons:** `clock` (days remaining), `users` (donor count).
- **Background Style:** `surface-muted`, visually distinct "spotlight" framing (subtle border or elevated card on alt background).

### 6. How It Works / Your Donation Journey
- **Purpose:** Remove donor anxiety about what happens after clicking "Donate" — a subtle but high-impact trust/conversion lever often missing from template NGO sites.
- **Heading:** "What Happens After You Give"
- **Subheading:** "Complete transparency, every step of the way."
- **Content:** 3-step horizontal process: 1) "You Contribute" → 2) "We Deliver" (funds directed to active program/campaign) → 3) "You See the Impact" (receipt + optional follow-up impact update).
- **CTA Buttons:** None needed (informational, reduces friction ahead of the next section's CTA).
- **Suggested Icons:** `hand-raised`, `truck`/`gift`, `photo`.
- **Background Style:** `bg-base`.

### 7. Gallery/Impact Stories Preview
- **Purpose:** Emotional proof — photography does the persuasion work numbers/text can't.
- **Heading:** "Moments of Seva"
- **Content:** 6–8 image grid preview (masonry style) pulling recent gallery uploads, each with a subtle caption on hover.
- **CTA Buttons:** Ghost — "View Full Gallery".
- **Suggested Images:** Recent real drive photography.
- **Background Style:** `surface-white`, full-bleed grid (edge-to-edge on mobile for visual impact).

### 8. Testimonials
- **Purpose:** Third-party social proof from donors, volunteers, or beneficiaries (with consent) builds trust that self-authored copy can't.
- **Heading:** "Voices From Our Community"
- **Content:** Carousel-free (per Section 12, avoid auto-advancing carousels) — instead a horizontal scroll-snap row or 3-column static grid of quote cards with photo + name + role (Donor/Volunteer/Beneficiary tag).
- **CTA Buttons:** None.
- **Background Style:** `surface-muted`.

### 9. Get Involved (Volunteer) Band
- **Purpose:** Offer a non-monetary conversion path for visitors not ready/able to donate.
- **Heading:** "Not Ready to Donate? Give Your Time Instead."
- **Subheading:** "Volunteers are the heart of everything we do."
- **Content:** Split layout — image of volunteers in action (left) + short copy and CTA (right).
- **CTA Buttons:** Secondary/outline — "Become a Volunteer".
- **Background Style:** `bg-base`.

### 10. Blog/News Preview
- **Purpose:** Signal ongoing activity/freshness (a stale-looking site erodes trust) and support SEO internal linking.
- **Heading:** "Latest Updates"
- **Content:** 3 most recent blog cards.
- **CTA Buttons:** Ghost — "Visit Our Blog".
- **Background Style:** `surface-white`.

### 11. Final CTA Band
- **Purpose:** Last conversion opportunity before the visitor exits via footer.
- **Heading:** "Your Small Act of Kindness Can Change a Life Today."
- **Content:** Centered, full-width band, dark `primary-800` background for visual weight/closure ahead of footer.
- **CTA Buttons:** Primary accent (large `lg` size) — "Donate Now"; secondary inverse ghost — "Contact Us".
- **Background Style:** `primary-800` solid, `text-inverse` text — the one other section (besides footer) allowed a dark background, used deliberately to signal "this is the final word."

### 12. Footer
- Per Section 16 specification above.

---

# ADMIN PANEL DESIGN

**Design rationale:** The admin panel serves non-technical trustees/coordinators (Persona: Suresh, Section 3.4 of SRS) — it must prioritize clarity and safe defaults over density or cleverness. Visually it is a distinct system from the public site (utility-first, not marketing), but shares the same color tokens/typography for brand consistency and reduced design-system overhead.

## Sidebar
- Fixed left sidebar (240px desktop, collapses to icon-only 64px rail on tablet, converts to slide-over drawer on mobile).
- Grouped nav sections with small uppercase group labels: **Overview** (Dashboard) / **Fundraising** (Campaigns, Donations) / **Engagement** (Volunteers, Help Requests, Testimonials) / **Content** (Blog, Gallery, Team) / **System** (Settings, Users & Roles, Audit Log).
- Active item: `primary-100` background pill + `primary-700` text/icon (Solid Heroicon variant for active state per Section 10).
- User account mini-card pinned at sidebar bottom (avatar, name, role, quick logout).

## Dashboard
- **Top row — Statistics Cards** (4-across desktop, 2-across tablet, stacked mobile): Total Donations (This Month), Active Campaigns, Pending Volunteer Applications, Pending Help Requests. Each card: large number, small trend indicator (▲/▼ vs. previous period), icon badge, and a ghost "View All" link.
- **Second row:** Donation trend chart (line/bar, last 30/90 days toggle) — left two-thirds; "Recent Activity" feed — right one-third (latest donations, applications, submissions as a compact timeline list).
- **Third row:** "Needs Attention" table — pending Help Requests and Volunteer Applications requiring review, sorted oldest-first so nothing silently ages out.

## Statistics Cards (component spec)
- `surface-white` bg, `radius-lg`, `shadow-sm`, `space-6` padding, icon badge top-left (`primary-100` circle), value in large serif-free bold number (`text-3xl`), label below in `text-sm`/`text-600`, trend chip (success/error colored per Section 2) top-right.

## Tables
- Used for Donations, Volunteers, Help Requests, Blog, Users listings.
- Sticky header row, zebra-free (relies on `border-subtle` row dividers instead of alternating background, cleaner/less noisy at data density).
- Row-level status shown via **Badge** component (Section: Design System), never color-only (icon + text label always paired, per accessibility principle).
- Bulk-select checkbox column for admin efficiency (e.g., export selected donations, mark multiple help requests reviewed).
- Sticky/frozen action column (right) with icon-buttons (view/edit/delete) using Heroicons outline, `text-600` default → `primary-700`/`error-600` on hover depending on action.
- Empty states are designed explicitly (not blank): icon + short message + relevant CTA (e.g., "No campaigns yet — Create your first campaign").

## Forms (Admin)
- Two-column layout on desktop for shorter forms (Settings), single-column for long content forms (Blog editor, Campaign editor) with a sticky "Save/Publish" action bar at the top-right that stays visible while scrolling a long form.
- Required-field indication, inline validation identical pattern to public forms (Section 9) for consistency.
- Destructive actions (Delete Campaign, Delete User) always require a confirmation modal restating what will be affected — never a bare browser `confirm()`.

## Media Manager
- Grid view of uploaded media (thumbnail, filename, upload date, used-in reference count), with drag-and-drop upload zone at top.
- Upload flow shows automatic optimization status ("Converting to WebP...", per SRS FR-24) so admins understand processing isn't a hang/failure.
- Alt-text field required at upload time (not optional) — enforces SRS Section 17 accessibility requirement at the source rather than relying on admin discipline later.
- Filter by usage context (Gallery / Blog / Campaign / Team).

## Settings
- Tabbed sub-navigation (General / SEO Defaults / Social Links / Contact Info) rather than one long scrolling form — reduces cognitive load for infrequent admin visits.
- Each tab is its own focused form with its own Save action (avoids one giant "Save All" with unclear scope of what changed).

## Donation Management
- Primary table view with filters: date range, campaign, status (Success/Pending/Failed), amount range.
- Row click → Donation detail panel (slide-over, not full page navigation) showing donor info, transaction ID, gateway status, linked campaign, and receipt download/resend action.
- Export button (CSV/Excel) respects currently applied filters, not just "export everything."
- Financial figures given visual precedence (larger/bolder) over metadata in both table and detail view, reflecting their importance to trustee reporting.

## Volunteer Management
- Kanban-lite status view (New / In Review / Contacted / Onboarded / Not a Fit) as an alternative view toggle alongside the standard table — matches the natural pipeline mental model coordinators use.
- Detail slide-over: applicant info, area of interest, availability, internal notes field (admin-only, never shown publicly), status dropdown.

## Gallery Management
- Grid-based management mirroring the Media Manager pattern but organized into **Albums/Events** (e.g., "Ganesh Utsav 2026 Drive") rather than a flat image pool — matches how photos are actually contributed and consumed on the public Gallery page.
- Drag-to-reorder within an album for controlling public display order.

## Activity (Programs) Management
- Simple list/table of the 5 core programs (these are largely fixed, not frequently added-to) with edit access to description, hero image, and linked campaigns — intentionally lightweight since this isn't a high-churn content type.

## Blog Management
- Table view (Title, Category, Status [Draft/Published], Published Date, Author) + "New Post" primary button top-right.
- Editor: title, slug (auto-generated, editable), rich-text body, featured image (with mandatory alt text), category/tags, and a dedicated **SEO panel** (meta title, meta description with character-count guidance, OG image preview) — surfaced prominently, not buried, since SEO is a stated SRS priority.
- Draft/Preview/Publish states clearly separated — a Draft should never be publicly reachable by URL guessing (enforced at the access-control level, noted here as a design requirement, not implementation).

## Reports
- Pre-built report templates: Monthly Donation Summary, Campaign Performance, Volunteer Pipeline Summary — each previewable on-screen before export (PDF/CSV), addressing the SRS need for trustee-meeting-ready reports without requiring an admin to manually assemble data.
- Simple date-range picker consistent across all report types.

---

# DESIGN SYSTEM — Reusable Components

## Buttons
Covered fully in Section 7. Variants: Primary, Accent, Secondary, Ghost, Danger, Disabled. Sizes: sm/md/lg.

## Cards
Covered fully in Section 8. Variants: Standard Content Card, Campaign Card (with progress bar), Stat Card (admin), Testimonial Card, Team/Avatar Card.

## Badges
- **Purpose:** Compact status/category indicators used in tables, cards, and detail views.
- **Style:** `radius-sm` (pill option available for status specifically), `text-xs` semibold, `space-2` horizontal padding, background = semantic-color-100 tint, text = semantic-color-700 for contrast.
- **Variants:** Category badge (e.g., "Medical Help" — uses `primary-100`/`primary-700`), Status badge (Success/Warning/Error tokens per Section 2 — always icon + label, e.g., ✓ "Completed", ● "Pending", ✕ "Failed").

## Alerts
- **Purpose:** System-level feedback banners (form submission results, admin action confirmations).
- **Style:** Left-border accent (4px, semantic color) + tinted background (semantic-color-100) + icon + message + optional dismiss (×).
- **Variants:** Success ("Your donation was received — thank you!"), Warning, Error, Info (neutral `primary` tint for informational notices like "Your help request is under review").

## Inputs
Covered in Section 9. Text input, textarea, and file-upload variants share the same border/focus/error treatment for consistency.

## Select Boxes
- Same visual shell as text inputs (border, radius, height, focus ring) for consistency.
- Custom chevron icon (Heroicon `chevron-down`) replacing default browser styling.
- Multi-select (e.g., admin tag assignment) uses a chip-based input pattern rather than a native multi-select listbox, for usability.

## Tables
Covered in Admin Panel > Tables above. Public-facing equivalent (rare — mainly Donation transparency summaries if published) uses the same border/spacing logic but lighter visual weight (no action columns).

## Pagination
- **Style:** Numbered page pills (`radius-full`, current page = solid `primary-700`, others = ghost/text) + Previous/Next arrow buttons using Heroicons `chevron-left`/`chevron-right`.
- **Behavior:** On mobile, collapses to a simple "Page X of Y" with Prev/Next only, avoiding a cramped numbered row.

## Breadcrumbs
- **Style:** `text-sm`, `text-400` separators (`/` or Heroicon `chevron-right` at reduced opacity), current page in `text-600` non-link weight, ancestor links in `primary-700`.
- **Usage:** Present on all inner pages' compact headers (Section on Page Wireframes) for orientation and SEO structured-data alignment (`BreadcrumbList` schema per SRS Section 16).

## Modals
- **Style:** Centered, `surface-white`, `radius-xl`, `shadow-lg`, max-width ~480px (confirmation) or ~720px (content, e.g., gallery lightbox uses a full-bleed variant instead).
- **Overlay:** `primary-900` at 60% opacity backdrop, click-outside-to-dismiss enabled only for non-destructive modals.
- **Structure:** Header (title + close icon-button) → Body → Footer (right-aligned action buttons, destructive action styled `Danger` variant, cancel styled `Ghost`).
- **Accessibility:** Focus trapped within modal while open, `Escape` key dismisses, focus returns to the triggering element on close.

---

## Next Steps

This blueprint defines the visual and interaction language for every screen in the product. Recommended next step: convert this document into a **Tailwind Design Tokens Configuration Plan** (still a planning artifact, not code) — mapping every color/spacing/radius/shadow/font token above to a proposed `tailwind.config` structure and a component-naming convention (Blade component library structure: `<x-ui.button>`, `<x-ui.card>`, etc.) — so that when implementation begins, the design system is translated 1:1 into reusable Blade components rather than ad-hoc utility classes scattered across views, which is the single biggest factor in whether this ends up looking "custom premium" versus "generic Tailwind template" in practice.
