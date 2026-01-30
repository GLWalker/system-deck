# WordPress Components CSS Reference

## 📍 **Where The Styles Are Loaded**

### In SystemDeck

**File:** `/includes/Core/Assets.php` (Line 79)

```php
wp_enqueue_style('wp-components');
```

This WordPress function automatically loads:

- **Path:** `/wp-includes/css/dist/components/style.min.css`
- **Size:** ~100 KB (minified)
- **Version:** Matches your WordPress version (6.9 or similar)

---

## 🎨 **Components We're Using**

### From `wp.components`:

1. **`__experimentalGrid` (Grid)**
    - Main grid container
    - CSS Classes: `.components-grid`
    - Experimental component (not in docs yet)

2. **`Card`**
    - Used for: Pinned metrics display
    - CSS Classes: `.components-card`, `.components-card__body`
    - Border, shadow, padding styles

3. **`CardBody`**
    - Content wrapper inside Card
    - CSS Classes: `.components-card__body`
    - Padding and spacing

4. **`Panel`**
    - Used for: Widget containers
    - CSS Classes: `.components-panel`, `.components-panel__header`, `.components-panel__body`
    - Collapsible container

5. **`PanelBody`**
    - Collapsible section inside Panel
    - CSS Classes: `.components-panel__body`, `.components-panel__body-toggle`
    - Has built-in collapse/expand button

6. **`Button`**
    - Used for: Unpin button
    - CSS Classes: `.components-button`, `.is-destructive`, `.is-small`
    - WordPress standard button styles

---

## 📚 **CSS File Locations**

### Your WordPress Installation:

```
/wp-includes/css/dist/components/
├── style.css           (108 KB - Development)
├── style.min.css       (100 KB - Production)  ✅ This is loaded
├── style-rtl.css       (Right-to-Left)
└── style-rtl.min.css   (RTL minified)
```

### Other Related WordPress Styles:

```
/wp-includes/css/dist/
├── components/         ✅ We use this
├── block-editor/       (Block editor UI)
├── editor/             (Classic + block editor)
├── base-styles/        (CSS reset/base)
└── admin-ui/           (Admin interface)
```

---

## 🌐 **Online Documentation & Source**

### Official WordPress Component Library:

**Storybook (Interactive Docs):**

- https://wordpress.github.io/gutenberg/?path=/docs/components-intro--docs
- **Search for:** Card, Panel, Button, Grid

### GitHub Source (CSS):

**Components Package:**

- https://github.com/WordPress/gutenberg/tree/trunk/packages/components
- **Styles:** `/packages/components/src/`
- Each component has its own `/style.scss` file

### Specific Components:

1. **Card:**
    - Docs: https://wordpress.github.io/gutenberg/?path=/docs/components-card--docs
    - Source: https://github.com/WordPress/gutenberg/tree/trunk/packages/components/src/card

2. **Panel:**
    - Docs: https://wordpress.github.io/gutenberg/?path=/docs/components-panel--docs
    - Source: https://github.com/WordPress/gutenberg/tree/trunk/packages/components/src/panel

3. **Button:**
    - Docs: https://wordpress.github.io/gutenberg/?path=/docs/components-button--docs
    - Source: https://github.com/WordPress/gutenberg/tree/trunk/packages/components/src/button

4. **Grid (Experimental):**
    - Source: https://github.com/WordPress/gutenberg/tree/trunk/packages/components/src/grid
    - **Note:** Not in official docs yet (experimental)

---

## 🔧 **How to Override Component Styles**

### Priority Order (Specificity):

1. **WordPress default** (lowest): `.components-panel`
2. **Your custom class**: `.sd-grid-widget.components-panel`
3. **Inline styles**: `style={{ ... }}` (highest)
4. **!important** (nuclear option): Avoid unless necessary

### Example Override in `sd-temp.css`:

```css
/* Override Panel default padding */
.sd-grid-widget .components-panel {
	margin: 0;
	box-sizing: border-box;
}

/* Override Card default margin */
.sd-grid-pin .components-card {
	margin: 0;
}
```

---

## 📦 **Local Copy for Inspection**

To view the full WordPress components CSS:

```bash
# View the minified version
cat /Users/glwalker/DevKinsta/public/thedrawer/wp-includes/css/dist/components/style.css

# Or prettier version for studying
code /Users/glwalker/DevKinsta/public/thedrawer/wp-includes/css/dist/components/style.css
```

---

## 🎯 **Key CSS Classes in Use**

| Component | Main Class                | Modifiers                      | Purpose             |
| --------- | ------------------------- | ------------------------------ | ------------------- |
| Grid      | `.components-grid`        | -                              | CSS Grid container  |
| Card      | `.components-card`        | `.is-elevation-{n}`            | Metric cards        |
| CardBody  | `.components-card__body`  | `.is-size-small`               | Card content        |
| Panel     | `.components-panel`       | -                              | Widget wrapper      |
| PanelBody | `.components-panel__body` | `.is-opened`                   | Collapsible section |
| Button    | `.components-button`      | `.is-destructive`, `.is-small` | Unpin button        |

---

## 💡 **Pro Tips**

1. **Inspect in Browser DevTools:**
    - Right-click component → Inspect
    - See exact classes and inherited styles
    - Find what CSS file they come from

2. **Common Overrides Needed:**
    - Remove margins: `margin: 0`
    - Fix box-sizing: `box-sizing: border-box`
    - Control heights: `height: auto`, `min-height: 0`

3. **Component Variants:**
    - Check Storybook for available props
    - Props like `size="small"` add CSS classes
    - Example: `<Card size="small">` → `.is-size-small`

---

## ✅ **What's Already Loaded in SystemDeck**

When you call `wp_enqueue_style('wp-components')`, WordPress automatically handles:

- ✅ Loading the CSS file
- ✅ Version management (cache busting)
- ✅ Dependencies (loads `wp-admin` styles first)
- ✅ RTL support (loads RTL version in RTL languages)

**You don't need to manually link any CSS files!**
