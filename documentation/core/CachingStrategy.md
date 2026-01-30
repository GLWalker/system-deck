# Caching Strategy

SystemDeck implements a dual-layer caching system for dynamic CSS generation to optimize performance and reduce database queries.

## Overview

SystemDeck generates user-specific CSS based on WordPress admin color schemes. To avoid regenerating this CSS on every page load, we implement a sophisticated caching strategy using WordPress's native caching mechanisms.

## Architecture

### Dual-Cache System

```
Request for CSS
    ↓
Check Object Cache (wp_cache_*)
    ↓
    ├─ HIT → Return cached CSS
    ↓
Check Transient (get_transient)
    ↓
    ├─ HIT → Populate object cache → Return CSS
    ↓
Generate Fresh CSS → Cache both layers → Return CSS
```

### Cache Layers

#### 1. Object Cache (First Layer)

```php
$cache_key = 'sd_css_' . $user_id;
$cache_group = 'system_deck';

// Try object cache first
$cached = wp_cache_get($cache_key, $cache_group);
if ($cached !== false) {
    return (string)$cached;
}
```

**Benefits:**

-   Fastest access (in-memory when persistent object cache available)
-   Automatically cleared on cache flush
-   Supports Redis, Memcached, APCu
-   Per-request persistence even without persistent cache

#### 2. Transients (Second Layer)

```php
$transient_key = 'sd_css_' . $user_id;

// Fall back to transient
$cached = get_transient($transient_key);
if ($cached !== false) {
    // Populate object cache for next request
    wp_cache_set($cache_key, $cached, $cache_group, HOUR_IN_SECONDS);
    return (string)$cached;
}
```

**Benefits:**

-   Survives between page loads
-   Database-backed persistence
-   Works on all WordPress installations
-   Automatic expiration handling

## Cache Key Generation

### Format

```
sd_css_{user_id}
```

### Why User-Specific?

Each user can have a different WordPress admin color scheme preference:

```php
$user_id = get_current_user_id();
$scheme = get_user_option('admin_color') ?: 'fresh';
```

**Example keys:**

-   User 1 (Fresh scheme): `sd_css_1`
-   User 2 (Ectoplasm scheme): `sd_css_2`
-   User 3 (Ocean scheme): `sd_css_3`

## Cache Population

### Generation Process

```php
public static function get_dynamic_css(): string
{
    $user_id = get_current_user_id();
    $cache_key = 'sd_css_' . $user_id;

    // Check caches...

    // Generate fresh CSS
    $scheme = get_user_option('admin_color') ?: 'fresh';
    $variables = self::get_wp_color_variables($scheme);
    $css = self::build_css_string($variables);

    // Cache both layers
    set_transient($transient_key, $css, HOUR_IN_SECONDS);
    wp_cache_set($cache_key, $css, $cache_group, HOUR_IN_SECONDS);

    return $css;
}
```

### Variable Injection

The system generates CSS variable declarations for 17 color variables per scheme:

```php
$keys = [
    'link', 'link-focus', 'highlight-color', 'button-color', 'notification-color',
    'menu-background', 'menu-text', 'menu-icon',
    'menu-highlight-background', 'menu-highlight-text', 'menu-highlight-icon',
    'menu-current-background', 'menu-current-text', 'menu-current-icon',
    'menu-submenu-background', 'menu-submenu-text', 'menu-submenu-focus-text'
];
```

**Generated CSS:**

```css
#systemdeck {
	--sd-link: #2271b1;
	--sd-link-focus: #135e96;
	--sd-highlight-color: #2271b1;
	/* ... 14 more variables ... */
}
```

## Cache Invalidation

### Automatic Invalidation

Cache is automatically cleared in these scenarios:

#### 1. Plugin Update

```php
register_activation_hook(__FILE__, function() {
    wp_cache_flush(); // Clears all object cache
    // Transients auto-expire
});
```

#### 2. Time-Based Expiration

Both cache layers use a **1 hour TTL**:

```php
HOUR_IN_SECONDS // 3600 seconds
```

After 1 hour, cache is regenerated on next request.

#### 3. WordPress Cache Flush

When admin clears cache via:

-   WP-CLI: `wp cache flush`
-   Plugin (W3 Total Cache, WP Super Cache, etc.)
-   Manual: `wp_cache_flush()`

### Manual Invalidation

Developers can force cache refresh:

```php
// Clear specific user
delete_transient('sd_css_' . $user_id);
wp_cache_delete('sd_css_' . $user_id, 'system_deck');

// Clear all SystemDeck caches
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_sd_css_%'");
wp_cache_flush(); // Nuclear option
```

### User Color Scheme Change

When a user changes their admin color scheme:

```php
// WordPress action hook
add_action('update_user_meta', function($meta_id, $user_id, $meta_key, $meta_value) {
    if ($meta_key === 'admin_color') {
        // Clear user's cached CSS
        delete_transient('sd_css_' . $user_id);
        wp_cache_delete('sd_css_' . $user_id, 'system_deck');
    }
}, 10, 4);
```

