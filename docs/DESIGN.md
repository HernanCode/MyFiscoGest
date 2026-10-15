# Steep — Style Reference
> serif analytics on warm paper

**Theme:** light

Source measurements are normalized; roles and recommendations are interpreted. Font summary lists are independent, not paired by position. HTML examples are reconstructions, not source components.

Steep renders analytics as editorial — serif Signifier headlines float over a near-monochrome white canvas while a single warm peach accent (#fbe1d1) punctuates an otherwise achromatic system. The page reads like a product magazine spread: oversized italicized display type, generous breathing room, large soft-edged cards at 24px radius, and pill-shaped controls that sit flat against the surface. Components feel quiet and weightless — shadows are barely-there, borders are hairline, and color is rationed to functional emphasis (a peach callout card, a dark brown label on peach). The product surfaces (region tables, activation charts, AI composers) are presented as floating artifacts around the headline, not nested in a dashboard shell.

## Tokens — Colors

| Name | Value | Token | Role |
|------|-------|-------|------|
| Ink Black | `#17191c` | `--color-ink-black` | Primary text, filled button background, nav logo — the only dark surface in the system; every CTA and body headline resolves to this near-black |
| Paper White | `#ffffff` | `--color-paper-white` | Page canvas, button text, elevated card surfaces — the dominant background tone carrying ~76 frequency points |
| Mist Gray | `#f2f2f3` | `--color-mist-gray` | Card surfaces, secondary backgrounds, input fills — the quiet layer beneath paper for nested content |
| Fog White | `#fafafb` | `--color-fog-white` | Secondary page background for alternating sections, hover surfaces — one step above paper for subtle band variation |
| Slate Gray | `#777b86` | `--color-slate-gray` | Link color, muted helper text, footer copy — cool desaturated gray that sits between body text and disabled |
| Ash Gray | `#979799` | `--color-ash-gray` | Tertiary labels, category tags (Marketing, Finance, Sales) — one step lighter than link color |
| Smoke Gray | `#a3a6af` | `--color-smoke-gray` | Placeholder text (Ask anything…), disabled labels — the lightest functional gray, used when text recedes |
| Blush Peach | `#fbe1d1` | `--color-blush-peach` | Accent card background, warm highlight wash — the only chromatic surface in the system; creates editorial warmth against monochrome |
| Sienna Brown | `#5d2a1a` | `--color-sienna-brown` | Text and stroke on peach surfaces, dark accent for chart line strokes — a warm deep brown that pairs with Blush Peach like ink on kraft paper |

## Tokens — Typography

### Signifier — Display and headline serif — used exclusively for H1/H2 at three sizes; weight stays at 400 (regular) at every scale, which is the signature choice: the serif whispers authority rather than shouting in bold · `--font-signifier`
- **Substitute:** GT Sectra, Tiempos Headline, Source Serif 4, or ui-serif/Georgia as fallback
- **Weights:** 400
- **Sizes:** 44px, 64px, 90px
- **Line height:** 1.30
- **Letter spacing:** -2.25px at 90px, -0.96px at 64px, -0.66px at 44px
- **Role:** Display and headline serif — used exclusively for H1/H2 at three sizes; weight stays at 400 (regular) at every scale, which is the signature choice: the serif whispers authority rather than shouting in bold

### Sohne — Body, UI, and navigation sans — the workhorse covering everything from 14px metadata to 26px subheads; the half-step weights (430, 450, 480) create fine-grained hierarchy without jumping to bold · `--font-sohne`
- **Substitute:** Inter, Söhne (Klim Type Foundry), or ui-sans-serif/system-ui stack
- **Weights:** 400, 430, 450, 480, 500
- **Sizes:** 14px, 15px, 16px, 17px, 18px, 20px, 22px, 26px
- **Line height:** 1.00–1.50
- **Letter spacing:** -0.234px at 26px, -0.162px at 18px, 0 at body sizes
- **Role:** Body, UI, and navigation sans — the workhorse covering everything from 14px metadata to 26px subheads; the half-step weights (430, 450, 480) create fine-grained hierarchy without jumping to bold

### Type Scale

| Role | Family | Weight | Size | Line Height | Letter Spacing | Token |
|------|--------|--------|------|-------------|----------------|-------|
| caption | — | — | 15px | 1.5 | — | `--text-caption` |
| body | — | — | 17px | 1.35 | — | `--text-body` |
| body-lg | — | — | 20px | 1.35 | — | `--text-body-lg` |
| subheading | — | — | 22px | 1.5 | — | `--text-subheading` |
| heading-sm | — | — | 26px | 1.18 | -0.23px | `--text-heading-sm` |
| heading | — | — | 44px | 1.3 | -0.66px | `--text-heading` |
| heading-lg | — | — | 64px | 1.3 | -0.96px | `--text-heading-lg` |
| display | — | — | 90px | 1.3 | -2.25px | `--text-display` |

## Tokens — Spacing & Shapes

**Base unit:** 4px

**Density:** comfortable

### Spacing Scale

| Name | Value | Token |
|------|-------|-------|
| 4 | 4px | `--spacing-4` |
| 8 | 8px | `--spacing-8` |
| 12 | 12px | `--spacing-12` |
| 16 | 16px | `--spacing-16` |
| 20 | 20px | `--spacing-20` |
| 24 | 24px | `--spacing-24` |
| 28 | 28px | `--spacing-28` |
| 32 | 32px | `--spacing-32` |
| 40 | 40px | `--spacing-40` |
| 64 | 64px | `--spacing-64` |
| 80 | 80px | `--spacing-80` |
| 96 | 96px | `--spacing-96` |
| 124 | 124px | `--spacing-124` |
| 128 | 128px | `--spacing-128` |
| 160 | 160px | `--spacing-160` |

### Border Radius

| Element | Value |
|---------|-------|
| cards | 24px |
| images | 12px |
| inputs | 16px |
| buttons | 9999px |
| smallCards | 16px |
| elevatedCards | 20px |

### Shadows

| Name | Value | Token |
|------|-------|-------|
| subtle | `oklab(0 0 0 / 0.05) 0px 0px 0px 1px, rgba(0, 0, 0, 0.08) ...` | `--shadow-subtle` |
| subtle-2 | `oklab(0 0 0 / 0.05) 0px 0px 0px 1px, rgba(0, 0, 0, 0.1) 0...` | `--shadow-subtle-2` |
| subtle-3 | `rgba(4, 23, 43, 0.05) 0px 0px 0px 1px, rgba(0, 0, 0, 0.1)...` | `--shadow-subtle-3` |

### Layout

- **Page max-width:** 1200px
- **Section gap:** 80px
- **Card padding:** 20px
- **Element gap:** 8px

## Components

### Pill Button — Filled
**Role:** Primary call-to-action (Get started, Book a demo)

Background #17191c, text #ffffff, border 1px solid #ffffff (invisible against fill), border-radius 9999px (fully rounded), padding 0 20px, height auto with text. Sohne 16px weight 400. No shadow. The pill shape and dark fill against white is the signature action element — it reads as a solid black lozenge.

### Pill Button — Ghost
**Role:** Secondary action paired with filled primary (Book a demo beside Get started)

Background transparent, text #17191c, border 1px solid #17191c, border-radius 9999px, padding 0 20px. Sohne 16px weight 400. Shares the pill geometry with the filled variant so they read as a matched pair on the same baseline.

### Text Link with Arrow
**Role:** Inline navigation and section transitions (Learn more →, Read the story →)

No background, no border, no border-radius, text #17191c, Sohne 16px weight 400, padding 20px 0. The arrow glyph (→) is part of the label, not a separate icon. This is the lowest-emphasis interactive element — underlines only on hover.

### Nav Link
**Role:** Top navigation items (Product, Resources, Customers, Pricing)

No background or border, text #17191c, Sohne 16px weight 400, padding 2px 0. Sits in a transparent top bar with the logo left and CTAs right. The nav is whisper-quiet — no background, no shadow, no separator.

### Neutral Card
**Role:** Feature blocks, content containers, and base card surface

Background #f2f2f3, border-radius 24px, no shadow, no border, padding varies (0 internally with content children providing their own padding). This is the default workhorse card — flat, soft, and quiet.

### Accent Peach Card
**Role:** Editorial highlight or callout panel (customer quotes, feature spotlights)

Background #fbe1d1, text and strokes #5d2a1a, border-radius 24px, no shadow, no border. The warm-on-warm palette creates a kraft-paper effect — these cards should be rare (one per page maximum) to preserve their impact.

### Floating Product Artifact
**Role:** Hero and section visual elements (region table, activation chart, registration card, AI composer)

Background #ffffff, border-radius 20px, subtle box-shadow: 0 0 0 1px rgba(4,23,43,0.05), 0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1), padding 16px 20px 12px 12px. These are the product UI fragments that float around hero text — they are the only elements with visible shadow, and only at 10% opacity.

### Input / Composer
**Role:** AI question input field (Ask anything…)

Background #ffffff, border 1px solid #ececec or hairline, border-radius 16px, padding 16px, placeholder text #a3a6af in Sohne 16px. Contains left-side @ and ⓘ icons and a right-side dark circular send button (40px diameter, #17191c fill, white arrow icon).

### Stat Card with Chart
**Role:** Data display fragment (Registrations 2.4k, Activation 46.2%)

White floating artifact surface with a bold metric in Sohne 20px weight 500 #17191c, a delta line (↑ 5.5x vs last week) in Sohne 14px #777b86, and a minimal line or radial chart in #5d2a1a stroke. No axes, no gridlines — the chart is a gestural line, not a data dashboard.

### Avatar Bubble
**Role:** User presence indicator on floating cards (JB, AF initials)

Circular, 40px diameter, border-radius 9999px, background tinted (light green for JB, light blue for AF), 2-letter monogram in Sohne weight 500, small directional arrow (cursor pointer) extending from the bubble edge.

### Tag / Category Label
**Role:** Section or content category markers (Marketing, Finance, Sales)

No background, no border, text in Sohne 14px weight 400 #979799. Intentionally ghost-like — these are typographic tags, not badges. They group without visual weight.

## Do's and Don'ts

### Do
- Use Signifier weight 400 at 44/64/90px for all display and heading copy; never substitute a sans-serif at these sizes
- Use the peach #fbe1d1 card surface at most once per page and only for editorial emphasis — treat it as a rare accent, not a background
- Set border-radius to 9999px on all buttons and 24px on all content cards; these are the two structural radii of the system
- Pair every filled pill button (#17191c) with a ghost pill button (#17191c border, transparent fill) as a secondary action on the same row
- Use Sohne half-step weights (430, 450, 480) for body hierarchy before reaching weight 500 — the scale is finer than standard 400/500/700
- Set letter-spacing to -0.025em on 90px display, -0.015em on 64/44px headings, and -0.009em on 26/18px Sohne — tighter tracking at larger sizes is the typographic signature
- Keep the 4px base unit: use 4/8/12/16/20/24px for component padding, and 80px for section gaps

### Don't
- Don't use chromatic colors beyond the peach/brown pair — the system is intentionally 97% achromatic; introducing blue, green, or purple will break the editorial restraint
- Don't use bold (600+) or semibold (500) weights in Signifier — the serif stays at 400 across all sizes, that restraint is the signature
- Don't apply drop shadows to content cards (Neutral Card or Accent Peach Card) — only floating product artifacts earn elevation
- Don't use border-radius below 16px on cards or below 9999px on buttons — sharp corners and moderate radii are not part of this system
- Don't underline inline text links at rest — the arrow suffix (→) carries the link affordance; underlines appear only on hover
- Don't place the peach #fbe1d1 card on a non-white section background — it needs Paper White or Card Mist beneath it to read as warm-on-neutral
- Don't use the #5d2a1a Sienna Brown outside peach surfaces — it's the ink for Blush Peach cards and chart strokes, never body text on white

## Surfaces

| Level | Name | Value | Purpose |
|-------|------|-------|---------|
| 0 | Canvas | `#ffffff` | Default page background; the white paper everything sits on |
| 1 | Card Mist | `#f2f2f3` | Quietly nested content blocks, feature cards, tab panels |
| 2 | Section Fog | `#fafafb` | Alternating section bands that break up the white canvas without contrast |
| 3 | Accent Blush | `#fbe1d1` | Editorial accent cards; the chromatic punctuation of the system |
| 4 | Elevated White | `#ffffff` | Floating product UI artifacts (region tables, activation charts, AI composer) that overlap hero/section content with subtle shadow |

## Elevation

- **Floating Product Artifact:** `0 0 0 1px rgba(4,23,43,0.05), 0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1)`
- **Modal / Overlay Card:** `oklab(0 0 0 / 0.05) 0px 0px 0px 1px, rgba(0,0,0,0.1) 0px 8px 40px 0px`
- **Dropdown / Popover:** `oklab(0 0 0 / 0.05) 0px 0px 0px 1px, rgba(0,0,0,0.08) 0px 4px 24px 0px`

## Imagery

Imagery is product-first, not lifestyle: floating UI fragments (region tables with 5-row data, line charts showing activation over Aug–Nov, radial progress rings, AI input composers) are positioned as cropped screenshots around editorial headlines. No photography, no illustration, no abstract graphics. All product visuals sit on white floating-artifact cards with hairline borders and soft 10% shadows. Avatar circles carry a small directional cursor pointer — a visual motif that signals live interaction. The hero composition is a text-and-UI collage, not a centered headline with a stock photo.

## Layout

Page model is max-width 1200px centered, with hero sections going near full-bleed but staying within the container. The hero pattern is a centered oversized serif headline with a subhead and pill button pair, surrounded by four floating product artifact cards (region table top-left, registration card right, activation chart bottom-left, AI composer bottom-center) that overlap the white canvas at varied offsets. Sections alternate between Paper White and Card Mist backgrounds to create quiet rhythm without strong contrast. Feature sections use a 2-column text+UI layout with generous 80px vertical gaps. Navigation is a single transparent top bar (no background, no border, no shadow) with logo left, nav links center, and two CTAs (text link + filled pill) right. The overall density is spacious — the page breathes between sections, and content never crowds the edges.

## Agent Prompt Guide

## Quick Color Reference
- text: #17191c
- background: #ffffff
- border: #ececec
- muted text: #777b86
- accent: #fbe1d1
- primary action: #17191c (filled action)

## Example Component Prompts
1. **Hero headline + accent card collage**: White canvas (#ffffff). Display headline at 90px Signifier weight 400, #17191c, letter-spacing -2.25px, with one italicized phrase mid-sentence. Subhead at 17px Sohne weight 400, #777b86. Below: a filled pill button (background #17191c, text #ffffff, border-radius 9999px, padding 0 20px, Sohne 16px) and a ghost pill button (background transparent, border 1px solid #17191c, text #17191c, border-radius 9999px) side by side. Surround the text with three white floating product artifact cards: a data table card, a line chart card, and a stat card — each with background #ffffff, border-radius 20px, box-shadow 0 0 0 1px rgba(4,23,43,0.05) + 0 20px 25px -5px rgba(0,0,0,0.1), positioned with negative margins to overlap the text margins.

2. **Accent editorial card**: Background #fbe1d1, text #5d2a1a, border-radius 24px, no shadow, padding 40px. Title at 26px Sohne weight 450, #5d2a1a, letter-spacing -0.23px. Body quote at 18px Sohne weight 430, #5d2a1a. Attribution at 14px Sohne weight 400, #5d2a1a. Place this card once on a #ffffff section, never on a colored or dark background.

3. **Neutral feature card**: Background #f2f2f3, border-radius 24px, no shadow, padding 32px 20px. Category label at 14px Sohne weight 400, #979799 (no background, no badge style). Title at 20px Sohne weight 500, #17191c. Body at 16px Sohne weight 400, #17191c, line-height 1.5. Text link below: Sohne 16px weight 400, #17191c, no border-radius, padding 20px 0, with → arrow suffix.

4. **AI composer input**: White background #ffffff, border 1px solid #ececec, border-radius 16px, padding 16px, width 480px. Placeholder text Ask anything… at 16px Sohne weight 400, #a3a6af. Left side: two ghost icon buttons (40px circle, no fill). Right side: 40px circular send button with background #17191c, white arrow icon centered.

5. **Section with alternating background**: Section background #fafafb (Fog White), padding 80px vertical. Section title at 64px Signifier weight 400, #17191c, letter-spacing -0.96px. Subhead at 18px Sohne weight 430, #777b86. Below: 3-column grid of neutral feature cards (#f2f2f3 background, 24px radius, 20px padding, no shadow) with 24px column gap.

## Similar Brands

- **Linear** — Same monochrome dark-text-on-white approach with oversized serif-free type and pill-shaped CTAs; Linear's restraint matches Steep's editorial minimalism
- **Pitch** — Presentation tool that pairs serif display headlines with a warm accent palette and floating UI cards; shares the editorial-product hybrid visual language
- **Arc** — Browser with a soft warm-toned monochrome interface, generous border-radius on cards, and the same whisper-quiet typography approach
- **Framer** — Large serif headlines floating over white with minimal chrome and pill controls; shares the magazine-spread page architecture

## Quick Start

### CSS Custom Properties

```css
:root {
  /* Colors */
  --color-ink-black: #17191c;
  --color-paper-white: #ffffff;
  --color-mist-gray: #f2f2f3;
  --color-fog-white: #fafafb;
  --color-slate-gray: #777b86;
  --color-ash-gray: #979799;
  --color-smoke-gray: #a3a6af;
  --color-blush-peach: #fbe1d1;
  --color-sienna-brown: #5d2a1a;

  /* Typography — Font Families */
  --font-signifier: 'Signifier', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  --font-sohne: 'Sohne', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;

  /* Typography — Scale */
  --text-caption: 15px;
  --leading-caption: 1.5;
  --text-body: 17px;
  --leading-body: 1.35;
  --text-body-lg: 20px;
  --leading-body-lg: 1.35;
  --text-subheading: 22px;
  --leading-subheading: 1.5;
  --text-heading-sm: 26px;
  --leading-heading-sm: 1.18;
  --tracking-heading-sm: -0.23px;
  --text-heading: 44px;
  --leading-heading: 1.3;
  --tracking-heading: -0.66px;
  --text-heading-lg: 64px;
  --leading-heading-lg: 1.3;
  --tracking-heading-lg: -0.96px;
  --text-display: 90px;
  --leading-display: 1.3;
  --tracking-display: -2.25px;

  /* Typography — Weights */
  --font-weight-regular: 400;
  --font-weight-w430: 430;
  --font-weight-w450: 450;
  --font-weight-w480: 480;
  --font-weight-medium: 500;

  /* Spacing */
  --spacing-unit: 4px;
  --spacing-4: 4px;
  --spacing-8: 8px;
  --spacing-12: 12px;
  --spacing-16: 16px;
  --spacing-20: 20px;
  --spacing-24: 24px;
  --spacing-28: 28px;
  --spacing-32: 32px;
  --spacing-40: 40px;
  --spacing-64: 64px;
  --spacing-80: 80px;
  --spacing-96: 96px;
  --spacing-124: 124px;
  --spacing-128: 128px;
  --spacing-160: 160px;

  /* Layout */
  --page-max-width: 1200px;
  --section-gap: 80px;
  --card-padding: 20px;
  --element-gap: 8px;

  /* Border Radius */
  --radius-sm: 0.01px;
  --radius-xl: 12px;
  --radius-2xl: 16px;
  --radius-2xl-2: 20px;
  --radius-3xl: 24px;

  /* Named Radii */
  --radius-cards: 24px;
  --radius-images: 12px;
  --radius-inputs: 16px;
  --radius-buttons: 9999px;
  --radius-smallcards: 16px;
  --radius-elevatedcards: 20px;

  /* Shadows */
  --shadow-subtle: oklab(0 0 0 / 0.05) 0px 0px 0px 1px, rgba(0, 0, 0, 0.08) 0px 4px 24px 0px;
  --shadow-subtle-2: oklab(0 0 0 / 0.05) 0px 0px 0px 1px, rgba(0, 0, 0, 0.1) 0px 8px 40px 0px;
  --shadow-subtle-3: rgba(4, 23, 43, 0.05) 0px 0px 0px 1px, rgba(0, 0, 0, 0.1) 0px 20px 25px -5px, rgba(0, 0, 0, 0.1) 0px 8px 10px -6px;

  /* Surfaces */
  --surface-canvas: #ffffff;
  --surface-card-mist: #f2f2f3;
  --surface-section-fog: #fafafb;
  --surface-accent-blush: #fbe1d1;
  --surface-elevated-white: #ffffff;
}
```

### Tailwind v4

```css
@theme {
  /* Colors */
  --color-ink-black: #17191c;
  --color-paper-white: #ffffff;
  --color-mist-gray: #f2f2f3;
  --color-fog-white: #fafafb;
  --color-slate-gray: #777b86;
  --color-ash-gray: #979799;
  --color-smoke-gray: #a3a6af;
  --color-blush-peach: #fbe1d1;
  --color-sienna-brown: #5d2a1a;

  /* Typography */
  --font-signifier: 'Signifier', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  --font-sohne: 'Sohne', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;

  /* Typography — Scale */
  --text-caption: 15px;
  --leading-caption: 1.5;
  --text-body: 17px;
  --leading-body: 1.35;
  --text-body-lg: 20px;
  --leading-body-lg: 1.35;
  --text-subheading: 22px;
  --leading-subheading: 1.5;
  --text-heading-sm: 26px;
  --leading-heading-sm: 1.18;
  --tracking-heading-sm: -0.23px;
  --text-heading: 44px;
  --leading-heading: 1.3;
  --tracking-heading: -0.66px;
  --text-heading-lg: 64px;
  --leading-heading-lg: 1.3;
  --tracking-heading-lg: -0.96px;
  --text-display: 90px;
  --leading-display: 1.3;
  --tracking-display: -2.25px;

  /* Spacing */
  --spacing-4: 4px;
  --spacing-8: 8px;
  --spacing-12: 12px;
  --spacing-16: 16px;
  --spacing-20: 20px;
  --spacing-24: 24px;
  --spacing-28: 28px;
  --spacing-32: 32px;
  --spacing-40: 40px;
  --spacing-64: 64px;
  --spacing-80: 80px;
  --spacing-96: 96px;
  --spacing-124: 124px;
  --spacing-128: 128px;
  --spacing-160: 160px;

  /* Border Radius */
  --radius-sm: 0.01px;
  --radius-xl: 12px;
  --radius-2xl: 16px;
  --radius-2xl-2: 20px;
  --radius-3xl: 24px;

  /* Shadows */
  --shadow-subtle: oklab(0 0 0 / 0.05) 0px 0px 0px 1px, rgba(0, 0, 0, 0.08) 0px 4px 24px 0px;
  --shadow-subtle-2: oklab(0 0 0 / 0.05) 0px 0px 0px 1px, rgba(0, 0, 0, 0.1) 0px 8px 40px 0px;
  --shadow-subtle-3: rgba(4, 23, 43, 0.05) 0px 0px 0px 1px, rgba(0, 0, 0, 0.1) 0px 20px 25px -5px, rgba(0, 0, 0, 0.1) 0px 8px 10px -6px;
}
```

---

## Aplicación: panel Fiscal (esta app)

> Adaptación de producto de una referencia visual externa (solo estructura y
> composición). Sobre las bases de la marca Steep: tipografía serif para titulares,
> radios de 24 px en tarjetas, controles en píldora, fondo papel.

### Excepciones a la marca (aprobadas para esta app)

- **Azul marino de datos `#1f4a8a`** y su tinte **`#e8eef9`**: permitidos únicamente en
  la tarjeta principal del panel (Ingresos) y en las barras de las gráficas del panel.
  El resto de colores cromáticos siguen prohibidos.
- **Sombra solo en artefactos flotantes, popovers y modales** (`shadow-subtle-2`):
  composer del panel, tarjeta "Resultado acumulado" (artefacto flotante blanco con
  gráfico), menús desplegables y modales. Las tarjetas blancas normales y las neutras
  (`#f2f2f3`) **no** llevan sombra; las blancas se delimitan con borde de 1 px `#ececec`.
- **Destructivo en `#17191c`** (nunca rojo): el borrado se confirma siempre en un modal.

### Tokens añadidos

| Nombre | Valor | Uso |
|--------|-------|-----|
| `navy` | `#1f4a8a` | Cifras y barras de ingresos, píldora de trimestre activo |
| `navy-tint` | `#e8eef9` | Fondo de la tarjeta principal del panel |
| `mid` | `#4d5159` | Ítems inactivos del sidebar |
| `bar` | `#c9ccd3` | Barras de gastos en las gráficas del panel |

### Reglas globales (se aplican a toda la app)

- **Serif** (Georgia/Signifier) solo para titulares, peso **400**, tracking negativo.
  Nunca serif en negrita.
- **Inter variable** (se carga `inter:opsz,wght@14..32,100..900`): pesos intermedios
  `font-[430]`, `font-[450]`, `font-[480]` para cifras y jerarquía fina antes del 500.
- Radios: botones 9999 px, tarjetas 24 px, inputs 16 px. Espaciados en múltiplos de 4.
  Separación entre secciones 80 px (`mb-20`).
- Enlaces con flecha (→) y sin subrayado en reposo.
- Estados interactivos: hover, `focus-visible` con anillo 2 px `#17191c` y offset 2 px,
  disabled y loading. Tamaños táctiles mínimos de 40 px.
- **Contenedor de página** (`x-page-container`): un único contenedor compartido por la
  barra superior y el contenido, de modo que título y botones quedan alineados con la
  rejilla de tarjetas. Comportamiento:
  - Pantallas de datos (Panel, Movimientos, Presentaciones): arranque pegado al sidebar
    y ancho adaptable al 100 % del área principal (padding horizontal 40 px desde `lg`,
    20 px en móvil). Solo en pantallas **muy grandes** se limita con un **tope de
    1800 px** (`w-full max-w-[1800px]`) y se centra (`margin-inline: auto`), para que en
    ultrawide no se estire de forma absurda.
  - Los formularios (movimiento y Perfil) usan también el contenedor a ancho
    completo, con una rejilla de dos columnas: ajustes a la izquierda y una
    tarjeta de contexto fija a la derecha (previsualización del importe en
    movimiento, resumen de cuenta en Perfil).
  - Por debajo de 1024 px la rejilla pasa a una sola columna.
- Toast flotante para mensajes de éxito (fondo `#17191c`, texto blanco, radio 16 px,
  autodescartable a los 4 s) en lugar de barras grises a todo el ancho.

### Carcasa (escritorio, a partir de 1024 px)

- **Sidebar** (`x-sidebar`, 232 px, fija, `#fafafb`, borde derecho 1 px `#ececec`, sin
  sombra, texto 14 px):
  - Cabecera: logo "Fiscal." en serif 22 px + selector de año (dropdown pequeño, sin
    flecha pisando el texto; al cambiar, el panel se recarga con ese año).
  - Grupo **Principal**: Panel, Movimientos, Presentaciones. Ítem: icono outline
    18 px + texto, altura 36 px, **radio 12 px**, padding 0 10 px. Activo: fondo
    `#f2f2f3`, texto `#17191c`, peso 480/500. Inactivo: texto `#4d5159`.
  - Grupo **Trimestres**: separado con `mt-8`; chip cuadrada 20 px (radio 6 px) +
    etiqueta y, a la derecha, el estado como **pill discreta** (punto + etiqueta en
    11 px sobre `#f2f2f3`): punto relleno = Presentado, punto con borde = Pendiente.
    Clic = `/movimientos` filtrado por ese trimestre.
  - Grupo **Clientes**: los 3 clientes con más ingresos del año seleccionado, cada uno
    con **avatar burbuja redondo** de 28 px con la inicial. Sin datos → oculto.
  - Pie: bloque de usuario con avatar 28 px redondo, nombre y menú con "Cerrar sesión".
  - Títulos de grupo en 12 px, `#979799`, sin mayúsculas.
- **Barra superior** (`x-topbar`, 56 px, sin fondo ni borde): solo el título de la
  página en 15 px peso 500 a la izquierda (más el botón de menú en móvil). Sin botón
  global de "Añadir movimiento": las acciones de creación viven junto al saludo (Panel)
  o junto al título (Movimientos) — jerarquía única por pantalla.
- **Móvil (< 1024 px)**: sidebar oculto tras un botón de menú en la barra superior
  (panel lateral con velo, Alpine, se cierra con Escape). Tarjetas en una sola columna.

### Panel

- Saludo según la hora + nombre del usuario, en serif 44 px peso 400. Debajo, frase de
  17 px `#777b86` calculada con datos reales (p. ej. "El modelo 303 del T3 vence en
  13 días."). A la derecha del saludo, alineados con su base, el par de pills
  "Nuevo ingreso" (relleno `#17191c`, texto blanco) y "Nuevo gasto" (ghost, borde
  1 px `#17191c`), mismo estilo y tamaño que en Movimientos. Separación de 32 px
  (`mb-8`) entre el saludo y la primera fila de tarjetas.
- Rejilla de **12 columnas ancho completo hasta 1440 px, gap uniforme 16 px**, sin
  tarjetas pegadas ni alturas desiguales (`h-full` en filas):
  - **Fila 1**: Ingresos (8 col) + Próximo vencimiento (4 col).
  - **Fila 2**: tres tarjetas iguales (4 col cada una): Resultado acumulado (blanco
    flotante con sombra), 303, 130 (Neutral Card `#f2f2f3`, sin sombra).
  - **Fila 3**: Últimos movimientos (4 col) + Caja pendiente (8 col), ambas como
    tarjetas normales en la rejilla (nada flotando en la cabecera).
  - Todas las tarjetas con padding interior de 20–24 px y radio 24 px.
  - **A) Ingresos** — fondo `#e8eef9`: cabecera con las **tres cifras del
    trimestre** — Ingresos (Inter 44 px, peso 450, tracking negativo, con delta vs
    el trimestre anterior) y, a su lado, **Gastos** y **Margen** (22 px, peso 450;
    el margen lleva el `% sobre ingresos` debajo); a la derecha,
    **segmented control T1–T4** en píldora (activo `#1f4a8a`/blanco). Barras
    mensuales **a todo el ancho de la tarjeta**: 12 columnas con `flex` repartidas
    por todo el ancho (cada mes ocupa 1/12; mes a la izquierda y mes a la derecha
    pegados a los bordes), dos barras por mes con `justify-center` (azul marino
    sólido para meses del trimestre, atenuados el resto; gris `#c9ccd3` para gastos;
    meses sin datos con arranque mínimo atenuado, sin huecos), esquinas superiores
    redondeadas, etiquetas de mes en 14 px `#777b86`. A la derecha (desde `xl`),
    "Por categoría" como lista con separadores hairline (nombre a la izquierda,
    importe a la derecha, tabular).
  - **B) Próximo vencimiento** — fondo `#fbe1d1`, texto solo `#5d2a1a`: título
    pequeño arriba, **anillo de progreso grande (128 px)** con trazo `#5d2a1a` y los
    días dentro en grande; abajo, "Importe estimado" con la **suma de todos los
    modelos que vencen ese mismo día** (28 px, peso 450), la fecha y el **desglose
    por modelo** (`Modelo 303 · T{n}` / `Modelo 130 · T{n}`). Los importes negativos
    se muestran como "A compensar" y no se restan del total a pagar.
  - **C) Resultado acumulado** — artefacto flotante blanco con sombra: etiqueta
    pequeña, cifra grande (peso 450), pares etiqueta/valor (Ingresos/Gastos
    acumulados, 14 px `#777b86`) y **sparkline gestual** `#5d2a1a` al fondo de la
    tarjeta (línea + relleno suave + discontinua con el nivel del trimestre anterior).
  - **D) 303 y 130** — `x-metric-tile` con la misma estructura: etiqueta pequeña,
    cifra (28 px, peso 450), chip de estado ("A pagar"/"A ingresar"), pares
    etiqueta/valor en 14 px `#777b86` (Repercutido/Soportado; Base 20 %/Retenciones/
    130 ya pagados).
  - **E) Caja pendiente** — tarjeta normal de la rejilla; arriba, **resumen** de
    "Por cobrar", "Por pagar" y "Caja neta" (22 px peso 450; el neto en `navy`, o
    `ink` si es negativo); debajo, las filas de movimientos pendientes (cobros y
    pagos) con indicador ↑/↓, cliente/proveedor, concepto, fecha, pill "Por
    cobrar" (mist) / "Por pagar" (borde `ink`) e importe.
