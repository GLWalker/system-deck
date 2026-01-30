# SystemDeck Function Reference

This document provides a comprehensive reference for all PHP functions and class methods available within the SystemDeck codebase.

## Global Helper Functions

These functions are available globally and are the primary way **third-party developers** should interact with SystemDeck.

### `sd_register_workspace( string $id, array $args ): void`

Registers a new custom workspace type.

-   **$id** _(string)_: Unique identifier for the workspace (e.g., 'seo_audit').
-   **$args** _(array)_: Configuration arguments.
    -   `label`: Human-readable name.
    -   `layout`: Array defining the grid structure (e.g., `['full', 'split']`).
    -   `context`: 'admin' or 'retail'.
    -   `icon`: Dashicons class.

### `sd_register_widget( string $id, array $args ): void`

Registers a custom widget for use in workspaces.

-   **$id** _(string)_: Unique widget ID.
-   **$args** _(array)_: Widget configuration.
    -   `title`: Widget title.
    -   `callback`: Function calls that outputs the widget HTML.
    -   `context`: 'normal', 'side', or 'advanced'.
    -   `priority`: 'high', 'core', 'default', or 'low'.

### `sd_register_pin_item( string $id, array $args ): void`

Registers a new item in the SystemDeck toolbar (Pin Bar).

-   **$id** _(string)_: Unique item ID.
-   **$args** _(array)_: Configuration including icon, label, and callback logic.

### `sd_attr( string $context, array $attributes = [] ): void`

A utility helper that echoes HTML attributes for a specific context.

-   **$context** _(string)_: The context string (e.g., 'system-deck', 'header').
-   **$attributes** _(array)_: Optional array of default attributes to merge.

---

## Core Classes

Located in `includes/Core/`. These classes handle the plugin lifecycle and base functionality.

### `SystemDeck\Core\Registry`

The central storage brain for all registered components.

-   **`instance(): Registry`**: Singleton accessor.
-   **`register_workspace( string $id, array $args )`**: Internal handler for workspace registration.
-   **`register_widget( string $id, array $args )`**: Internal handler for widget registration.
-   **`register_pin_item( string $id, array $args )`**: Internal handler for pin item registration.
-   **`get_workspaces(): array`**: Returns all registered workspaces.
-   **`get_widgets(): array`**: Returns all registered widgets.
-   **`get_pin_items(): array`**: Returns all registered pin items.

### `SystemDeck\Core\Boot`

The application bootstrapper.

-   **`init()`**: Initializes the plugin, loads dependencies, and fires core hooks.
-   **`on_plugins_loaded()`**: Hook callback for `plugins_loaded`. Loads text domain.
-   **`on_init()`**: Hook callback for `init`.
-   **`on_rest_api_init()`**: Registers REST routes.

### `SystemDeck\Core\Assets`

Asset management and dynamic CSS generation.

-   **`init()`**: Registers enqueue hooks.
-   **`enqueue_admin_assets()`**: Enqueues scripts and styles for the admin and active frontend users. passes `sd_vars` (including `default_dock`) to JS.
-   **`get_dynamic_css(): string`**: Generates the CSS variable map based on the current user's Admin Color Scheme. Uses object caching and transients for performance.
-   **`detect_color_change()`**: Clears CSS cache when a user updates their profile.
-   **`clear_css_cache( int $user_id )`**: Manually clears the CSS cache.

### `SystemDeck\Core\UserPreferences`

Manages user-specific settings.

-   **`init()`**: Registers profile fields and save handlers.
-   **`render_profile_fields( WP_User $user )`**: Outputs the dock settings in the user profile.
-   **`save_profile_fields( int $user_id )`**: Sanitizes and saves profile settings.
-   **`is_incognito_active(): bool`**: Checks if the current user has enabled Incognito Mode.
-   **`get_default_dock(): string`**: Returns the user's preferred default dock state (e.g., 'standard-dock', 'right-dock').

### `SystemDeck\Core\HtmlAttributes`

Manages HTML attribute injection for safe and consistent markup.

