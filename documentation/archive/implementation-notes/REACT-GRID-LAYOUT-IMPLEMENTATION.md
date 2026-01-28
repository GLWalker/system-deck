# SystemDeck React Grid Layout Implementation

## Change Summary - January 20, 2026

---

## 🎯 **OBJECTIVE**

Replace custom drag-and-drop implementation with React Grid Layout (RGL) - the industry-standard library used by Grafana, BitMEX, etc.

---

## 📦 **NEW DEPENDENCIES ADDED**

### 1. React Grid Layout Library (Local Files)

**Location:** `assets/vendor/react-grid-layout/`

- `react-grid-layout.min.js` (84KB) - UMD build for browser
- `styles.css` (2.7KB) - Core RGL styles

**Download Method:**

```bash
curl -L https://unpkg.com/react-grid-layout@1.4.4/dist/react-grid-layout.min.js
curl -L https://unpkg.com/react-grid-layout@1.4.4/css/styles.css
```

**Reason:** No CDN usage - all assets served locally for performance, security, and offline development.

---

## 🔧 **FILES MODIFIED**

### 1. **`includes/Core/Assets.php`**

**Changes:**

- Added React Grid Layout CSS enqueue (line ~74)
- Added React Grid Layout JS enqueue before workspace component (line ~79)
- Updated `sd-workspace-react` dependencies to include `react-grid-layout` (line ~82)

**Code Added:**

```php
// React Grid Layout CSS
wp_enqueue_style('react-grid-layout', SD_URL . 'assets/vendor/react-grid-layout/styles.css', [], '1.4.4');

// React Grid Layout Library (before our workspace component)
wp_enqueue_script('react-grid-layout', SD_URL . 'assets/vendor/react-grid-layout/react-grid-layout.min.js', $react_deps, '1.4.4', true);

// Our workspace component (depends on react-grid-layout)
wp_enqueue_script('sd-workspace-react', SD_URL . 'assets/js/sd-workspace.js', array_merge($react_deps, ['react-grid-layout']), SD_VERSION, true);
```

---

### 2. **`includes/Core/RetailController.php`**

**Changes:**

- Added same React Grid Layout enqueues for frontend (Retail Mode)
- Ensures library loads on both admin and frontend

**Code Added:** (Same as Assets.php, lines ~39-58)

**Reason:** Frontend dashboard needs RGL just like admin.

---

### 3. **`assets/js/sd-workspace.js`** ⚠️ **COMPLETE REWRITE**

**REMOVED:**

- Custom drag state management (`draggedIndex`, `draggedItem`, `dragNode`)
- Manual `handleDragStart`, `handleDragEnter`, `handleDragEnd` functions
- CSS Grid-based layout (`sd-unified-grid` with manual span classes)
- Manual swap-on-hover logic
- Manual layout save calls (was disabled anyway)

**ADDED:**

- React Grid Layout integration via `window.ReactGridLayout.Responsive`
- Responsive breakpoints: `{ lg: 1200, md: 996, sm: 768, xs: 480, xxs: 0 }`
- Column configuration: `{ lg: 12, md: 10, sm: 6, xs: 4, xxs: 2 }`
- Layout generation from manifest data
- localStorage persistence for grid layouts
- Proper min/max sizing constraints:
    - **Pins:** minW: 3 (¼ width), maxW: 12, minH: 1, maxH: 1
    - **Widgets:** minW: 4 (⅓ width), maxW: 12, minH: 2, maxH: 20

**New Grid Configuration:**

```javascript
rowHeight: 40,           // Compact row height
margin: [12, 12],        // Tight spacing
containerPadding: [0, 0],
draggableHandle: ".sd-widget-header, .sd-pin-card",
compactType: "vertical",
preventCollision: false,
```

**Key Improvements:**

1. Pins and widgets can now be intermixed (no forced separation)
2. Professional drag-and-drop with collision detection
3. Resize handles on all items
4. Automatic layout persistence
5. Responsive breakpoints built-in

---

### 4. **`assets/css/sd-temp.css`**

**REMOVED/REPLACED:**

- Old manual grid CSS (`.sd-unified-grid`, `.span-*` classes)
- Old grid positioning logic
- Custom drag placeholder styles
- Manual responsive column rules

**ADDED:**

#### A. React Grid Layout Core Styles (Required by RGL)

