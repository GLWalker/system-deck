# Actions Reference

SystemDeck provides several action hooks to allow developers to tap into the lifecycle of the plugin.

## Plugin Lifecycle

### `system_deck_loaded`

Fires when the SystemDeck core bootstrapper has finished loading dependencies and initializing core components.

-   **File**: `includes/Core/Boot.php`
-   **Context**: `plugins_loaded`

```php
add_action('system_deck_loaded', function() {
    // Core is ready
});
```

### `system_deck_init`

Fires during the WordPress `init` hook, after SystemDeck has initialized its integrations (like the Admin Bar menu). This is the best place to register custom workspaces and widgets.

-   **File**: `includes/Core/Boot.php`
-   **Context**: `init`

```php
add_action('system_deck_init', function() {
    sd_register_widget('my_widget', [...]);
});
```

---

## Integration Hooks

### `wp_dashboard_setup`

SystemDeck triggers this standard WordPress action if it hasn't fired yet when loading the Dashboard Proxy. This ensures dashboard widgets are registered and available to the proxy.

-   **File**: `includes/Modules/DashboardProxy.php`
