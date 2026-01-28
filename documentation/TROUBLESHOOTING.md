# SystemDeck Troubleshooting Guide

**Version:** 1.1.2
**Last Updated:** January 27, 2026

## 🔧 Common Issues & Solutions

---

## Issue: SystemDeck Not Appearing

### Symptoms

- SystemDeck menu item missing from WordPress admin
- No drawer appears when clicking toggle

### Possible Causes & Solutions

#### 1. Plugin Not Activated

**Check:**

```
WordPress Admin → Plugins → SystemDeck
```

**Solution:** Click "Activate"

#### 2. Insufficient Permissions

**Check:** Current user capabilities
**Solution:** Ensure user has `manage_options` capability (Administrator role)

#### 3. JavaScript Not Loading

**Check:** Browser console for errors
**Solution:**

- Hard refresh: `Cmd+Shift+R` (Mac) or `Ctrl+Shift+R` (Windows)
- Clear browser cache
- Check `/wp-content/plugins/system-deck/assets/js/` files exist

#### 4. Cookie Not Set

**Check:** Browser DevTools → Application → Cookies → `sd_is_active`
**Solution:**

```javascript
// In browser console:
document.cookie = "sd_is_active=true; path=/"
```

---

## Issue: Widgets Not Saving

### Symptoms

- Widget changes don't persist after refresh
- Layout resets to default

### Possible Causes & Solutions

#### 1. AJAX Failing

**Check:** Browser DevTools → Network tab
**Look for:** Failed requests to `admin-ajax.php`

**Solution:**

- Verify nonce is valid (check `sd_vars.nonce` in console)
- Check for PHP errors in `/wp-content/debug.log`
- Ensure user is logged in

#### 2. Database Write Failure

**Check:** PHP error log
**Solution:**

- Verify database connection
- Check user meta table permissions
- Ensure `wp_usermeta` table exists

#### 3. Auto-Save Timing Issue

**Check:** Console for "Skipping auto-save on initial mount"
**Solution:** Already fixed in v1.1.2 (initial mount lock)

---

## Issue: Widgets Not Loading Content

### Symptoms

- Widget appears but shows "Loading..." forever
- Widget shows error message

### Possible Causes & Solutions

#### 1. AJAX Endpoint Missing

**Check:** Network tab for `sd_render_widget` request
**Solution:** Ensure v1.1.2+ (endpoint added Jan 27, 2026)

#### 2. Widget Not Registered

**Check:**

```javascript
// In browser console:
console.log(window.SD_Manifest.registry)
```

**Solution:** Verify widget is registered in `Registry.php`

#### 3. Content Generation Error

**Check:** PHP error log
**Solution:**

- Check widget callback function exists
- Verify widget has proper permissions
- Test widget directly in WordPress dashboard

---

## Issue: Drag & Drop Not Working

### Symptoms

- Cannot drag widgets
- Widgets don't reorder

### Possible Causes & Solutions

#### 1. Drag Handle Not Clicked

**Solution:** Click and hold the ⋮⋮ (move) icon, not the widget title

#### 2. JavaScript Error

**Check:** Browser console for errors
**Solution:**

- Hard refresh browser
- Check for conflicting plugins
- Disable other plugins temporarily

#### 3. Browser Compatibility

**Check:** Browser version
**Solution:** Update to latest version of:

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

---

## Issue: Collapse/Expand Not Working

### Symptoms

- Clicking arrow doesn't collapse widget
- Widget stays expanded/collapsed

### Possible Causes & Solutions

#### 1. Event Handler Not Attached

**Check:** Console for errors
**Solution:** Ensure `sd-workspace.js` is loaded

#### 2. State Not Updating

**Check:**

```javascript
// In browser console:
console.log("Collapse state:", window.SD_Manifest)
```

**Solution:** Hard refresh browser

---

## Issue: Unpin Button Missing

### Symptoms

- No X button on pinned items
- Cannot remove pins

### Possible Causes & Solutions

#### 1. Old Version

**Check:** Plugin version
**Solution:** Ensure v1.1.2+ (unpin button always present)

#### 2. CSS Not Loaded

**Check:** DevTools → Elements → Inspect pin card
**Solution:**

- Verify `sd-core.css` is loaded
- Hard refresh browser
- Clear CSS cache

---

## Issue: Dashboard Tunnel Not Loading

### Symptoms

- Proxy widgets show blank iframe
- "Widget not found" error

### Possible Causes & Solutions

#### 1. Widget ID Incorrect

**Check:** Network tab → iframe src URL
**Solution:** Verify widget ID matches dashboard widget ID

#### 2. Tunnel Page Not Registered

**Check:**

```
WordPress Admin → Pages → Search for "sd-dashboard-tunnel"
```

**Solution:** Deactivate and reactivate plugin

#### 3. Nonce Expired

**Check:** URL parameters in iframe src
**Solution:** Refresh parent page to regenerate nonce

