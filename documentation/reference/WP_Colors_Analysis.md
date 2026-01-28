# WordPress Admin Color Scheme Analysis: Ectoplasm

**Source File:** `wp-admin/css/colors/ectoplasm/colors.css`

This report analyzes the styles used in the "Ectoplasm" admin color scheme to identify reusable patterns, variable mappings, and missing UI polish for SystemDeck.

## 1. Color Mapping Strategy

SystemDeck uses a set of CSS variables (`--sd-*`) injected by `Assets.php`. This analysis confirms how WP core colors map to these variables.

| WP Context             | Ectoplasm Value   | SystemDeck Variable | Notes                                 |
| :--------------------- | :---------------- | :------------------ | :------------------------------------ |
| **Body Background**    | `#f1f1f1`         | `--sd-canvas-bg`    | Standard WP gray.                     |
| **Admin Menu BG**      | `#523f6d`         | `--sd-base-dark`    | Used for Admin Bar & Menu.            |
| **Highlight / Accent** | `#a3b745`         | `--sd-highlight`    | Buttons, Focus rings, Active links.   |
| **Text (Menu)**        | `#ece6f6`         | `--sd-menu-text`    | Slightly muted white for icons/text.  |
| **Text (Active)**      | `#fff`            | `--sd-text`         | Active menu items.                    |
| **Notification**       | `#d46f15`         | `--sd-notification` | Update bubbles, Recovery Mode.        |
| **Hover BG**           | `rgb(64, 49, 86)` | `--sd-ac-highlight` | Darker/Lighter shift for menu hovers. |

## 2. Reusable UI Patterns (The "WP Polish")

These are specific CSS rules found in `colors.css` that define the "feel" of the WordPress Admin. We should replicate these in `sd-wpcolors.css` using our variables.

### A. Focus Rings (Accessibility)

WP uses a distinct "Double Ring" for primary buttons to ensure visibility on any background.

```css
/* WP Core */
.button-primary:focus {
	box-shadow: 0 0 0 1px #fff, 0 0 0 3px #a3b745;
}
/* SystemDeck Implementation */
.sd-btn-primary:focus {
	box-shadow: 0 0 0 1px #fff, 0 0 0 3px var(--sd-highlight);
}
```

### B. Form Inputs

Inputs get a border color change AND a shadow.

```css
/* WP Core */
input:focus,
select:focus,
textarea:focus {
	border-color: #a3b745;
	box-shadow: 0 0 0 1px #a3b745;
}
/* SystemDeck Implementation */
.sd-input:focus {
	border-color: var(--sd-highlight);
	box-shadow: 0 0 0 1px var(--sd-highlight);
}
```

### C. Admin Menu / Dock Items

The specific interaction of text color going to white + background changing is critical.

```css
/* WP Core */
#adminmenu li.menu-top:hover {
	color: #fff;
	background-color: #a3b745;
}
```

### D. Checkboxes & Radios

WP uses detailed SVG replacements or simple borders.

```css
/* WP Core */
input[type="checkbox"]:checked::before {
	content: url(...); /* Uses fill='#523f6d' */
}
```

_Note: For SystemDeck, we might stick to CSS-only styling using `--sd-highlight` to avoid complex SVG generation PHP-side, or use Dashicons._

## 3. Recommendations for `sd-wpcolors.css`

Create a new file `assets/css/sd-wpcolors.css` that serves as a **Theme Bridge**. It should contain generic class mappings that apply the specific "WP Polish" using our dynamic variables.

### Proposed Structure:

1.  **Form Elements**: `input`, `select`, `textarea` focus states.
2.  **Buttons**: Replicate `.button` and `.button-primary` styling exactly (heights, padding, focus rings).
3.  **Links**: Standard WP link colors (`#0073aa` default, variable on hover).
4.  **Notices**: `.notice`, `.updated`, `.error` styles using `--sd-notification`.
5.  **List Tables**: Styles for standard WP list tables if we render them inside the deck.

This separation allows `sd-core.css` to handle layout and `sd-general.css` to handle generic UI, while `sd-wpcolors.css` specifically enforces _Theme Compatibility_.
