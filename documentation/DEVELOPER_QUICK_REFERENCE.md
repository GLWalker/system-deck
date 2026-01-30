# SystemDeck - Developer Quick Reference

_Last Updated: January 22, 2026_

---

## 🚀 **Quick Start Commands**

### **Development:**

```bash
# Navigate to plugin directory
cd /Users/glwalker/DevKinsta/public/thedrawer/wp-content/plugins/system-deck

# View documentation
ls -la documentation/

# Check error logs
tail -f /Users/glwalker/DevKinsta/public/thedrawer/wp-content/debug.log
```

### **Browser Testing:**

- URL: `http://thedrawer.local/wp-admin/`
- Username: `walker`
- Hard Refresh: `Cmd+Shift+R`

---

## 📁 **File Structure**

```
system-deck/
├── assets/
│   ├── js/
│   │   ├── sd-workspace.js        ← Main workspace grid
│   │   ├── sd-system-grid.js      ← System Config grid
│   │   ├── sd-system.js           ← System logic
│   │   ├── sd-deck.js             ← Drawer controller
│   │   └── sd-grid.js             ← Legacy grid
│   └── css/
│       ├── sd-core.css            ← Load first
│       ├── sd-common.css          ← Common styles
│       ├── sd-react-grid.css      ← Grid styles
│       └── sd-wpcolors.css        ← Color scheme
├── includes/
│   ├── Core/
│   │   ├── Assets.php             ← Asset registration
│   │   ├── Boot.php               ← Plugin initialization
│   │   └── Defaults.php           ← Default layouts
│   └── Modules/
│       ├── WorkspaceRenderer.php  ← Workspace rendering
│       ├── SystemScreen.php       ← System Config
│       └── PinManager.php         ← Pin CRUD + AJAX
└── documentation/
    ├── INDEX.md                   ⭐ START HERE
    ├── SESSION_SUMMARY_JAN_22_2026.md
    └── SYSTEM_CONFIG_REACT_GRID.md
```

---

## 🔧 **Common Tasks**

### **Adding a New Widget:**

1. Register in `includes/Registry/WidgetRegistry.php`
2. Create widget class in `includes/Widgets/`
3. Add to default layout in `includes/Core/Defaults.php`

### **Adding a New AJAX Endpoint:**

1. Add hook in module's `init()` method:
    ```php
    add_action('wp_ajax_sd_your_action', [self::class, 'ajax_your_action']);
    ```
2. Implement handler with security checks:
    ```php
    public static function ajax_your_action(): void {
        check_ajax_referer('sd_load_shell', 'nonce');
        if (!current_user_can('manage_options'))
            wp_send_json_error('Unauthorized');
        // Your logic here
        wp_send_json_success($data);
    }
    ```

### **Modifying the Grid:**

- **Workspace Grid:** `assets/js/sd-workspace.js`
- **System Config Grid:** `assets/js/sd-system-grid.js`
- Both use WordPress `__experimentalGrid` component

---

## 🎨 **WordPress Components Used**

### **Layout:**

```javascript
const { __experimentalGrid: Grid } = wp.components
el(Grid, { columns: 3, gap: 4 }, children)
```

### **Containers:**

```javascript
const { Panel, PanelBody, Card, CardBody } = wp.components
el(Panel, {}, [el(PanelBody, { title: "Title", opened: true }, content)])
```

### **Controls:**

```javascript
const { Button, TextControl } = wp.components
el(
	Button,
	{
		variant: "primary", // or 'secondary', 'tertiary'
		onClick: handler,
		isDestructive: true, // for delete buttons
	},
	"Label",
)
```

---

## 🔐 **Security Patterns**

### **AJAX Handler Template:**

```php
public static function ajax_handler(): void {
    // 1. Verify nonce
    check_ajax_referer('sd_load_shell', 'nonce');

    // 2. Check capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    // 3. Sanitize inputs
    $input = sanitize_text_field($_POST['input'] ?? '');

    // 4. Validate
    if (empty($input)) {
        wp_send_json_error(['message' => 'Invalid input']);
    }

    // 5. Process & respond
    wp_send_json_success(['data' => $result]);
}
```

### **Frontend AJAX Call:**

```javascript
fetch(window.sd_vars?.ajax_url || "/wp-admin/admin-ajax.php", {
	method: "POST",
	headers: { "Content-Type": "application/x-www-form-urlencoded" },
	body: new URLSearchParams({
		action: "sd_your_action",
		nonce: window.sd_vars?.nonce || "",
		data: yourData,
	}),
})
	.then((res) => res.json())
	.then((data) => {
		if (data.success) {
			// Handle success
		} else {
			// Handle error
		}
	})
```

