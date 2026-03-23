# Design System

## Direction

Personality: Precision & Density
Foundation: Cool (slate)
Depth: Borders-only

## Tokens

### Spacing

Base: 4px
Scale: 4, 8, 12, 16, 24, 32

### Colors

--foreground: slate-900
--muted: slate-600
--background: slate-50
--surface: slate-100
--accent: blue-600
--success: green-600
--warning: amber-500
--danger: red-600

### Typography

--font-sans: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial
--scale-h1: 28px/36px
--scale-h2: 20px/28px
--scale-body: 14px/20px
--scale-small: 12px/16px

### Dark Mode Tokens

--foreground-dark: slate-50
--muted-dark: slate-400
--background-dark: slate-950
--surface-dark: slate-900
--accent-dark: sky-400

## Depth / Surfaces

- Strategy: Borders-only
- Surface lightness steps: surface, surface+1 (slightly lighter), surface+2
- Border: rgba(15,23,42,0.06) (subtle, low-contrast)

## Patterns

### Button Primary

- Height: 36px
- Padding: 8px 12px
- Radius: 6px
- Background: `--accent`
- Text: `--foreground`

### Button Secondary

- Height: 36px
- Padding: 8px 12px
- Radius: 6px
- Background: transparent
- Border: 1px solid rgba(15,23,42,0.06)

### Card Default

- Border: 1px solid rgba(15,23,42,0.04)
- Padding: 16px
- Radius: 8px

### Form Field

- Height: 40px
- Padding: 8px 12px
- Background: `--surface`
- Border: 1px solid rgba(15,23,42,0.04)

## Accessibility

- Contrast: ensure text on `--accent` meets AA on buttons
- Focus: 2px outline using a 20% darker `--accent`

## Usage Notes

- Spacing must use the defined scale; avoid ad-hoc values.
- Use borders-only depth for all primary layouts; reserve shadows for explicit modal layering.
- Save patterns here when reused 2+ times across the project.

---

Generated: initial system for CanchaAlquiler — direction: Precision & Density
