# Tool Box - WordPress Screen Options Implementation

## Current Status

✅ **HTML Structure Added** - WordPress pattern in `system-deck.php`
✅ **CSS Created** - `sd-screen-meta.css` with WordPress classes
✅ **JavaScript Updated** - `sd-toolbox-toggle.js` with slideToggle
✅ **Assets Enqueued** - Both files loaded in Assets.php and RetailController.php

## What's Working

1. The HTML structure now uses WordPress's exact pattern:

    ```html
    <div id="screen-meta-links">
    	<div class="screen-meta-toggle">
    		<button id="sd-toolbox-link" class="button show-settings">
    			Tool Box
    		</button>
    	</div>
    </div>

    <div id="sd-toolbox-panel" class="screen-meta">
    	<div id="sd-toolbox-content">
    		<!-- Toolbox content here -->
    	</div>
    </div>
    ```

2. The button should appear at the top-right
3. Clicking it slides the panel down (WordPress slideDown animation)
4. Uses WordPress admin CSS classes for styling

## What Needs Manual Adjustment

### JavaScript Component Mount Point

The React `ToolBox` component currently renders into `#sd-toolbox-overlay` but needs to render into `#sd-toolbox-content` instead.

**File**: `sd-workspace.js` (around line 505-520)

**Find**:

```javascript
const toolboxRoot = document.getElementById("sd-toolbox-overlay")
```

**Replace with**:

```javascript
const toolboxRoot = document.getElementById("sd-toolbox-content")
```

### Simplify ToolBox Component

The ToolBox component has a header and wrapper div that aren't needed anymore since WordPress provides the structure.

**File**: `sd-workspace.js` (around line 49-73)

The component should return JUST the grid, without the outer wrapper:

```javascript
const ToolBox = ({ registry, activeItems, onToggle, isOpen, onClose }) => {
	if (!registry) return null
	const widgets = Object.values(registry)

	return el(
		"div",
		{ className: "sd-toolbox-grid" },
		widgets.map((widget) => {
			// ... existing widget mapping code
		}),
	)
}
```

## Testing After Changes

1. Clear all caches
2. Reload SystemDeck
3. Look for "Tool Box" button at top-right (below admin bar if present)
4. Click it - panel should slide down from the button
5. Should look exactly like WordPress Screen Options

## Files Modified

- ✅ `templates/system-deck.php` - Added screen-meta HTML
- ✅ `assets/css/sd-screen-meta.css` - WordPress classes
- ✅ `assets/js/sd-toolbox-toggle.js` - slideToggle logic
- ✅ `includes/Core/Assets.php` - Enqueued new CSS
- ⚠️ `assets/js/sd-workspace.js` - Needs manual mount point update

---

_Claude Sonnet - WordPress Screen Options Pattern Implementation_