- **Ponte al día** (checklist, al pie del panel, tras la rejilla): tarjeta blanca que
  solo aparece si hay algo que hacer; una fila por tarea (vencimientos vencidos sin
  marcar, cobros vencidos, movimientos sin factura, gastos sin categoría) como enlace
  con título, detalle y acción ("Ver movimientos →") a la derecha.
- "Estimaciones orientativas. Verifica antes de presentar." como nota de 13 px
  `#777b86` al pie.
- Gráficas en SVG, sin rejilla; el aviso de vencidas sin marcar se integra en el
  checklist "Ponte al día" (acromático, `#17191c`).

### Movimientos (listado)

- **Cabecera**: título serif + **una sola jerarquía de acciones**: pill rellena
  "Nuevo ingreso" + pill ghost "Nuevo gasto" junto al título, en 16 px (el botón
  global de la barra se oculta en esta pantalla).
- **Franja de resumen**: 3 tarjetas pequeñas Neutral Card (Ingresos, Gastos e IVA del
  periodo filtrado) con el estilo de las del Panel.
- **Filtros compactos** (en vez de 4 selects): input de búsqueda con lupa (radio
  16 px, 40 px de alto), **tipo como segmented control** (Todos · Ingresos · Gastos),
  chips desplegables de Año/Trimestre/Estado (36 px, texto 14–15 px, popover con
  sombra; chip activo `#17191c` con texto blanco), enlace "Limpiar filtros" y botón
  **"Exportar CSV"** (descarga el periodo filtrado, formato Excel: `;` + BOM).