-   **`do_attributes( string $context, array $attributes = [] )`**: Echoes attributes.
-   **`get_attributes( string $context, array $attributes = [] ): string`**: Returns attributes string.
-   **`parse_attributes( array $attributes, string $context ): array`**: Filter callback that applies logic (like adding classes based on dock state) to attribute arrays.

---

## Modules

Located in `includes/Modules/`. Independent features packaged as modules.

### `SystemDeck\Modules\Renderer`

Handles the injection of the SystemDeck UI shell.

-   **`init()`**: Helper to register footer and AJAX hooks.
-   **`render_shell()`**: Hook callback used to inject the deck HTML into the `admin_footer`. Checks cookies to ensure it only renders if active.
-   **`ajax_load_shell()`**: AJAX callback for lazy-loading the shell HTML if not present on page load.

### `SystemDeck\Modules\WorkspaceRenderer`

Responsible for the internal content of the deck.

-   **`render( string $workspace_id )`**: Main entry point. Fetches config, builds the React Manifest, and outputs the HTML grid skeleton.
-   **`get_filtered_widgets( string $workspace_id ): array`**: Merges generic widgets with context-specific ones (like Dashboard Proxy).

### `SystemDeck\Modules\IFrameEngine`

Handles the Responsive Preview Sandbox.

-   **`init()`**: Registers query vars.
-   **`maybe_render_sandbox()`**: Hook callback that intercepts page loads. If `?sd_preview=1` is present, it aborts the standard page load and renders the sandbox wrapper instead.

### `SystemDeck\Modules\IFrameInspector`

Injects visual debugging tools (Blueprints) into the preview frame.

-   **`init()`**: Registers footer hooks.
-   **`inject_inspector()`**: Outputs the CSS/JS for the blueprint grid, pixel rulers, and element information tools inside the preview iframe.

### `SystemDeck\Modules\DashboardProxy`

Adapts native WordPress Dashboard widgets for use in SystemDeck.

-   **`get_widgets(): array`**: Scrapes the global `$wp_meta_boxes` array, captures widget output buffers, and normalizes them into a SystemDeck-compatible widget structure.

### `SystemDeck\Modules\HealthBridge`

Provides simplified access to WordPress Site Health data.

-   **`get_status(): string`**: Returns 'good', 'recommended', or 'critical' based on cached Site Health tests.

### `SystemDeck\Modules\Notes`

The Notes module / widget.

-   **`init()`**: Registers CPT, Widget, and AJAX actions.
-   **`render()`**: Outputs the Notes widget HTML logic.
-   **`ajax_get_notes()`**: Returns JSON list of notes for the user.
-   **`ajax_save_note()`**: Handles creating or updating a note post.
-   **`ajax_pin_note()`**: Toggles the pinned meta state of a note.

### `SystemDeck\Modules\PinManager`

Handles the 'Pinning' of items to workspaces.

-   **`ajax_toggle_pin()`**: Toggles an arbitrary item ID in the user's pin list for a given workspace.

---

## Utilities

Located in `includes/Utils/`.

### `SystemDeck\Utils\Color`

A robust color manipulation library ported for SystemDeck.

-   **`__construct( string $color )`**: Initializes with a Hex or RGB color.
-   **`hex_to_rgb( string $color, float $alpha = null ): string`**: Converts hex to `rgb()` or `rgba()`.
-   **`rgb_to_hex( string $color ): string`**: Converts arbitrary RGB strings back to Hex.
-   **`adjust_color_contrast( string $bg, string $text, float $min = 7.0 ): string`**: Magically adjusts a text color to meet WCAG AA/AAA contrast ratios against a given background.
-   **`createPalette( int $count, float $step ): array`**: Generates a harmonious color palette based on the seed color.
-   **`createDarkPalette( int $count ): array`**: Specialized generator for dark mode variants.
-   **`makeNeon(): string`**: Returns a vibrant, neon version of the color (useful for dark mode highlights).
-   **`makeBright(): string`**: Ensures a color is bright enough for dark backgrounds.
-   **`relative_luminance( string $rgb ): float`**: Calculates the perceived brightness of a color (0.0 to 1.0).
