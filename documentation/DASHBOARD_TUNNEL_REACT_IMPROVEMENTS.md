# Dashboard Tunnel React Widget Improvements

**Date:** January 27, 2026
**Version:** 1.1.2+
**File:** `includes/Modules/DashboardTunnel.php`

## 🎯 Problem

Some React-based dashboard widgets (particularly Yoast SEO) were not loading properly in the Dashboard Tunnel iframe. The widgets would appear blank or show loading spinners indefinitely.

**Root Causes:**

1. **Timing issues** - React scripts loading after DOM ready
2. **Missing mount points** - React looking for specific div IDs that don't exist
3. **Data store initialization** - WordPress data stores not properly initialized
4. **Event sequencing** - React waiting for events that never fire in iframe context

## ✅ Solution

Implemented a comprehensive **Universal React Lifecycle Bridge** with:

1. **Intelligent timing** - Multiple initialization triggers
2. **Automatic mount point detection** - Creates mount points for React widgets
3. **Data store initialization** - Properly sets up WordPress core data
4. **Debug logging** - Console output to diagnose issues
5. **Universal cluster rendering** - Supports multiple plugin ecosystems

---

## 🔧 Key Improvements

### 1. Enhanced React Lifecycle Bridge

**Location:** Lines 160-318

**Features:**

- **Multi-stage initialization** - DOM ready, window load, fallback timer
- **API Fetch configuration** - Sets up REST API middleware
- **Data store initialization** - Injects user data and permissions
- **Mount point detection** - Watches for React mount points being added
- **Event dispatching** - Triggers all necessary readiness events
- **Debug logging** - Prefixed with `[SD Tunnel]` for easy filtering

**Initialization Sequence:**

```
1. DOM Ready → Start observer → Initialize
2. Window Load → Initialize (if not done)
3. 2-second fallback → Force initialize
4. Mount point detected → Initialize (if not done)
```

### 2. Universal React Mount Point Creation

**Location:** Lines 590-605

**Logic:**

```php
// Detect if widget output is minimal (likely React)
$trimmed = trim(strip_tags($output));
$is_likely_react = empty($trimmed) || strlen($trimmed) < 50;

// Create mount point if needed
if ($is_likely_react && !has_mount_point($output)) {
    echo '<div id="' . $widget_id . '" class="react-mount-point"></div>';
}
```

**Benefits:**

- Works with **any** React widget, not just Yoast
- Automatically detects when mount point is needed
- Uses widget ID as mount point ID (standard convention)
- Doesn't duplicate if mount point already exists

### 3. Universal Cluster Rendering

**Location:** Lines 613-643

**Supported Plugins:**

- Yoast SEO (`wpseo`)
- All in One SEO (`aioseo`)
- Jetpack (`jetpack`)

**How It Works:**

```php
// Check if widget needs cluster rendering
$cluster_prefixes = ['wpseo', 'aioseo', 'jetpack'];

// Render all sibling widgets from same plugin
foreach ($sibling_widgets as $widget) {
    echo '<div class="sd-cluster-helper" style="display:none;">';
    render_widget($widget);
    echo '</div>';
}
```

**Why This Helps:**

- Some plugins register multiple widgets that depend on each other
- Rendering siblings ensures all necessary scripts/data are loaded
- Hidden with `display:none` so they don't show visually

---

## 📊 Debug Logging

### Enable/Disable

```javascript
var debug = true // Set to false to disable logging
```

### Log Messages

| Message                                 | Meaning                          |
| --------------------------------------- | -------------------------------- |
| `[SD Tunnel] DOM Ready`                 | DOM is ready for manipulation    |
| `[SD Tunnel] Observer started`          | MutationObserver is watching     |
| `[SD Tunnel] Initializing...`           | Starting initialization sequence |
| `[SD Tunnel] API Fetch configured`      | REST API middleware set up       |
| `[SD Tunnel] wp.data not available yet` | Data stores not loaded           |
| `[SD Tunnel] User already loaded: X`    | User data exists                 |
| `[SD Tunnel] User data injected`        | User data manually set           |
| `[SD Tunnel] Triggering React mount...` | Dispatching mount events         |
| `[SD Tunnel] React mount complete`      | Initialization finished          |
| `[SD Tunnel] Mount point detected: X`   | Found React mount div            |
| `[SD Tunnel] Window loaded`             | All scripts loaded               |
| `[SD Tunnel] Fallback initialization`   | 2-second timer triggered         |

### How to Use Logs

1. Open browser DevTools → Console
2. Filter by `SD Tunnel` to see only tunnel logs
3. Watch the sequence to diagnose issues
4. Look for errors between log messages

---

## 🧪 Testing

### Test Scenario 1: Yoast SEO Widget

**Steps:**

1. Install Yoast SEO plugin
2. Add Yoast dashboard widget to SystemDeck
3. Open browser console
4. Watch for `[SD Tunnel]` logs