- **Tabla** (desde `lg`): contenedor blanco con hairline, radio 24 px, cabecera
  sticky (`top-0`, 14 px `#979799`, sin mayúsculas; ordenación por Fecha/Concepto/
  Total con flecha), filas de 56–64 px con hover `#fafafb` y divisores hairline.
  - Columna Concepto: título (peso 480) + cliente/proveedor debajo en 14 px
    `#777b86` (sin "—" si no hay cliente).
  - Indicador de tipo: círculo pequeño monocromo con flecha ↑ (ingreso) / ↓ (gasto).
  - Importes con `tabular-nums` alineados a la derecha; "Total" en peso 500 y
    "Base / IVA" debajo en `#777b86`.
  - Estado como pill (punto + etiqueta): Pendiente `#f2f2f3`, Cobrado/Pagado
    `#17191c` texto blanco, Vencida borde `#17191c`. Sin verdes ni rojos.
  - Acciones en "···" (menú con sombra: Editar / Eliminar), visible al hover en
    escritorio, siempre en móvil. Eliminar pide confirmación en **modal**; el confirm
    elimina a través de la ruta existente y recarga.
  - Selección múltiple con checkbox + barra de acciones masivas ("Eliminar
    seleccionados") y confirmación en modal; paginación con query string.
  - Fecha en formato corto ("7 oct 2026").
  - **Estado vacío** cuidado: icono sobre círculo `#f2f2f3`, titular serif, texto
    secundario y pill "Crear primer movimiento" (o "Limpiar filtros" si hay filtros).
- **Móvil (< `lg`)**: cada fila pasa a tarjeta apilada (concepto + importe arriba,
  fecha y estado abajo, acciones siempre visibles).
- **Mensajes de éxito → toast flotante** abajo a la derecha (fondo `#17191c`, texto
  blanco, radio 16 px, autodescartable a los 4 s), no una barra gris a todo el ancho.
- Nota: "Duplicar" del menú de acciones queda fuera hasta tener ruta propia (se
  respeta "no cambiar rutas/lógica"); el menú incluye Editar y Eliminar.

### Formulario de movimiento (Nuevo/Editar ingreso o gasto)

- **Mismo contenedor que Panel y Movimientos**: título serif 44 px y enlace
  "← Volver a movimientos" en la cabecera, alineados con la rejilla de tarjetas.
- **Dos columnas** (rejilla de 12): formulario a la izquierda (7/8 col) y
  **previsualización** a la derecha (5/4 col), `sticky top-20` mientras se hace
  scroll. En móvil la previsualización pasa a ir **encima** (`order-first`).
- **Previsualización** (`x-form-preview`): tarjeta con radio 24 px, etiqueta
  pequeña ("Total a cobrar"/"Total a pagar"), el **total en grande** (Inter
  44 px peso 450, `tabular-nums`) y el desglose Base/IVA/Retención debajo.
  **El color cambia con el tipo** (`transition-colors`): ingreso `#e8eef9` (azul
  de Ingresos del Panel), gasto `#fbe1d1` (durazno) con texto `#5d2a1a`. En
  gasto la retención IRPF no se muestra.
- **Sin tarjeta contenedora**: los campos se agrupan en **bloques gris suave**
  (`x-form-section`, `bg-mist`, radio 24 px) — Datos de la factura · Cliente y
  categoría · Importes · Repetición · Factura y notas. El **título de cada
  bloque va en serif 20 px** (`font-serif text-body-lg`, `#17191c`), para marcar
  jerarquía frente a las etiquetas de campo.
- **Inputs "flotantes"** (`x-form-input` / `x-form-select`): fondo `#ffffff`,
  sin borde, alto `py-3` y radio 16 px; texto **16 px** (`text-base`) y
  **etiquetas 14 px** (`text-sm`) para que ambos se sientan de la misma familia;
  foco con anillo interior `ring-2 ring-ink/15`; error con `ring-red-400` y
  mensaje en rojo. La **base imponible** es el campo protagonista: 28 px peso 450
  y símbolo `€` fijo dentro.
- **Importes por chips** (`x-rate-chips`): IVA (21/10/4/0), retención IRPF
  (15/7/0) y % deducible (100/50/0), con una opción **"Otro"** que revela un
  campo manual. El valor viaja en un `input hidden` con el mismo nombre de
  siempre, así la validación no cambia.
- **Cliente/proveedor** (`x-counterparty-combobox`): combobox con sugerencias de
  los nombres ya usados (por tipo) y autocompletado, navegable por teclado.
- **Categoría** (`x-category-chips`): chips por tipo en lugar de select nativo.
- **Selector Ingreso/Gasto**: píldora como la de trimestres del Panel (activo
  azul marino para ingreso, sienna para gasto); conmuta el color de la
  previsualización y el título de la página.
- **Barra de acciones fija** al pie (`sticky bottom-4`): "Cancelar" y el botón
  principal ("Guardar ingreso"/"Guardar gasto", o "Guardar cambios" al editar),
  con el estilo de las pills del Panel.
- La edición de recurrencia (`movements/recurring-edit`) reutiliza el mismo
  layout, secciones y componentes (con el tipo fijo y el estado Activa/Pausada).

### Perfil

- Mismo contenedor y título serif 44 px que Panel/Movimientos (con subtítulo
  "Tu cuenta y tus datos de acceso.").
- **Dos columnas**: a la izquierda, los bloques `x-form-section` **Información
  del perfil** y **Actualizar contraseña** (campos "flotantes", botón "Guardar"
  en pill `#17191c`); a la derecha, una tarjeta fija (`sticky top-20`) con el
  **resumen de la cuenta** (tarjeta `tint` `#e8eef9`, avatar circular `navy` con
  iniciales, nombre y correo) y la zona de **Eliminar cuenta** (tarjeta blanca
  con borde hairline y botón destructivo en rojo, que abre el modal de
  confirmación con el input ya restilado).
- El aviso de correo sin verificar y el estado "Guardado." mantienen su lógica;
  el aviso deja de usar verde (se pasa a `#17191c`, coherente con la paleta).
- **Tus datos**: bloque `x-form-section` con el botón **"Exportar copia completa
  (.zip)"** (CSV de movimientos/categorías/recurrentes/presentaciones + facturas,
  estas en `facturas/{año}/T{n}/ingresos|gastos/`).
