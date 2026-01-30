# SystemDeck Hybrid Hydration - Manual JavaScript Update

**File**: `/assets/js/sd-workspace.js`
**Location**: Line 196 - `fetchHydratedWidget` function

## Replace This Function

Find the `fetchHydratedWidget` function (around line 196) and replace it with:

```javascript
const fetchHydratedWidget = (widgetId) => {
	console.log("🔄 Lazy-loading widget via REST API:", widgetId)

	// Use WordPress REST API for lazy-loading (Hybrid Hydration v1.3)
	// Bypasses admin-ajax 403 issues with proper REST authentication
	wp.apiFetch({
		path: `/systemdeck/v1/hydrate-widget/${widgetId}`,
		method: "GET",
	})
		.then((data) => {
			if (data.success && data.data) {
				setRegistry((prev) => ({
					...prev,
					[widgetId]: {
						...prev[widgetId],
						content: data.data.content,
					},
				}))
				console.log("✅ Widget hydrated via REST:", widgetId)
			} else {
				console.error(
					"❌ Hydration failed:",
					data.message || "Unknown error",
				)
			}
		})
		.catch((err) => console.error("❌ REST API error during hydrat:", err))
}
```

## Status

✅ **Registry.php** - Selective hydration active
✅ **RestController.php** - REST endpoint deployed
⚠️ **sd-workspace.js** - Manual update required (formatting mismatch)

## Test After Update

1. Clear browser cache
2. Reload workspace
3. Toggle a widget on that isn't in the layout
4. Check Network tab for: `GET /wp-json/systemdeck/v1/hydrate-widget/{id}`
5. Verify widget loads without 403 errors

---

_Principal Architect Claude - Ready for manual JavaScript patch_
