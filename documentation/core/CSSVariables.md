# CSS Variables Reference

Complete reference for all CSS variables used in SystemDeck, including WordPress admin color scheme integration.

## Table of Contents

1. [Core Variables](#core-variables)
2. [WordPress Preset Colors](#wordpress-preset-colors)
3. [Admin Color Scheme Variables](#admin-color-scheme-variables)
4. [Dark Mode Overrides](#dark-mode-overrides)
5. [Variable Naming Conventions](#variable-naming-conventions)
6. [Usage Examples](#usage-examples)

---

## Core Variables

Defined in [`sd-core.css`](file:///Users/glwalker/DevKinsta/public/thedrawer/wp-content/plugins/system-deck/assets/css/sd-core.css)

### Layout Dimensions

```css
--sd-header-h: 40px; /* Header bar height */
--sd-sidebar-w: 400px; /* Side dock width */
--sd-menu-wide: 160px; /* Expanded menu width */
--sd-menu-folded: 36px; /* Collapsed menu width */
--sd-min-dock-h: 40px; /* Minimized dock height */
```

### Typography & Breakpoints

```css
--sd-base-font-size: 16px; /* Anchor for em units */
--sd-font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, ...;

--sd-breakpoint-sm: 360px;
--sd-breakpoint-md: 782px; /* WordPress admin breakpoint */
--sd-breakpoint-lg: 783px;
```

### Core Semantic Colors

```css
--sd-body-background: #f1f1f1; /* Light mode workspace background */
--sd-card-bg: #ffffff; /* Card/widget background */
--sd-border: #c3c4c7; /* Standard border color */
--sd-text: #3c434a; /* Primary text color */
--sd-text-muted: #646970; /* Secondary/muted text */
```

### Accent & Utility

```css
--sd-accent: #2271b1; /* Theme accent (derived from highlight) */
--sd-accent-rgb: 34, 113, 177; /* For rgba() usage */
```

### Resize System

```css
--sd-resize-glow: var(--wp--preset--color--vivid-cyan-blue);
--sd-resize-glow-blur: 12px;
--sd-resize-glow-spread: -6px;
```

### Z-Index & Transitions

```css
--sd-z-drawer: 99998;
--sd-transition: 0.15s ease-in-out;
```

---

## WordPress Preset Colors

Standard WordPress color palette, available for use throughout SystemDeck:

```css
--wp--preset--color--black: #000000;
--wp--preset--color--white: #ffffff;
--wp--preset--color--cyan-bluish-gray: #abb8c3;
--wp--preset--color--pale-pink: #f78da7;
--wp--preset--color--vivid-red: #cf2e2e;
--wp--preset--color--luminous-vivid-orange: #ff6900;
--wp--preset--color--luminous-vivid-amber: #fcb900;
--wp--preset--color--light-green-cyan: #7bdcb5;
--wp--preset--color--vivid-green-cyan: #00d084;
--wp--preset--color--pale-cyan-blue: #8ed1fc;
--wp--preset--color--vivid-cyan-blue: #0693e3;
--wp--preset--color--vivid-purple: #9b51e0;
```

### Usage Example

```css
#systemdeck .error-notice {
	border-left-color: var(--wp--preset--color--vivid-red);
}

#systemdeck .success-icon {
	color: var(--wp--preset--color--vivid-green-cyan);
}
```

---

## Admin Color Scheme Variables

SystemDeck automatically syncs with WordPress admin color schemes, including support for the [Admin Color Schemes plugin](https://wordpress.org/plugins/admin-color-schemes/).

### Dynamically Injected Variables

These variables are **PHP-generated** via [`Assets.php`](file:///Users/glwalker/DevKinsta/public/thedrawer/wp-content/plugins/system-deck/includes/Core/Assets.php) based on the user's selected admin color scheme:

#### Global Interactive Colors

```css
--sd-link: #2271b1; /* Default link color */
--sd-link-focus: #135e96; /* Link hover/focus color */
--sd-highlight-color: #2271b1; /* Primary button/form focus */
--sd-button-color: #2271b1; /* Button text color */
--sd-notification-color: #d63638; /* Notification/warning color */
```

#### Menu & Admin Bar Colors

```css
--sd-menu-background: #1d2327;
--sd-menu-text: #fff;
--sd-menu-icon: #f5f7f8;

--sd-menu-highlight-background: #1d2327;
--sd-menu-highlight-text: #72aee6;
--sd-menu-highlight-icon: #72aee6;

--sd-menu-current-background: #2271b1;
--sd-menu-current-text: #fff;
--sd-menu-current-icon: #fff;

--sd-menu-submenu-background: #2c3339;
--sd-menu-submenu-text: #a2aab2;
--sd-menu-submenu-focus-text: #72aee6;
```

### Supported Color Schemes

SystemDeck supports **21 total color schemes**:

**WordPress Core (8):**

-   Fresh (default)
-   Blue
-   Coffee
-   Ectoplasm
-   Light
-   Midnight
-   Modern
-   Ocean
-   Sunrise

**Admin Color Schemes Plugin (13):**

-   80s Kid
-   Adderley
-   Aubergine
-   Contrast Blue
-   Cruise
-   Flat
-   Kirk
-   Lawn
-   Modern Evergreen
-   Primary
-   Seashore
-   Vinyard

All schemes are defined in [`sd-variables.css`](file:///Users/glwalker/DevKinsta/public/thedrawer/wp-content/plugins/system-deck/assets/css/sd-variables.css).

---

## Dark Mode Overrides

Dark mode is activated via `data-theme="dark"` attribute on `#systemdeck`.

### Dark Mode Variables

Defined in [`sd-general.css`](file:///Users/glwalker/DevKinsta/public/thedrawer/wp-content/plugins/system-deck/assets/css/sd-general.css):

```css
#systemdeck[data-theme="dark"] {
	/* Only swap neutral workspace colors */
	--sd-body-background: #1a1a1a;
	--sd-text: #e4e4e7;
	--sd-border: #3f3f46;

	/* Declare dark mode to browser */
	color-scheme: dark;
}
```

### Dark Mode Philosophy

> **Important:** Dark mode **only affects neutral colors** (backgrounds, text, borders). All interactive elements (menu, buttons, links) preserve their WordPress admin color scheme colors for consistency.

---

## Variable Naming Conventions

### Prefix System

| Prefix                  | Purpose                       | Example                          |
| ----------------------- | ----------------------------- | -------------------------------- |
| `--sd-`                 | SystemDeck-specific variables | `--sd-header-h`                  |
| `--wp--preset--color--` | WordPress default colors      | `--wp--preset--color--vivid-red` |

### Semantic Naming

Variables use descriptive, semantic names:

```css
/* ✅ Good - Semantic */
--sd-menu-highlight-text
--sd-notification-color
--sd-body-background

/* ❌ Avoid - Implementation details */
--sd-color-blue-1
--sd-bg-dark
--sd-font-14
```

### Hierarchy

Variables follow a logical hierarchy:

```
--sd-menu-*               (Menu container)
  --sd-menu-text          (Default state)
  --sd-menu-highlight-*   (Hover state)
  --sd-menu-current-*     (Active/selected state)
  --sd-menu-submenu-*     (Submenu context)
```

---

## Usage Examples

### Using Core Variables

```css
/* Typography with em scaling */
.my-widget {
	font-size: 0.875em; /* 14px relative to --sd-base-font-size */
	color: var(--sd-text);
	padding: 0.75em; /* 12px, scales with font */
}

/* Hardware dimensions in px */
.my-header {
	height: var(--sd-header-h); /* 40px, fixed */
	border: 1px solid var(--sd-border);
}
```

### Using WordPress Colors

```css
/* Status indicators */
.success-badge {
	background: var(--wp--preset--color--vivid-green-cyan);
	color: var(--wp--preset--color--white);
}

.error-badge {
	background: var(--wp--preset--color--vivid-red);
	color: var(--wp--preset--color--white);
}
```

### Using Menu Variables

```css
/* Custom menu item */
.my-menu-item {
	color: var(--sd-menu-text);
	background: var(--sd-menu-background);
}

.my-menu-item:hover {
	color: var(--sd-menu-highlight-text);
	background: var(--sd-menu-highlight-background);
}
```

### Dark Mode Aware Styles

```css
/* Automatically adapts to dark mode */
.my-card {
	background: var(--sd-card-bg);
	border: 1px solid var(--sd-border);
	color: var(--sd-text);
}

/* Specific dark mode override */
#systemdeck[data-theme="dark"] .my-special-element {
	box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
}
```

---

## Best Practices

### ✅ Do

-   Use CSS variables for all colors
-   Use `em` for scalable spacing/typography
-   Use `px` for fixed hardware dimensions
-   Reference existing variables before creating new ones
-   Use WP preset colors for standard UI states

### ❌ Don't

-   Hardcode hex colors
-   Use var() fallback values (PHP provides defaults)
-   Create duplicate variables
-   Override dark mode menu colors (preserves admin scheme)

---

## See Also

-   [Style Architecture](./StyleArchitecture.md) - Overall CSS strategy
-   [Caching Strategy](./CachingStrategy.md) - How dynamic CSS is cached
-   [Assets.php Source](file:///Users/glwalker/DevKinsta/public/thedrawer/wp-content/plugins/system-deck/includes/Core/Assets.php) - Variable injection