---

## 🐛 **Debugging**

### **Enable Debug Mode:**

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### **Browser Console:**

```javascript
// Check if React mounted
console.log("SD_Manifest:", window.SD_Manifest)
console.log("SystemDeckSys:", window.SystemDeckSys)

// Check grid state
const grid = document.getElementById("sd-react-workspace-root")
console.log("Grid exists:", !!grid)
```

### **PHP Debug Logs:**

```bash
# Watch error log in real-time
tail -f /Users/glwalker/DevKinsta/public/thedrawer/wp-content/debug.log
```

---

## 📊 **Performance Tips**

### **React Optimization:**

```javascript
// Use useMemo for expensive calculations
const gridConfig = useMemo(
	() => ({
		columns: calculateColumns(),
		gap: 4,
	}),
	[windowWidth],
)

// Use useEffect with proper dependencies
useEffect(() => {
	// Only run when items change
}, [items])
```

### **CSS Optimization:**

- Core styles load first (`sd-core.css`)
- Component styles after (`sd-common.css`)
- Dynamic colors injected inline
- Cached per-user for 24 hours

---

## 🎯 **Common Issues & Solutions**

### **Grid Not Rendering:**

1. Check console for React errors
2. Verify `wp.components` is loaded
3. Ensure mount point exists (`#sd-react-workspace-root`)
4. Check if trigger event fired

### **AJAX Failing:**

1. Verify nonce is correct
2. Check user has `manage_options` capability
3. Look for PHP errors in debug.log
4. Confirm endpoint is registered

### **Persistence Not Working:**

1. Check `isInitialMount` ref is defined
2. Verify `useEffect` dependencies correct
3. Ensure AJAX endpoint returns success
4. Check user meta is saving

---

## 📝 **Git Workflow** (If Using Version Control)

```bash
# Feature branch
git checkout -b feature/new-widget

# Commit changes
git add .
git commit -m "feat: add new dashboard widget"

# Merge to main
git checkout main
git merge feature/new-widget

# Tag release
git tag -a v1.5.0 -m "Added React grid, fixed persistence"
```

---

## 🌟 **Best Practices**

### **Code Style:**

- Use tabs for indentation (WordPress standard)
- PHPDoc for all functions
- JSDoc for complex JavaScript functions
- Meaningful variable names

### **React Components:**

- Keep components small and focused
- Use WordPress components when available
- Handle loading states
- Provide error feedback

### **Security:**

- Always check nonce
- Verify user capabilities
- Sanitize all inputs
- Escape all outputs

### **Documentation:**

- Update documentation when adding features
- Comment complex logic
- Provide examples
- Keep changelog updated

---

## 📚 **Resources**

### **WordPress:**

- [WordPress Components](https://developer.wordpress.org/block-editor/reference-guides/components/)
- [AJAX in Plugins](https://developer.wordpress.org/plugins/javascript/ajax/)
- [Data Sanitization](https://developer.wordpress.org/apis/security/sanitizing-securing-output/)

### **React:**

- [React Hooks](https://react.dev/reference/react)
- [WordPress React Integration](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-element/)

### **SystemDeck Docs:**

- `/documentation/INDEX.md` - Master index
- `/documentation/SESSION_SUMMARY_JAN_22_2026.md` - Latest session
- `/documentation/WORDPRESS_COMPONENTS_CSS_REFERENCE.md` - Component guide

---

## 🚨 **Emergency Rollback**

### **If Something Breaks:**

```bash
# Restore from backup
cp /tmp/sd-workspace-backup.js \
   /path/to/assets/js/sd-workspace.js

# Or restore from .bak files
cd /path/to/assets/js/
ls -la *.bak
cp sd-system.js.bak sd-system.js
```

### **Disable Plugin:**

```bash
# Via WP-CLI (if installed)
wp plugin deactivate system-deck

# Or rename plugin directory
mv system-deck system-deck-disabled
```

---

## ✅ **Checklist for New Features**

- [ ] Security checks implemented
- [ ] Error handling added
- [ ] Loading states shown
- [ ] Console logging for debugging
- [ ] Documentation updated
- [ ] Browser tested (Chrome/Safari)
- [ ] Mobile responsive checked
- [ ] AJAX endpoints nonce-protected
- [ ] User capabilities verified
- [ ] Inputs sanitized
- [ ] Outputs escaped

---

**Quick Help:**

- All docs in `/documentation/`
- Start with `INDEX.md`
- Session summary in `SESSION_SUMMARY_JAN_22_2026.md`

**Need More Help?**
Check the comprehensive documentation or review the session summary for detailed implementation examples!
