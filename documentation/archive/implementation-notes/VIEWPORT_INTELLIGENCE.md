# Viewport Intelligence Status: Active

## Context Detection Implemented

SystemDeck now intelligently detects its environment and adapts its layout strategy.

### 1. Context Class Injection (`HtmlAttributes.php`)

The main wrapper (`#systemdeck`) now receives a context class:

- **`sd-context-admin`**: Use when inside `wp-admin`. Sits within the WP wrapper flow.
- **`sd-context-retail`**: Use when on the Frontend. Configured as a high-z-index overlay.

### 2. Retail (Frontend) Overlay Strategy (`sd-core.css`)

Specific styles are now applied to `.sd-context-retail`:

- **Z-Index**: `999999` (Above all theme elements)
- **Position**: `fixed`
- **Admin Bar Awareness**: Automatically adjusts `top` offset (`32px` desktop, `46px` mobile) if `.admin-bar` is present on the body.

### 3. Screen Options Scoping (`sd-screen-meta.css`)

The Tool Box (Screen Options) is strictly scoped to `#sd-workspace-content`.

- **Absolute Positioning**: Anchored to the top-right of the content area.
- **Z-Index Management**: Links (`100`) sit above Panel (`99`) to create the seamless tab effect.
- **Frontend Checkboxes**: Styling forced to match WP Admin visuals even on frontend themes.

## Verification Steps

1.  **Frontend Layout**: Open SystemDeck on the homepage.
    - [ ] Should float above the site header.
    - [ ] Should not be obscured by theme z-indexes.
2.  **Admin Bar Sync**:
    - [ ] Toggle Admin Bar on/off in user profile.
    - [ ] SystemDeck should adjust its top position accordingly.
3.  **Tool Box**:
    - [ ] Should appear inside the deck, top-right.
    - [ ] Checkboxes should look standard (blue check, white bg) regardless of theme.

---

_Viewport Intelligence Logic Deployed via `HtmlAttributes` and `sd-core.css`._
