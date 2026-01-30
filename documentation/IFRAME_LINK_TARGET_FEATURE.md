# Iframe Link Target Override Feature

**Added:** January 27, 2026
**Version:** 1.1.2+
**File:** `includes/Modules/DashboardTunnel.php`

## 🎯 Problem

When users click links within iframe-based proxy widgets (Dashboard Tunnel), the links would navigate **inside the iframe** instead of opening in the main browser window. This creates a poor user experience where:

- Users get "trapped" in the iframe
- Navigation breaks the widget display
- Back button doesn't work as expected
- Users can't see the full page they're trying to access

## ✅ Solution

Implemented automatic link target override that forces **all links** within iframe widgets to open in the parent window (`target="_top"`).

## 🔧 How It Works

### Detection & Override

The script runs inside the iframe and:

1. **Finds all links** on page load
2. **Sets `target="_top"`** on each link (except anchors)
3. **Watches for new links** added dynamically (React widgets)
4. **Automatically updates** new links as they appear

### Code Flow

```javascript
// 1. Initial scan on page load
document.querySelectorAll('a').forEach(link => {
  if (href && href !== '#') {
    link.setAttribute('target', '_top')
  }
})

// 2. Watch for dynamically added links
MutationObserver watches DOM changes
→ New link detected
→ Automatically set target="_top"
```

## 📊 Technical Details

**Location:** `DashboardTunnel::asset_firewall()`
**Injection Method:** `wp_add_inline_script('common', ...)`
**Execution:** Runs inside iframe, not parent page

**Link Detection:**

- Initial: `document.querySelectorAll('a')`
- Dynamic: `MutationObserver` watching `document.body`

**Exclusions:**

- Anchor links (`#something`) - kept as-is for same-page navigation
- Empty hrefs - ignored

**Target Value:**

- `_top` - Opens in topmost window (breaks out of all iframes)

## 🎨 User Experience

### Before

```
User clicks link in widget
→ Link opens inside tiny iframe
→ User sees broken/cramped page
→ User confused, can't navigate back
```

### After

```
User clicks link in widget
→ Link opens in full browser window
→ User sees full page properly
→ Normal browser navigation works
```

## 🧪 Testing

### Test Scenario 1: Static Links

1. Open SystemDeck with a widget containing links
2. Click any link in the widget
3. **Expected:** Link opens in full browser window (not iframe)

### Test Scenario 2: React Widget Links

1. Open SystemDeck with Yoast SEO widget (React-based)
2. Wait for widget to load
3. Click any link in the widget
4. **Expected:** Link opens in full browser window

### Test Scenario 3: Anchor Links

1. Open widget with same-page anchor links (`#section`)
2. Click anchor link
3. **Expected:** Scrolls within widget (stays in iframe)

## ⚙️ Implementation Details

### MutationObserver Configuration

```javascript
observer.observe(document.body, {
	childList: true, // Watch for added/removed nodes
	subtree: true, // Watch entire tree, not just direct children
})
```

### Link Detection Logic

```javascript
// Check if node is a link
if (node.tagName === "A") {
	// Process link
}

// Check children for links
node.querySelectorAll("a").forEach((link) => {
	// Process each link
})
```

## 🔍 Edge Cases Handled

1. **Anchor links** - Preserved for same-page navigation
2. **Empty hrefs** - Ignored (no target needed)
3. **Dynamically added links** - Caught by MutationObserver
4. **React widgets** - Observer catches all DOM changes
5. **Multiple iframes** - Each iframe runs its own script
6. **Late-loading content** - Observer watches continuously

## 📝 Notes

### Why `_top` instead of `_parent`?

- `_top` breaks out of **all** iframes (even nested ones)
- `_parent` only goes up one level
- `_top` ensures links always open in main window

### Why MutationObserver?

- React widgets add links **after** page load
- Static `querySelectorAll` would miss them
- Observer catches all dynamic changes

### Performance Impact

- **Minimal** - Observer only fires when DOM changes
- **Efficient** - Only processes new nodes, not entire DOM
- **No polling** - Event-driven, not interval-based

## 🚀 Benefits

- ✅ Better user experience
- ✅ Prevents iframe navigation traps
- ✅ Works with static and dynamic content
- ✅ Handles React widgets automatically
- ✅ Preserves anchor link functionality
- ✅ No user configuration needed

## 🐛 Known Limitations

- **JavaScript-based navigation** - Won't catch `window.location` changes
- **Form submissions** - Forms still submit within iframe (could add similar fix)
- **Popup windows** - `target="_blank"` links will still open in new tab

## 💡 Future Enhancements

Potential improvements:

1. **Form target override** - Force forms to submit to parent
2. **Configurable behavior** - Let users choose `_top` vs `_blank`
3. **Whitelist/blacklist** - Allow specific links to stay in iframe
4. **Visual indicator** - Show icon on links that will open in new context

## ✅ Testing Checklist

- [ ] Static links open in parent window
- [ ] React widget links open in parent window
- [ ] Anchor links stay in iframe
- [ ] Multiple widgets work independently
- [ ] No console errors
- [ ] Performance is acceptable

---

**Implementation Status:** ✅ Complete
**Testing Status:** ⏳ Needs user testing
**Documentation Status:** ✅ Complete

---

**Related Files:**

- `includes/Modules/DashboardTunnel.php` (lines 348-397)

**Related Features:**

- Wake-from-sleep auto-reload
- Dashboard Tunnel iframe system

**Version:** Added in v1.1.2+
**Date:** January 27, 2026
