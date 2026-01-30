# Filters Reference

SystemDeck provides filters to allow developers to modify data and behavior throughout the system.

## Security & Safety

### `sd_disable_sandbox`

Allows developers to disable the IFrame Sandbox engine. Useful for production safety to prevent any potential unauthorized access to the preview environment.

-   **File**: `includes/Modules/IFrameEngine.php`
-   **Default**: `false`

```php
// Disable sandbox in production
add_filter('sd_disable_sandbox', '__return_true');
```

---

## UI & Logic

### `sd_shell_classes`

Filters the CSS classes applied to the main SystemDeck shell container.

-   **File**: `includes/Modules/Renderer.php`
-   **Arguments**: `array $classes`

```php
add_filter('sd_shell_classes', function($classes) {
    if (is_admin()) {
        $classes[] = 'is-admin-view';
    }
    return $classes;
});
```

### `sd_parse_attr`

Filters the attributes array during HTML attribute generation.

-   **File**: `includes/Core/HtmlAttributes.php`
-   **Arguments**: `array $attributes`, `string $context`

### `sd_menu_items`

Filters the main menu items array before rendering.

-   **File**: `includes/Core/MenuEngine.php`
-   **Arguments**: `array $items`

### `sd_workspace_submenu_items`

Filters the automatically generated submenu items for workspaces in the sidebar.

-   **File**: `includes/Core/MenuEngine.php`
-   **Arguments**: `array $submenu_items`
