# CRITICAL FIX: Remove Duplicate ToolBox Render

## Problem

ToolBox is being rendered **twice**:

1. Inside `ExperimentalGridApp` (lines 527-533) - OLD, remove this
2. As standalone mount (line 598-607) - NEW, keep this

The old render inside the grid is why it appears inside `#sd-wp-grid-experiment` instead of the WordPress screen-meta panel.

## Solution

**File**: `sd-workspace.js`
**Lines**: 527-533

### Remove This Block:

```javascript
el(ToolBox, {
    registry,
    activeItems: items,
    isOpen: isToolBoxOpen,
    onClose: () => setIsToolBoxOpen(false),
    onToggle: toggleWidget,
}),
```

### Final Code Should Look Like:

```javascript
return el(
	"div",
	{ className: "sd-wp-grid-experiment" },
	// ToolBox removed - now mounted separately
	el(
		Grid,
		{
			gap: 4,
			className: "sd-native-grid",
			// ... rest of Grid config
		},
		gridItems,
	),
)
```

## Why This Fixes It

- **Before**: ToolBox renders inside grid component → appears in wrong place
- **After**: ToolBox only renders via standalone mount → appears in WordPress screen-meta panel at `#sd-toolbox-content`

## After Removing

1. Clear cache
2. Reload workspace
3. ToolBox button should appear top-right
4. Click it - panel slides down with checkboxes
5. Component now renders in correct location outside grid

---

**DELETE LINES 527-533** to fix the duplicate render issue.