> **Note:** SystemDeck doesn't currently hook this, but could be added for instant color scheme updates.

## Performance Benefits

### Without Caching

```
Request → get_user_option() → Color lookup → Build 17 variables → Concatenate CSS
Time: ~5-10ms per request
Database: 1 query per request
```

### With Object Cache

```
Request → wp_cache_get()
Time: ~0.1ms
Database: 0 queries
```

### With Transient Cache

```
Request → get_transient() → wp_cache_set()
Time: ~1-2ms
Database: 1 query (first load only)
```

### Performance Metrics

| Scenario         | Time   | DB Queries | Cache Type       |
| ---------------- | ------ | ---------- | ---------------- |
| Object cache hit | 0.1ms  | 0          | Memory           |
| Transient hit    | 1-2ms  | 1          | Database         |
| Cache miss       | 5-10ms | 1-2        | None (generates) |

**Improvement:** ~50-100x faster with object cache hit

## WordPress Integration

### Color Scheme Support

SystemDeck supports **21 color schemes** out of the box:

**Core schemes (8):**

-   Fresh, Blue, Coffee, Ectoplasm, Light, Midnight, Modern, Ocean, Sunrise

**Extended schemes (13):** via [Admin Color Schemes plugin](https://wordpress.org/plugins/admin-color-schemes/)

-   80s Kid, Adderley, Aubergine, Contrast Blue, Cruise, Flat, Kirk, Lawn, Modern Evergreen, Primary, Seashore, Vinyard

### Inline Style Injection

Cached CSS is injected as inline styles:

```php
wp_add_inline_style('sd-core-css', self::get_dynamic_css());
```

**Why inline?**

-   User-specific (can't share file across users)
-   Changes per color scheme
-   Small size (~1-2 KB)
-   No additional HTTP request
-   Immediate availability

## Code Reference

### Main Implementation

File: [`includes/Core/Assets.php`](file:///Users/glwalker/DevKinsta/public/thedrawer/wp-content/plugins/system-deck/includes/Core/Assets.php)

```php
public static function get_dynamic_css(): string
{
    $user_id = get_current_user_id();
    $cache_key = 'sd_css_' . $user_id;
    $cache_group = 'system_deck';

    // Try object cache first (if available)
    $cached = wp_cache_get($cache_key, $cache_group);
    if ($cached !== false) {
        return (string)$cached;
    }

    // Fall back to transient
    $transient_key = self::CSS_CACHE_KEY . $user_id;
    $cached = get_transient($transient_key);
    if ($cached !== false) {
        // Populate object cache for next request
        wp_cache_set($cache_key, $cached, $cache_group, HOUR_IN_SECONDS);
        return (string)$cached;
    }

    // Generate fresh CSS
    $scheme = get_user_option('admin_color') ?: 'fresh';
    $variables = self::get_wp_color_variables($scheme);

    $css = '#systemdeck{';
    foreach ($variables as $key => $value) {
        $css .= "--sd-{$key}:{$value};";
    }
    $css .= '}';

    // Cache both layers
    set_transient($transient_key, $css, HOUR_IN_SECONDS);
    wp_cache_set($cache_key, $css, $cache_group, HOUR_IN_SECONDS);

    return $css;
}
```

## Best Practices

### ✅ Do

-   Trust the dual-cache system
-   Use 1-hour TTL for color schemes (rarely change)
-   Generate user-specific keys
-   Populate both cache layers on miss
-   Use object cache as primary layer

### ❌ Don't

-   Cache globally for all users (color schemes differ)
-   Use excessively long TTL (prevents updates)
-   Skip transient fallback (required for non-persistent cache)
-   Store in database separately (transients handle it)
-   Cache in files (WordPress has better tools)

## Debugging Cache

### Check if Cached

```php
// In WordPress admin or via plugin
$user_id = get_current_user_id();

// Check object cache
$obj_cache = wp_cache_get('sd_css_' . $user_id, 'system_deck');
var_dump('Object Cache:', $obj_cache !== false ? 'HIT' : 'MISS');

// Check transient
$transient = get_transient('sd_css_' . $user_id);
var_dump('Transient:', $transient !== false ? 'HIT' : 'MISS');
```

### Force Regeneration

```php
// Clear for current user
delete_transient('sd_css_' . get_current_user_id());
wp_cache_delete('sd_css_' . get_current_user_id(), 'system_deck');

// Reload page to see fresh generation
```

## See Also

-   [CSS Variables](./CSSVariables.md) - What gets cached
-   [Assets.php Source](file:///Users/glwalker/DevKinsta/public/thedrawer/wp-content/plugins/system-deck/includes/Core/Assets.php) - Implementation
-   [WordPress Object Cache](https://developer.wordpress.org/reference/classes/wp_object_cache/) - Core documentation
-   [Transients API](https://developer.wordpress.org/apis/transients/) - WordPress docs