---

## Issue: Deep Scan Not Finding Widgets

### Symptoms

- "Scan Complete" but no widgets found
- "Scan Blocked" error

### Possible Causes & Solutions

#### 1. Same-Origin Policy

**Check:** Console for CORS errors
**Solution:** Ensure WordPress is not using subdomain for admin

#### 2. Dashboard Not Loaded

**Solution:** Navigate to WordPress Dashboard first, then run scan

#### 3. Widgets Load Slowly

**Solution:** Increase scan delay in `sd-scanner.js` (currently 2000ms)

---

## Issue: Performance Problems

### Symptoms

- Slow loading
- Laggy drag & drop
- Browser freezing

### Possible Causes & Solutions

#### 1. Too Many Widgets

**Check:** Number of active widgets
**Solution:** Limit to 10-15 widgets per workspace

#### 2. Heavy Widget Content

**Check:** Widget content size
**Solution:**

- Use lazy loading (already implemented)
- Collapse unused widgets
- Remove unnecessary widgets

#### 3. Browser Extensions

**Check:** Disable extensions temporarily
**Solution:** Identify conflicting extension and disable

---

## 🔍 Debugging Tools

### Enable WordPress Debug Mode

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', true);
```

### Check Debug Log

```bash
tail -f /wp-content/debug.log
```

### Browser Console Debugging

```javascript
// Check manifest
console.log("Manifest:", window.SD_Manifest)

// Check registry
console.log("Registry:", window.SD_Manifest.registry)

// Check layout
console.log("Layout:", window.SD_Manifest.user.layout)

// Check AJAX vars
console.log("AJAX vars:", window.sd_vars)
```

### Network Debugging

1. Open DevTools → Network tab
2. Filter by "admin-ajax.php"
3. Click request → Preview tab
4. Check response data

---

## 🚨 Emergency Fixes

### Reset All Settings

```php
// In WordPress Admin → Tools → Site Health → Info → Database
// Or via phpMyAdmin:
DELETE FROM wp_usermeta WHERE meta_key LIKE 'sd_%';
```

### Clear Plugin Cache

```bash
# Via SSH
rm -rf /wp-content/cache/system-deck/
```

### Reinstall Plugin

1. Deactivate SystemDeck
2. Delete plugin files
3. Re-upload fresh copy
4. Activate plugin

**Note:** This will preserve user data (stored in database)

---

## 📊 Diagnostic Checklist

Run through this checklist when troubleshooting:

- [ ] Plugin activated
- [ ] User has admin privileges
- [ ] No PHP errors in debug.log
- [ ] No JavaScript errors in console
- [ ] AJAX requests succeeding (200 status)
- [ ] Nonce valid and not expired
- [ ] Browser cache cleared
- [ ] Hard refresh performed
- [ ] Latest plugin version installed
- [ ] No conflicting plugins active

---

## 🆘 Getting Help

### Before Reporting Issues

1. **Check this guide** for common solutions
2. **Enable debug mode** and check logs
3. **Test with default theme** (Twenty Twenty-Four)
4. **Disable other plugins** to isolate conflict
5. **Try different browser** to rule out browser issues

### Information to Provide

When reporting issues, include:

- WordPress version
- PHP version
- SystemDeck version
- Browser and version
- Steps to reproduce
- Error messages (PHP and JavaScript)
- Screenshots/screen recording
- Debug log excerpt

### Support Channels

- **Documentation:** `/documentation/`
- **Issue Tracker:** [GitHub Issues]
- **Developer Contact:** [Contact Info]

---

## 🔄 Version-Specific Issues

### v1.1.2 (January 27, 2026)

**Fixed:**

- ✅ Widget lazy loading (added `render_widget` endpoint)
- ✅ Console log cleanup
- ✅ All critical fixes complete

**Known Issues:** None

### v1.1.1 and Earlier

**Issues:**

- ❌ Missing `render_widget` AJAX endpoint
- ❌ Debug logs in production
- ❌ Layout persistence race condition

**Solution:** Update to v1.1.2+

---

## 📝 FAQ

### Q: Why do my widgets disappear after refresh?

**A:** This was a known issue in v1.1.1 and earlier. Update to v1.1.2+ where the auto-save race condition is fixed.

### Q: Can I use SystemDeck on multisite?

**A:** Not tested. Use at your own risk on multisite installations.

### Q: Does SystemDeck work with page builders?

**A:** SystemDeck is designed for WordPress admin area, not frontend page builders.

### Q: Can I customize the appearance?

**A:** Yes, via CSS. See `/documentation/core/CSSVariables.md` for customization options.

### Q: Is SystemDeck compatible with my theme?

**A:** SystemDeck operates in WordPress admin and is theme-independent.

---

**Last Updated:** January 27, 2026
**Need More Help?** Check `/documentation/` for additional guides.
