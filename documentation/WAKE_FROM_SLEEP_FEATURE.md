# Wake-from-Sleep Auto-Reload Feature

**Added:** January 27, 2026
**Version:** 1.1.2+
**File:** `assets/js/sd-workspace.js`

## 🎯 Problem

When a computer goes to sleep while SystemDeck is open, iframe-based proxy widgets (Dashboard Tunnel widgets) lose their connection and display a "Your computer went to sleep" message instead of the widget content.

## ✅ Solution

Implemented automatic iframe reload detection that:

1. **Detects sleep/wake cycles** by monitoring time gaps
2. **Automatically reloads iframes** when wake is detected
3. **Handles tab visibility changes** (switching back to the tab)
4. **Graceful reload** with minimal visual disruption

## 🔧 How It Works

### Detection Methods

#### 1. Time Gap Detection

```javascript
// Checks every 2 seconds
// If >5 seconds have passed, computer likely slept
if (currentTime > lastTime + 5000) {
	// Reload iframe
}
```

#### 2. Visibility Change Detection

```javascript
// When tab becomes visible again
document.addEventListener("visibilitychange", () => {
	if (!document.hidden) {
		// Reload iframe after 500ms delay
	}
})
```

### Reload Process

1. Store current iframe src
2. Clear iframe src (blank it)
3. Wait 100ms
4. Restore iframe src (triggers reload)

This creates a clean reload without flickering.

## 📊 Technical Details

**Component:** `DashboardWidgetFrame`
**Hook Used:** `useEffect`
**Dependencies:** `[widgetId]`

**Intervals:**

- Check interval: 2000ms (2 seconds)
- Sleep threshold: 5000ms (5 seconds)
- Visibility reload delay: 500ms
- Iframe reload delay: 100ms

**Cleanup:**

- Clears interval on unmount
- Removes event listener on unmount

## 🧪 Testing

### Test Scenario 1: Computer Sleep

1. Open SystemDeck with proxy widgets
2. Put computer to sleep (close lid)
3. Wait 30+ seconds
4. Wake computer
5. **Expected:** Widgets automatically reload within 2-3 seconds

### Test Scenario 2: Tab Switch

1. Open SystemDeck with proxy widgets
2. Switch to another tab for 10+ seconds
3. Switch back to SystemDeck tab
4. **Expected:** Widgets reload within 500ms

### Test Scenario 3: Long Idle

1. Open SystemDeck with proxy widgets
2. Leave computer idle (but awake) for 10+ minutes
3. Return to computer
4. **Expected:** Widgets still functional, no unnecessary reloads

## ⚙️ Configuration

Currently hardcoded values. Could be made configurable:

```javascript
const CONFIG = {
	checkInterval: 2000, // How often to check for wake
	sleepThreshold: 5000, // Time gap indicating sleep
	visibilityDelay: 500, // Delay before reload on tab switch
	reloadDelay: 100, // Delay between blank and reload
}
```

## 🎨 User Experience

**Before:**

- Computer sleeps → Wake up → See "Your computer went to sleep" message
- User must manually refresh page

**After:**

- Computer sleeps → Wake up → Widgets automatically reload
- Seamless experience, no user action needed

## 🔍 Edge Cases Handled

1. **Multiple widgets:** Each iframe has its own detection
2. **Rapid tab switching:** Debounced with delays
3. **Component unmount:** Cleanup prevents memory leaks
4. **Null refs:** Checks for `iframeRef.current` before accessing

## 📝 Notes

- Only affects **proxy widgets** (iframe-based)
- Native widgets (non-iframe) are unaffected
- Minimal performance impact (2-second interval check)
- No network requests during detection (only time comparison)

## 🚀 Future Enhancements

Potential improvements:

1. **Visual indicator** during reload (subtle spinner)
2. **Configurable thresholds** via settings
3. **Retry logic** if reload fails
4. **Network status detection** (online/offline)
5. **User preference** to disable auto-reload

## 🐛 Known Limitations

- Relies on time gap detection (not 100% accurate)
- May occasionally reload unnecessarily on slow systems
- Doesn't detect network disconnection (only sleep)

## ✅ Benefits

- ✅ Better user experience
- ✅ No manual refresh needed
- ✅ Automatic recovery from sleep
- ✅ Handles tab switching
- ✅ Minimal performance impact

---

**Implementation Status:** ✅ Complete
**Testing Status:** ⏳ Needs user testing
**Documentation Status:** ✅ Complete

---

**Related Files:**

- `assets/js/sd-workspace.js` (lines 160-208)
- `includes/Modules/DashboardTunnel.php` (iframe source)

**Related Issues:**

- User reported "Your computer went to sleep" message
- Iframe connection loss on sleep/wake

**Version:** Added in v1.1.2+
**Date:** January 27, 2026
