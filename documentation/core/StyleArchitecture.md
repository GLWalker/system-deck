# SystemDeck Style Architecture

This document outlines the CSS strategy used in SystemDeck to ensure stability, scalability, and "Native WP" fidelity across hostile frontend and backend environments.

## Table of Contents

1. [The "Safe EM" Strategy](#the-safe-em-strategy)
2. [CSS File Organization](#css-file-organization)
3. [Variable System](#variable-system)
4. [Dark Mode & Theming](#dark-mode--theming)
5. [Comment & Documentation Standards](#comment--documentation-standards)

---

## 1. The "Safe EM" Strategy

SystemDeck operates as an OS-like shell injected into varied environments (WP Admin, Frontend Themes, Page Builders). These environments have inconsistent global settings (e.g., `html { font-size: 62.5% }` vs `16px`).

To protect SystemDeck's layout from shrinking or exploding based on the host theme, we employ a **Safe EM Strategy**:

### The "Hard Deck" Anchor

We explicitly set a base font size on the root container in `sd-core.css`:

```css
/* sd-core.css */
#systemdeck {
	--sd-base-font-size: 16px; /* The System Truth */
	font-size: var(--sd-base-font-size);
}
```

This creates a stable "iframe-like" bubble where `1em` always equals `16px`, regardless of the global `<html>` font size.

### Unit Usage Rules

We strictly separate **Hardware** (Shell) from **Software** (Content).

| Component Type                   | Unit | Reason                                     | Example                        |
| :------------------------------- | :--- | :----------------------------------------- | :----------------------------- |
| **Typography**                   | `em` | Scales relatively to our 16px anchor.      | `font-size: 0.875em;` (14px)   |
| **Spacing (Padding/Margin/Gap)** | `em` | Layout should breathe with text size.      | `gap: 1.25em;` (20px)          |
| **Border Radius**                | `em` | Scales proportionally with UI elements.    | `border-radius: 0.25em;` (4px) |
| **Hardware Shell**               | `px` | Must align perfectly with WP Admin Bar.    | `height: 40px;` (Header)       |
| **Borders**                      | `px` | Sub-pixel rendering can blur borders.      | `border: 1px solid...`         |
| **Resize Handles**               | `px` | Physical touch targets should be constant. | `width: 24px;`                 |
| **Min/Max Dimensions**           | `px` | Definitive constraints.                    | `min-height: 120px;`           |
| **Box Shadows**                  | `px` | Optical precision required.                | `box-shadow: 0 2px 4px...`     |

---

## 2. CSS File Organization

SystemDeck uses a specialized **4-file CSS architecture** for production, with color schemes dynamically injected via PHP.

### File Structure

```
assets/css/
├── sd-core.css        # Framework & layout (LOCKED)
├── sd-menumain.css    # Menu & navigation
├── sd-wpcolors.css    # WordPress UI bridge
└── sd-general.css     # UI components & styling

documentation/reference/
└── ColorSchemes_Reference.css  # All 21 color schemes (reference only)
```

### File Responsibilities

#### A. **sd-core.css** - The Hardware (LOCKED)

-   **Role**: The "OS Kernel"
-   **Contents**:
    -   CSS variable definitions (dimensions, z-index, breakpoints)
    -   WP preset color definitions
    -   Scoped resets
    -   System framework (positioning, flexbox)
    -   Docking system (all dock modes)
    -   Resize handles
-   **Edit Policy**: 🔒 **LOCKED** - Do not edit unless changing core physics
-   **Units**: Mixed - `px` for logical anchors, `em` for inner padding

**Key Principle:** This file defines the "physics" of SystemDeck. Changes here affect fundamental behavior.

---

#### B. **sd-menumain.css** - Navigation

-   **Role**: Menu and navigation system
-   **Contents**:
    -   Header bar
    -   Sidebar menu (expanded/collapsed states)
    -   Submenu flyouts
    -   Current/active states
    -   Triangle pointer
    -   Icons
    -   Collapse button
    -   Folded state logic
    -   RTL overrides
-   **Edit Policy**: Open for navigation styling

**Key Feature:** Automatically syncs with WordPress admin color schemes for native feel.

---

#### C. **sd-wpcolors.css** - WordPress UI Bridge

-   **Role**: WordPress-compatible UI components
-   **Contents**:
    -   Buttons matching `wp-core-ui`
    -   Form elements (inputs, checkboxes, radios, selects)
    -   Notices & bubbles
    -   List tables
-   **Edit Policy**: Open, but maintain WP core compatibility

**Purpose:** Makes SystemDeck components feel indistinguishable from native WordPress admin UI.

---

#### D. **sd-general.css** - The Software

-   **Role**: The "UI Skin"
-   **Contents**:
    -   Typography
    -   Buttons & controls
    -   Grid & sortable styles
    -   Full dock styles
    -   Dark mode theme
    -   Widget-specific styles
-   **Edit Policy**: ✅ Open for design iteration
-   **Units**: Predominantly `em` for fluid, accessible scaling

**Key Sections:**

1. Basic Elements & Typography
2. Buttons & Controls
3. Grid & Sortable Styles
4. Full Dock Styles
5. Theme: Dark Mode
6. Widget: Notes

---

### Loading Order & Dependencies

```
1. sd-core.css      (Base variables, framework)
    ↓
2. [Dynamic CSS]    (PHP-injected, 21 color schemes)
    ↓
3. sd-menumain.css  (Navigation)
4. sd-wpcolors.css  (WordPress bridge)
5. sd-general.css   (UI components)
```

**Critical:** `sd-general.css` depends on `sd-core.css` being loaded first. Color scheme variables are injected dynamically via inline styles based on user preference.

**Color Schemes:** All 21 schemes (8 WordPress Core + 13 from [Admin Color Schemes plugin](https://wordpress.org/plugins/admin-color-schemes/)) are PHP-generated and cached. See [ColorSchemes_Reference.css](file:///Users/glwalker/DevKinsta/public/thedrawer/wp-content/plugins/system-deck/documentation/reference/ColorSchemes_Reference.css) for complete definitions.

---

## 3. Variable System

### Variable Categories

SystemDeck uses CSS variables for all colors and dimensions.

#### Core Variables (sd-core.css)

```css
/* Layout */
--sd-header-h: 40px;
--sd-sidebar-w: 400px;
--sd-menu-wide: 160px;
--sd-menu-folded: 36px;

/* Colors */
--sd-body-background: #f1f1f1;
--sd-card-bg: #ffffff;
--sd-border: #c3c4c7;
--sd-text: #3c434a;
--sd-text-muted: #646970;

/* Accent */
--sd-accent: #2271b1;
--sd-accent-rgb: 34, 113, 177;
```

#### WordPress Preset Colors

12 standard WordPress colors available for use:

```css
--wp--preset--color--vivid-red: #cf2e2e;
--wp--preset--color--vivid-green-cyan: #00d084;
--wp--preset--color--vivid-cyan-blue: #0693e3;
/* ... etc ... */
```

#### Dynamic Color Variables (PHP-Injected)

17 variables per user, based on their selected admin color scheme:

```css
--sd-link
--sd-link-focus
--sd-highlight-color
--sd-button-color
--sd-notification-color
--sd-menu-background
--sd-menu-text
--sd-menu-icon
--sd-menu-highlight-background
--sd-menu-highlight-text
--sd-menu-highlight-icon
--sd-menu-current-background
--sd-menu-current-text
--sd-menu-current-icon
--sd-menu-submenu-background
--sd-menu-submenu-text
--sd-menu-submenu-focus-text
```

### Naming Conventions

| Prefix                  | Purpose              | Example                          |
| ----------------------- | -------------------- | -------------------------------- |
| `--sd-`                 | SystemDeck variables | `--sd-header-h`                  |
| `--wp--preset--color--` | WordPress colors     | `--wp--preset--color--vivid-red` |

**Rules:**

-   Use semantic names: `--sd-menu-text` not `--sd-white-1`
-   Follow hierarchy: `--sd-menu-highlight-text`
-   No fallback values in `var()` (PHP provides defaults)

---

## 4. Dark Mode & Theming

### Activation

Dark mode is triggered via `data-theme="dark"` attribute on `#systemdeck`.

```javascript
document.getElementById("systemdeck").setAttribute("data-theme", "dark")
```

### Implementation

Defined in `sd-general.css`:

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

> **Critical Design Decision:** Dark mode **ONLY affects neutral colors** (backgrounds, text, borders).
>
> **All interactive elements preserve their WordPress admin color scheme colors:**
>
> -   Menu colors (header bar, sidebar)
> -   Button colors
> -   Link colors
> -   Form focus states
>
> **Why?** To maintain the "native WordPress" feel and respect the user's chosen admin color scheme.

### Additional Dark Mode Styling

```css
/* Workspace grid background */
#systemdeck[data-theme="dark"] #sd-workspace-content {
	background-color: #0f0f0f;
	background-image: linear-gradient(
			rgba(255, 255, 255, 0.02) 1px,
			transparent 1px
		), linear-gradient(90deg, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
	background-size: 40px 40px;
}

/* Cards */
#systemdeck[data-theme="dark"] .postbox,
#systemdeck[data-theme="dark"] .adm-card {
	background-color: #2d2d2d;
	border-color: var(--sd-border);
}
```

---

## 5. Comment & Documentation Standards

### File Headers

Every CSS file includes:

1. File description
2. Table of Contents
3. Section headers

**Example:**

```css
/**
 * SystemDeck General Styles
 * UI components, controls, grid layouts, and dark mode theming.
 *
 * TABLE OF CONTENTS:
 * 1. Basic Elements & Typography
 * 2. Buttons & Controls
 * 3. Grid & Sortable Styles
 * 4. Full Dock Styles
 * 5. Theme: Dark Mode
 * 6. Widget: Notes
 */
```

### Section Headers

Standard format across all files:

```css
/* =========================================
   SECTION NAME
   ========================================= */
```

**Why this format?**

-   Instantly recognizable when scanning
-   Standard across industry
-   Supported by editor folding/navigation
-   Easy to search (`/==`)

### Inline Comments

```css
/* Descriptive comments for complex logic */
.sd-handle-n {
	cursor: ns-resize;
	height: 24px; /* Big hit area */
	top: -12px; /* Centered on edge */
}
```

---

## Best Practices Summary

### ✅ Do

-   Use CSS variables for all colors
-   Use `em` for scalable dimensions (padding, margin, gap, font-size)
-   Use `px` for fixed hardware dimensions (heights, widths, borders)
-   Define variables in `sd-core.css`
-   Respect the dark mode philosophy (neutral colors only)
-   Include TOC in file headers
-   Use standard section headers

### ❌ Don't

-   Hardcode hex colors
-   Use `var()` fallback values (PHP provides defaults)
-   Override menu/admin colors in dark mode
-   Edit `sd-core.css` without understanding impact
-   Create duplicate variables
-   Mix units (don't use `px` for padding if others use `em`)

---

## WordPress Integration

### Admin Color Scheme Support

SystemDeck supports **21 color schemes**:

**WordPress Core (8):**
Fresh, Blue, Coffee, Ectoplasm, Light, Midnight, Modern, Ocean, Sunrise

**Admin Color Schemes Plugin (13):**
80s Kid, Adderley, Aubergine, Contrast Blue, Cruise, Flat, Kirk, Lawn, Modern Evergreen, Primary, Seashore, Vinyard

**Plugin:** [Admin Color Schemes](https://wordpress.org/plugins/admin-color-schemes/)

### Color Scheme Caching

User-specific CSS is cached for performance. See [Caching Strategy](./CachingStrategy.md) for details.

---

## See Also

-   [CSS Variables Reference](./CSSVariables.md) - Complete variable documentation
-   [Caching Strategy](./CachingStrategy.md) - Dynamic CSS caching
-   [File Structure](../reference/FileStructure.md) - Complete file organization
-   [Assets.php](file:///Users/glwalker/DevKinsta/public/thedrawer/wp-content/plugins/system-deck/includes/Core/Assets.php) - Variable injection source
