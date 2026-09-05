# Enwires — Website Plan

## 1. Content analysis

**Company:** Enwires — Grenoble, France. ~10 employees, backed by industrial-sector experts.
**Tagline:** "more energy in your battery"
**Sector:** Materials science for lithium-ion batteries — specifically graphite/silicon
composite anode materials.

**Product: SiBoost**
- A silicon-doped graphite (graphite-silicon composite, "Gr-Si").
- Combines graphite's electronic conductivity with silicon's ability to store more
  lithium ions.
- Delivers **1.1× to 4×** the energy density of graphite currently used in Li-ion
  batteries.
- Process is adaptable to most industrial graphite feedstocks — including
  currently under-used graphite grades.
- Visual proof points available as imagery: raw graphite powder, purified/doped
  powder, SEM (electron microscope) close-ups of coated particles, and a
  product bottle shot ("more energy in your battery").

**Existing assets**
- Logo: red "ENWIRES" wordmark with a stacked-chevron / heart-shaped mark (a W built
  from three diagonal cut ribbons, reads as both "W" and a heart — nice dual meaning:
  W for Wires, heart for "more energy... in your battery" affection framing).
- Photography: two bottles (raw graphite vs. SiBoost powder side by side), a loose pile
  of graphite powder (before), a loose pile of doped powder (after), 3 SEM microscopy
  shots (particle-level, surface texture, coated-particle close-up).
- A plain grey mock-up (Word/PDF) with 3 pages: Home, Product, Contact — used here only
  as a copy/content reference, not a visual reference (brief asks for a *modern* look,
  mock-up is a wireframe).

**Site structure implied by the mock-up:** Home / Product / Contact — 3 pages, simple
industrial B2B site. We'll keep that structure but design it properly.

## 2. Design plan (per frontend-design guidance)

**Subject grounding:** This is a deep-tech / advanced-materials company selling into
battery manufacturers. Audience = R&D engineers, procurement, industrial partners.
The job of the site: communicate technical credibility and one clear number (1.1–4×
energy density) fast, and make the material itself — the powder, the particle surface —
the hero, since that IS the product.

**Color tokens** (brief pins primary + background, we extend minimally):
- `--bg` `#444345` — base background (brief)
- `--red` `#FF1400` — primary accent (brief)
- `--ink` `#F5F4F2` — near-white text on dark bg (warm white, not pure #FFF)
- `--charcoal` `#2B2B2C` — deeper panel background, for contrast layering against #444345
- `--line` `#5C5B5D` — hairline dividers/borders on dark
- `--paper` `#EFEDEA` — light section background (SiBoost spec panel), warm light grey,
  not pure white

**Type:**
- Display/headline: **Space Grotesk** (geometric, technical, slightly industrial —
  echoes the angular cut of the logo's diagonal ribbons) — bold weights only, tight
  tracking, sentence case (no all-caps tell).
- Body/UI: **Inter** — for readability at small sizes, form labels, data callouts.
- One numeric callout style reused for the "1.1–4×" stat, set large in Space Grotesk.

**Layout concept:**
- Dark, industrial canvas (#444345 family) throughout — matches a lab/cleanroom
  feel, lets the grayscale SEM photography and the red accent do the work.
- Hero: big Space Grotesk headline over a large SEM particle image bleeding to the
  edge (image IS the hero, not a gradient/stock photo).
- Diagonal accent bar (echoing the logo's diagonal ribbons) used exactly once per
  page as a section divider — a structural device tied to the actual logotype,
  not decoration.
- "Before / after" bottle photo used as a literal comparison device (raw graphite vs
  SiBoost) rather than a generic product shot.
- No card-grid-with-shadows kit. Sections are full-bleed bands alternating
  `--bg`/`--charcoal`/`--paper`, separated by hairlines, not shadows/radius.

ASCII wireframe — Home:
```
[ nav: ENWIRES logo  ·  Home Product Contact ]
------------------------------------------------
[ HERO: full-bleed SEM particle photo, dark scrim ]
[  Headline: "More energy in your battery."      ]
[  Sub: 1 line positioning + primary CTA -> SiBoost ]
------------------------------------------------
[ diagonal red divider strip ]
[ WHO WE ARE — 2-col: text left / stat block right ]
------------------------------------------------
[ WHAT WE OFFER — 3 short value props, left-aligned list, no icon cards ]
------------------------------------------------
[ strip of 5 process photos (powder -> SEM) as a horizontal filmstrip ]
------------------------------------------------
[ footer: Grenoble FR · email · nav · year ]
```

ASCII wireframe — Product (SiBoost):
```
[ nav ]
[ SiBoost headline + one-line definition, dark band ]
------------------------------------------------
[ paper band: big stat "1.1–4×" energy density, left; explanatory
  paragraph, right ]
------------------------------------------------
[ before/after bottle photo, full width, captioned ]
------------------------------------------------
[ dark band: SEM close-up image right / "how it works" 3-line list left ]
------------------------------------------------
[ footer ]
```

ASCII wireframe — Contact:
```
[ nav ]
[ short headline band ]
[ paper band: form (name/email/phone/subject/message) left,
  Grenoble address + map-less location note right ]
[ footer ]
```

**Principles for this build**
1. The material photography is the brand image — never cover it with generic
   gradients; use a dark scrim only where text needs contrast.
2. One motion moment: subtle fade-in on hero load only. No hover-lift cards, no
   scroll-triggered stagger on every block.
3. The diagonal-ribbon motif from the logo is the one recurring structural device,
   used sparingly (divider strips, form focus state).
4. Real sentence-case copy, active voice, no ALL-CAPS eyebrows, no "01/02/03"
   numbering (content isn't a sequence).

## 3. Technical plan

Plain PHP (no framework), shared partials via `include`, single stylesheet, no JS
framework — small vanilla JS only for mobile nav toggle and contact form
client-side check.

```
/enwires
  index.php          -> Home
  product.php         -> SiBoost product page
  contact.php          -> Contact page + form handler (POST to self)
  includes/
    header.php         -> <head>, nav
    footer.php          -> footer, closing tags
    config.php          -> site constants (name, tagline, colors, nav items)
  assets/
    css/style.css
    js/main.js
    img/  (photos copied from uploads + logo)
PLAN.md
```

Contact form: posts to itself, does basic PHP validation (name/email required),
shows a success/error message; no real mailer wired up yet (left as a clearly
marked TODO / mail() stub) since no SMTP details were given.

## 4. Open items / assumptions
- No real address/phone beyond "Grenoble, France" and contact@enwires.com — used as-is.
- Company description condensed from the mock-up copy into tighter, less
  "example content"-flavored sentences.
- Logo file `logo.png` used in the nav (bottle image only used for the
  before/after comparison on Product page).