```css
.react-grid-layout {
	position: relative;
	transition: height 200ms ease;
}
.react-grid-item {
	transition: all 200ms ease;
}
.react-grid-item.resizing {
	z-index: 100;
	box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
}
.react-grid-item.react-grid-placeholder {
	background: rgba(34, 113, 177, 0.3);
}
.react-resizable-handle {
	/* Resize grip styling */
}
```

#### B. SystemDeck Component Overrides

```css
.sd-workspace-canvas {
	margin-right: 20px;
	padding: 20px 0;
}
.sd-grid-item {
	height: 100%;
	width: 100%;
}
```

#### C. Size/Spacing Reductions

```css
/* Tighter padding */
.sd-pinned-item,
.sd-pin-card {
	padding: 4px 32px 4px 8px;
}

/* Smaller fonts */
.sd-pin-icon {
	font-size: 14px;
}
.sd-pin-label {
	font-size: 10px;
}
.sd-pin-value {
	font-size: 11px;
}
.sd-widget-header .hndle {
	font-size: 13px;
}
.sd-widget-body {
	padding: 12px;
	font-size: 13px;
}

/* Compact widget headers */
.sd-widget-header {
	padding: 8px 12px;
	min-height: 36px;
}
```

**Reason:** Make UI more compact, less "gawky"

---

## 🗑️ **WHAT WAS COMPLETELY REMOVED**

1. **Custom Drag Logic:**
    - All manual drag event handlers
    - Swap-on-hover engine
    - Ghost element creation
    - Manual DOM manipulation during drag

2. **Custom Grid CSS:**
    - `.sd-unified-grid` manual CSS Grid
    - `.span-1` through `.span-12` classes
    - Manual responsive breakpoint rules
    - Container queries

3. **Save Functionality** (was already disabled):
    - `saveLayout()` AJAX calls
    - We still save to localStorage now

---

## 📍 **FILES UNCHANGED**

These files were NOT modified:

- `includes/Modules/WorkspaceRenderer.php` - Still hydrates widgets server-side
- `includes/Modules/SystemDiagnostics.php` - Pin items unchanged
- `includes/Core/Defaults.php` - Default layouts unchanged
- All widget PHP files

---

## 🎨 **STYLING PHILOSOPHY CHANGES**

**Before:** Block Theme aesthetic (gradients, large padding, rounded corners)
**After:** Compact, WordPress admin notice style (flat, tight, functional)

**Font Sizes:**

- Before: 12-18px
- After: 10-13px

**Padding:**

- Before: 8-16px
- After: 4-12px

**Grid Rows:**

- Before: 60px
- After: 40px

---

## 🔑 **KEY VOCABULARY ESTABLISHED**

- **Pin Item** (`.sd-pin-item`) = Inside widgets, can be toggled on/off
- **Pinned Item** (`.sd-pinned-item` / `.sd-pin-card`) = In grid, already pinned

---

## ✅ **CURRENT STATE**

**Working:**

- ✅ React Grid Layout loads on admin and frontend
- ✅ Drag-and-drop with collision detection
- ✅ Resize handles working
- ✅ Layouts persist to localStorage
- ✅ Responsive breakpoints functional
- ✅ Pins and widgets can be intermixed
- ✅ Proper min/max constraints

**Not Yet Implemented:**

- ⏳ Backend save to WordPress user meta (using localStorage for now)
- ⏳ Database table for production persistence
- ⏳ Widget auto-height calculation
- ⏳ Pin unpin functionality from grid

---

## 📊 **FILE SIZE IMPACT**

**Added:**

- `react-grid-layout.min.js`: 84KB
- `styles.css`: 2.7KB
- **Total:** ~87KB

**Net Change:** +87KB (acceptable for professional grid system)

---

## 🚀 **NEXT STEPS**

1. Tune widget auto-sizing for content
2. Implement backend persistence (user meta → database table)
3. Add pin/unpin toggle from grid
4. Fine-tune responsive breakpoint behavior
5. Add visual feedback for resize constraints

---

## 🎸 **RESULT**

Replaced custom "Antigravity" grid with battle-tested React Grid Layout. The grid now:

- Feels professional (like Grafana)
- Has proper physics
- Persists layouts
- Works responsively
- Allows full flexibility (pins + widgets intermixed)

**Status:** ✅ HEAVY! 🤘