- **Eliminar cuenta** borra también todos los datos del usuario (años, movimientos,
  facturas adjuntas, vencimientos y recurrencias) para no dejar registros huérfanos.
  El **único administrador** no puede darse de baja: la instancia se quedaría sin
  nadie que pueda invitar.

### Autenticación (login, contraseña y verificación)

- Layout de invitado a **dos columnas en escritorio** (`lg`), una en móvil:
  - **Izquierda** (solo escritorio): **foto de fondo** a sangre (webp en escala de
    grises, `object-cover`, `loading="eager"`) con dos capas: `bg-ink/40` y un
    degradado `from-ink/85 via-ink/25 to-transparent` desde abajo. Logo "Fiscal."
    en `paper` arriba a la izquierda; titular y párrafo **anclados abajo** al mismo
    margen. Titular serif, peso 400, `clamp(88px, 9vw, 140px)`, interlineado 0.95,
    tracking `-0.03em` y tres líneas; párrafo `text-paper/80`, ancho máximo 44ch.
  - **Derecha**: el formulario centrado sobre papel (ancho 448 → **512 px** en
    `lg`), con el logo en móvil; en `lg` el título sube a 40 px y los campos y el
    botón ganan un escalón de tamaño.
- Campos con el input "flotante" en tono **`mist`** (`x-form-input tone="mist"`),
  etiquetas `x-form-label` y botón principal a **ancho completo**. Se aplica a
  login, restablecer/nueva contraseña, confirmar contraseña y verificar correo.
