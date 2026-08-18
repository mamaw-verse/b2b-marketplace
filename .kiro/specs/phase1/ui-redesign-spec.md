# UI Redesign Spec — One Halal Global Storefront

## Source References
- **Primary**: `mockup/index.html` — single-page prototype with all portal views
- **Secondary**: `https://1halalstore.com/index.html` — live marketplace reference

## Design System (from mockup)

### Colors (already in `resources/css/tokens.css`)
- Brand: `--hs-green-600: #158A5A` (primary), `--hs-green-950: #05291B` (hero/footer/dark)
- Gold: `--hs-gold-500: #C9A24B` (export/premium accent)
- Neutrals: green-tinted scale `--hs-neutral-0` → `--hs-neutral-900`

### Typography
- Display: **Sora** (headings, stat numerals, prices)
- Body: **Inter** (UI, body copy)

### Key Radii
- Tag: `4px`, Card: `14px`, Panel: `24px`, Pill: `999px`

### Shadows
- `shadow-hs-sm`: `0 1px 2px rgba(5,41,27,0.06)`
- `shadow-hs-md`: `0 4px 14px rgba(5,41,27,0.08)`
- `shadow-hs-lg`: `0 12px 32px rgba(5,41,27,0.14)`

---

## Pages to Implement

### 1. Storefront Home (`/`)
- **Hero**: Dark green (`--hs-green-950`) with subtle dot pattern overlay
  - Gold badge: "Verified halal supply chain"
  - Large headline: "Find certified halal manufacturers & suppliers you can trust."
  - Subtext about admin-review and certificate verification
  - Stats: verified companies count, published products count, categories count
- **Search widget**: White rounded panel (`24px radius`) with:
  - Tab toggle: "Find Companies" / "Find Products" (pill style)
  - Text input with search icon
  - Category dropdown
  - Green search button
- **Category sidebar** + **Results grid** below hero
  - Company cards or Product cards based on active tab

### 2. Company Card Component
- White card, `14px` radius, border, hover shadow + green border
- Top: Avatar block (dark green square with initials) + "Verified" badge
- Company name (bold, hover green)
- Tagline (gray, 2-line clamp)
- Category tags row
- Bottom divider: lead time chip + cert status badge

### 3. Product Card Component
- White card, `14px` radius, overflow hidden
- Top: colored background placeholder with "Halal" badge overlay
- Bottom: company name (green, small), product name (bold), price/MOQ row

### 4. Company Profile Page (`/companies/{shop}`)
- Dark header band with back button
- White card with: avatar, name, verified badge, export-ready badge, description
- Side panel: location, lead time, export readiness
- Sections: Value Chain Stepper, Capabilities tags, Certificates table, Regulatory reference
- Products grid from this company

### 5. Product Detail Page (`/products/{product}`)
- Two-column: gallery left, details right
- Gallery: large image with halal badge
- Details: company link, name, description, categories, variants table (SKU/price/MOQ/lead time), documents list
- Phase 1 info banner

### 6. Company Hub — Onboarding Wizard (`/hub/onboarding`)
- 5-step horizontal stepper with numbered circles + connecting lines
- Current step highlighted green, completed steps show checkmark
- Step forms with the field layouts from the mockup
- Revision warning banner (amber) when status is `requires_revision`

### 7. Company Hub — Product Management (`/hub/products`)
- Product list table with status filter tabs
- Product workspace with sub-nav tabs (Details / Variants / Gallery / Documents)
- Inline-editable variant table
- Drag-sortable gallery grid

### 8. Admin Console — Onboarding Queue (`/admin/onboardings`)
- Status filter tabs (Pending / Needs revision / Approved / Rejected) with counts
- Table: company name, categories, certificates, submitted date
- Review detail page: 3-column layout (2 for content, 1 for decision panel)
- Decision modal with notes textarea

### 9. Admin Console — Categories (`/admin/categories`)
- Table with name, slug, description
- Create/edit modal with slug auto-generation

### 10. Layout & Navigation
- **Top app bar**: sticky, white/blur background, logo left, portal switcher center (pill toggle), role badge right
- **Footer**: dark green (`--hs-green-950`), 4-column grid, copyright bar

---

## Implementation Priority
1. Storefront layout (`layouts/storefront.blade.php`) — header + footer matching mockup
2. Hero search component + company/product cards
3. Company profile + product detail pages
4. Hub onboarding wizard styling
5. Hub product management styling
6. Admin console styling
