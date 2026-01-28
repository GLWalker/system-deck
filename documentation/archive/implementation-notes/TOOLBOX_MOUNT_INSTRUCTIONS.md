# Tool Box WordPress Screen Options - Final Manual Steps

## The Issue

The ToolBox component is currently rendering inline within the main app. It needs to render separately into the WordPress screen-meta panel at `#sd-toolbox-content`.

## Solution

Add this code at the **END** of `sd-workspace.js`, right before the final `})(window.wp, jQuery)`:

```javascript
// Mount ToolBox separately into WordPress screen-meta panel
const toolboxRoot = document.getElementById("sd-toolbox-content")
if (toolboxRoot && window.SD_Manifest) {
	const manifest = window.SD_Manifest

	// Standalone ToolBox with its own state
	const ToolBoxStandalone = () => {
		const [registry] = useState(manifest.registry || {})
		const [items, setItems] = useState(manifest.user?.layout || [])

		const toggleWidget = (widgetId, isSelected) => {
			if (isSelected) {
				setItems((prev) => [
					...prev,
					{ id: widgetId, type: "widget", w: 6 },
				])
			} else {
				setItems((prev) =>
					prev.filter(
						(item) =>
							!(item.id === widgetId && item.type === "widget"),
					),
				)
			}
			// Notify main app to refresh
			$(document).trigger("sd_widget_toggled", [widgetId, isSelected])
		}

		return el(ToolBox, {
			registry: registry,
			activeItems: items,
			onToggle: toggleWidget,
			isOpen: true,
			onClose: () => {},
		})
	}

	render(el(ToolBoxStandalone), toolboxRoot)
}
```

## Where to Add It

**File**: `/assets/js/sd-workspace.js`

**Location**: Around line 514, inside the `mountApp` function, AFTER the main app render but BEFORE the closing brace.

Change from:

```javascript
    const root = document.getElementById("sd-react-root")
    if (root) {
        render(el(ExperimentalGridApp), root)
        // ... pin actions code ...
    }
} // <-- mountApp function ends here

$(document).ready(mountApp)
```

To:

```javascript
    const root = document.getElementById("sd-react-root")
    if (root) {
        render(el(ExperimentalGridApp), root)
        // ... pin actions code ...
    }

    // ADD THE NEW CODE HERE (from above)
    const toolboxRoot = document.getElementById("sd-toolbox-content")
    if (toolboxRoot) {
        // ... toolbox mount code ...
    }

} // <-- mountApp function ends here

$(document).ready(mountApp)
```

## Test After Adding

1. **Clear all caches** (browser + WordPress if applicable)
2. **Reload** SystemDeck
3. **Look top-right** for "Tool Box" button
4. **Click it** - panel should slide down with widget checkboxes

---

If you're still not seeing it, check browser console for errors and share them.