- Los avisos de sesión (`x-auth-session-status`) se muestran en `#f2f2f3`, sin verde.

### Usuarios e invitaciones (multiusuario)

- **Datos aislados por usuario**: `fiscal_years`, `movements`, `recurring_movements`
  y `quarter_filings` llevan `user_id`; todas las consultas se filtran por el
  usuario autenticado y las rutas con ID comprueban propiedad (404 si no es suyo).
  Las **categorías** son un catálogo compartido.
- **Pantalla "Usuarios"** (solo el dueño, ítem en la barra lateral): genera
  **invitaciones** (enlace con token, caduca a los 7 días y sirve una vez) y lista
  las cuentas. El registro público sigue desactivado.
- **Alta por invitación**: `GET /invitacion/{token}` muestra un formulario (usa el
  layout de acceso) donde la persona elige nombre y contraseña; al aceptar se crea
  la cuenta, se marca la invitación como usada y se inicia sesión.
- El usuario **dueño** es el primero creado por el seeder (`is_admin`); es el único
  que puede invitar.

### Correcciones de producto

- **Próximo vencimiento**: la fecha de presentación pendiente más cercana (igual o
  posterior a hoy); se **agrupan todos los modelos que vencen ese día** (303 y 130) y se
  suma el importe a pagar. Los vencidos sin marcar aparecen en el checklist "Ponte al
  día" con enlace a `/presentaciones`.
- El selector de año no tiene la flecha pisando el texto (chevron con padding
  reservado).

### Componentes reutilizables

- `x-sidebar` — sidebar escritorio + cajón móvil con velo.
- `x-topbar` — barra superior con título de página y "Añadir movimiento" (oculto en
  movimientos).
- `x-metric-tile` — tarjeta gris con título, cifra, chip de estado, pares
  etiqueta/valor y sparkline opcional.
- `x-card` — variantes `neutral`, `accent`, `white` (borde) y `tint` (`#e8eef9`);
  la sombra flotante se añade por clase donde corresponde.
- `x-form-section`, `x-form-label`, `x-form-input`, `x-form-select` — bloques y
  campos "flotantes" del formulario de movimiento.
- `x-rate-chips`, `x-category-chips`, `x-counterparty-combobox`,
  `x-form-preview` — selector de importes, categoría, cliente y previsualización.
