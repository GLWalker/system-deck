# CRITICAL ISSUE: Widget Hydration AJAX Blocked

## Problem Summary

The `sd_hydrate_widget` (formerly `sd_fetch_widget`) AJAX action is being blocked with a 403 error **before** it reaches the PHP handler function. This is evidenced by:

1. ✅ `sd_save_layout` works perfectly - logs appear, requests succeed
2. ❌ `sd_hydrate_widget` fails immediately - NO logs appear at all (not even the first `error_log`)
3. Both actions are registered identically in the same way
4. The function `WorkspaceRenderer::execute_widget_hydration()` is NEVER called

## Evidence

- Browser shows: `403 (Forbidden)` + "The link you followed has expired"
- Server logs: ZERO `[SD Hydration]` entries, even though the function starts with `error_log`
- This means WordPress's `admin-ajax.php` is rejecting the request before dispatch

## Attempted Fixes (All Failed)

1. ✗ Added `_wpnonce` parameter alongside `nonce`
2. ✗ Renamed action from `sd_fetch_widget` to `sd_hydrate_widget`
3. ✗ Added aggressive logging at function entry point
4. ✗ Multiple nonce verification approaches

## Root Cause Hypothesis

WordPress Core or a security layer (possibly Nginx/DevKinsta specific) is:

- Intercepting requests to `admin-ajax.php` with certain action patterns
- Applying CSRF protection BEFORE the `wp_ajax_*` action hook fires
- Blocking based on action name pattern or request characteristics

## Proposed Solutions

### Option 1: Use REST API Instead of admin-ajax

Replace the AJAX endpoint with a proper WordPress REST endpoint:

```php
// In WorkspaceRenderer.php
public static function init(): void {
    add_action('rest_api_init', [self::class, 'register_rest_routes']);
}

public static function register_rest_routes(): void {
    register_rest_route('systemdeck/v1', '/widget/(?P<id>[a-zA-Z0-9_-]+)', [
        'methods' => 'GET',
        'callback' => [self::class, 'rest_hydrate_widget'],
        'permission_callback' => function() {
            return current_user_can('manage_options');
        }
    ]);
}
```

###Option 2: Pre-load All Widget Content in Manifest
Instead of lazy-loading widget content via AJAX, hydrate everything server-side during initial workspace render. This is what `Registry::hydrate_manifest()` already does - we just need to ensure it executes callbacks for ALL widgets, not just active ones.

```php
// In Registry.php - modify hydrate_manifest to always execute callbacks
foreach ($definitions as $wid => $widget) {
    if (isset($widget['callback']) && is_callable($widget['callback'])) {
        ob_start();
        try {
            call_user_func($widget['callback']);
        } catch (\Throwable $e) {
            $widget['content'] = 'Error loading widget';
        }
        $widget['content'] .= ob_get_clean();
    }
    $hydrated_widgets[$wid] = $widget;
}
```

### Option 3: Debug DevKinsta/Nginx Configuration

Check if DevKinsta's Nginx has security rules blocking certain admin-ajax actions:

```bash
# Check Nginx logs for blocked requests
docker logs devkinsta_nginx | grep admin-ajax
```

## Recommendation

**Use Option 2 (Pre-load)** as the fastest path to stability. The lazy-loading optimization can be re-implemented later via REST API once the core system is stable.

---

_Claude Sonnet - SystemDeck Architecture Debug - Jan 23, 2026 07:15 UTC_