**Expected Logs:**

```
[SD Tunnel] DOM Ready
[SD Tunnel] Observer started
[SD Tunnel] Initializing...
[SD Tunnel] API Fetch configured
[SD Tunnel] User data injected
[SD Tunnel] Triggering React mount...
[SD Tunnel] React mount complete
```

**Expected Result:**

- Widget loads and displays content
- No infinite spinner
- No blank widget

### Test Scenario 2: All in One SEO

**Steps:**

1. Install All in One SEO plugin
2. Add AIOSEO dashboard widget
3. Check console logs

**Expected:**

- Similar log sequence
- Widget content appears
- Cluster rendering triggered (if multiple AIOSEO widgets exist)

### Test Scenario 3: Empty/Minimal Output Widget

**Steps:**

1. Find a widget with minimal HTML output
2. Add to SystemDeck
3. Inspect HTML in DevTools

**Expected:**

- Mount point div created: `<div id="widget_id" class="react-mount-point"></div>`
- Widget content renders inside or after mount point

---

## 🔍 Troubleshooting

### Widget Still Not Loading

**Check Console Logs:**

```
1. Is "API Fetch configured" appearing?
   → If no: wp.apiFetch not loaded, check script enqueue

2. Is "User data injected" appearing?
   → If no: wp.data not loaded, check dependencies

3. Is "React mount complete" appearing?
   → If no: Initialization not completing, check for JS errors

4. Is "Mount point detected" appearing?
   → If yes: Mount point exists but React not mounting
```

**Common Issues:**

| Issue                          | Solution                                            |
| ------------------------------ | --------------------------------------------------- |
| No logs at all                 | Check if widget is using tunnel (not direct render) |
| "wp.data not available"        | Ensure `wp-data` script is enqueued                 |
| Mount point created but empty  | Check widget's own JavaScript for errors            |
| Infinite spinner               | API Fetch not configured, check REST API            |
| Widget flashes then disappears | Check for CSS conflicts                             |

### Enable More Verbose Logging

Add to the initialization function:

```javascript
function initialize() {
	log("Initializing...")
	log("window.wp available: " + !!window.wp)
	log("window.wp.data available: " + !!window.wp?.data)
	log("window.wp.apiFetch available: " + !!window.wp?.apiFetch)
	// ... rest of function
}
```

---

## 📝 Technical Details

### Data Store Initialization

```javascript
// Get WordPress core store
var store = wp.data.select('core');
var dispatch = wp.data.dispatch('core');

// Inject user data
dispatch.receiveCurrentUser({
    id: <?php echo $user_id; ?>,
    name: '<?php echo $display_name; ?>'
});

// Set permissions
dispatch.receiveUserPermission('read', true);
dispatch.receiveUserPermission('edit', true);
```

### Event Dispatching

```javascript
// Standard WordPress events
window.dispatchEvent(new Event("wp-dashboard-ready"))

// Plugin-specific events
window.dispatchEvent(new Event("yoast:ready"))

// jQuery events (for legacy widgets)
jQuery(document).trigger("ready")
```

### Mount Point Detection

```javascript
// Watch for new nodes
observer.observe(document.body, {
	childList: true, // Watch for added/removed nodes
	subtree: true, // Watch entire tree
})

// Check if node is a mount point
if (
	node.id.indexOf("dashboard") !== -1 ||
	node.id.indexOf("widget") !== -1 ||
	node.id.indexOf("seo") !== -1
) {
	// Likely a React mount point
}
```

---

## ✅ Benefits

### For Users

- ✅ More widgets work out of the box
- ✅ No blank widgets
- ✅ Faster loading (better timing)
- ✅ Better error visibility (debug logs)

### For Developers

- ✅ Universal solution (not plugin-specific)
- ✅ Easy to debug (comprehensive logging)
- ✅ Extensible (add more cluster prefixes)
- ✅ Self-healing (multiple fallbacks)

---

## 🚀 Future Enhancements

Potential improvements:

1. **Configurable debug mode** - Admin setting to enable/disable logs
2. **Widget-specific overrides** - Custom initialization per widget
3. **Performance monitoring** - Track initialization time
4. **Error reporting** - Send errors to admin
5. **Retry logic** - Automatically retry failed initializations

---

## 📚 Related Documentation

- [Dashboard Tunnel Overview](./MASTER_MANIFEST.md)
- [Wake-from-Sleep Feature](./WAKE_FROM_SLEEP_FEATURE.md)
- [Iframe Link Target Feature](./IFRAME_LINK_TARGET_FEATURE.md)

---

**Implementation Status:** ✅ Complete
**Testing Status:** ⏳ Needs user testing
**Documentation Status:** ✅ Complete

**Version:** Added in v1.1.2+
**Date:** January 27, 2026
