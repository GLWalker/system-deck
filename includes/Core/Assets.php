<?php

/**
 * SystemDeck Assets Manager (Complete)
 */

declare(strict_types=1);

namespace SystemDeck\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Assets
{
    const CSS_CACHE_KEY = 'sd_css_cache_v4_';

    public static function init(): void
    {
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin_assets']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_frontend_assets']);
        add_action('updated_user_meta', [self::class, 'detect_color_change'], 10, 4);
    }

    /**
     * Single Source of Truth for CSS Assets.
     * Returns the strict load order: Core -> Common -> Temp -> Colors.
     */
    public static function get_core_styles(): array
    {
        return [
            ['handle' => 'sd-core', 'path' => 'assets/css/sd-core.css', 'deps' => ['dashicons']],
            ['handle' => 'sd-common', 'path' => 'assets/css/sd-common.css', 'deps' => ['sd-core']],
            ['handle' => 'sd-grid', 'path' => 'assets/css/sd-grid.css', 'deps' => ['sd-common']],
            ['handle' => 'sd-screen-meta', 'path' => 'assets/css/sd-screen-meta.css', 'deps' => ['sd-grid']],
            ['handle' => 'sd-wpcolors', 'path' => 'assets/css/sd-wpcolors.css', 'deps' => ['sd-screen-meta']]
        ];
    }

    /**
     * Centralized Asset Registration.
     * Uses modern WP 6.3+ script strategies (defer).
     */
    public static function register_all(): void
    {
        // 1. STYLES
        foreach (self::get_core_styles() as $style) {
            wp_register_style($style['handle'], SD_URL . $style['path'], $style['deps'], SD_VERSION);
        }


        // 2. SCRIPTS & STRATEGIES
        $react_deps = ['wp-element', 'wp-i18n', 'wp-components', 'wp-api-fetch'];
        $modern_args = ['strategy' => 'defer', 'in_footer' => true];



        // --- CORE: Workspace depends on Widget Component ---
        wp_register_script('sd-workspace-react', SD_URL . 'assets/js/sd-workspace.js',
            $react_deps,
            SD_VERSION, $modern_args
        );

        wp_register_script('sd-deck-js', SD_URL . 'assets/js/sd-deck.js', ['jquery'], SD_VERSION, $modern_args);
        wp_register_script('sd-system-js', SD_URL . 'assets/js/sd-system.js', ['jquery', 'jquery-ui-sortable'], SD_VERSION, $modern_args);

        wp_register_script('sd-toolbox-toggle-js', SD_URL . 'assets/js/sd-toolbox-toggle.js', ['jquery'], SD_VERSION, $modern_args);
        wp_register_script('sd-scanner-js', SD_URL . 'assets/js/sd-scanner.js', ['jquery'], SD_VERSION, $modern_args);

        // 3. LOCALIZATION
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $data = [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('sd_load_shell'),
            'root'     => esc_url_raw(rest_url()),
            'rest_nonce' => wp_create_nonce('wp_rest'),
            'screen_id' => $screen ? $screen->id : 'frontend'
        ];

        wp_localize_script('sd-workspace-react', 'sd_vars', $data);
        wp_localize_script('sd-deck-js', 'sd_vars', $data);
        wp_localize_script('sd-system-js', 'sd_vars', $data);
    }

    public static function enqueue_admin_assets(): void
    {
        // Enqueue Editor Assets
        if (function_exists('wp_enqueue_code_editor')) {
             wp_enqueue_code_editor(['type' => 'text/html']);
        }
        wp_enqueue_script('wp-tinymce');
        wp_enqueue_script('wp-editor');

        self::register_all();

        foreach (self::get_core_styles() as $style) {
            wp_enqueue_style($style['handle']);
        }
        wp_enqueue_style('wp-components');

        wp_enqueue_script('sd-workspace-react');
        wp_enqueue_script('sd-deck-js');
        wp_enqueue_script('sd-system-js');

        wp_enqueue_script('sd-toolbox-toggle-js');
        wp_enqueue_script('sd-scanner-js');

        wp_add_inline_style('sd-core', self::get_dynamic_css());
    }

    public static function enqueue_frontend_assets(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        self::enqueue_admin_assets();
    }

    // ... Cache & Color Logic ...
    public static function detect_color_change($meta_id, $object_id, $meta_key, $meta_value): void
    {
        if ($meta_key === 'admin_color') self::clear_css_cache($object_id);
    }

    public static function clear_css_cache($user_id): void
    {
        delete_transient(self::CSS_CACHE_KEY . $user_id);
        wp_cache_delete('sd_css_' . $user_id, 'system_deck');
    }

    public static function get_dynamic_css(): string
    {
        $user_id = get_current_user_id();
        $cache_key = 'sd_css_' . $user_id;
        $cache_group = 'system_deck';

        $cached = wp_cache_get($cache_key, $cache_group);
        if ($cached !== false) return (string)$cached;

        $transient_key = self::CSS_CACHE_KEY . $user_id;
        $cached = get_transient($transient_key);
        if ($cached !== false) {
            wp_cache_set($cache_key, $cached, $cache_group, HOUR_IN_SECONDS);
            return (string)$cached;
        }

        $scheme = get_user_option('admin_color') ?: 'fresh';
        $schemes = [
            'fresh' => ['#2271b1', '#135e96', '#2271b1', '#2271b1', '#d63638', '#1d2327', '#fff', '#f5f7f8', '#1d2327', '#72aee6', '#72aee6', '#2271b1', '#fff', '#fff', '#2c3339', '#a2aab2', '#72aee6'],
            'light' => ['#0073aa', '#0096dd', '#04a4cc', '#04a4cc', '#d64e07', '#e5e5e5', '#333', '#686868', '#888', '#fff', '#fff', '#888', '#fff', '#fff', '#ccc', '#686868', '#04a4cc'],
            'modern' => ['#3858e9', '#183ad6', '#3858e9', '#3858e9', '#3858e9', '#1e1e1e', '#fff', '#f2f2f2', '#3858e9', '#fff', '#fff', '#3858e9', '#fff', '#fff', '#0c0c0c', '#bbbbbb', '#7b90ff'],
            'blue' => ['#0073aa', '#0096dd', '#096484', '#e1a948', '#e1a948', '#52accc', '#fff', '#e5f8ff', '#096484', '#fff', '#fff', '#096484', '#fff', '#fff', '#4796b3', '#e2ecf1', '#fff'],
            'coffee' => ['#0073aa', '#0096dd', '#c7a589', '#59524c', '#9fa476', '#59524c', '#fff', '#f2f2f2', '#c7a589', '#fff', '#fff', '#c7a589', '#fff', '#fff', '#45403b', '#cdcbc9', '#c7a589'],
            'ectoplasm' => ['#0073aa', '#0096dd', '#a3b745', '#a3b745', '#d46f15', '#523f6d', '#fff', '#ece6f6', '#a3b745', '#fff', '#fff', '#a3b745', '#fff', '#fff', '#403156', '#cbc5d3', '#a3b745'],
            'midnight' => ['#0073aa', '#0096dd', '#e14d43', '#363b3f', '#69a8bb', '#363b3f', '#fff', '#f2f2f2', '#e14d43', '#fff', '#fff', '#e14d43', '#fff', '#fff', '#25282b', '#c3c4c5', '#e14d43'],
            'ocean' => ['#0073aa', '#0096dd', '#9ebaa0', '#738e96', '#aa9d88', '#738e96', '#fff', '#f2fcff', '#9ebaa0', '#fff', '#fff', '#9ebaa0', '#fff', '#fff', '#627b83', '#d5dddf', '#9ebaa0'],
            'sunrise' => ['#0073aa', '#0096dd', '#dd823b', '#dd823b', '#ccaf0b', '#cf4944', '#fff', '#f2f2f2', '#dd823b', '#fff', '#fff', '#dd823b', '#fff', '#fff', '#be3530', '#f1c8c7', '#f7e3d3']
        ];

        $colors = $schemes[$scheme] ?? $schemes['fresh'];
        $keys = ['link', 'link-focus', 'highlight-color', 'button-color', 'notification-color', 'menu-background', 'menu-text', 'menu-icon', 'menu-highlight-background', 'menu-highlight-text', 'menu-highlight-icon', 'menu-current-background', 'menu-current-text', 'menu-current-icon', 'menu-submenu-background', 'menu-submenu-text', 'menu-submenu-focus-text'];

        $css = "#systemdeck, #sd-retail-dock, .sd-dock-controls {\n";
        foreach ($keys as $i => $key) {
            $val = $colors[$i] ?? $colors[0];
            $css .= "    --sd-{$key}: {$val};\n";
        }
 $css .= "}\n";

        wp_cache_set($cache_key, $css, $cache_group, HOUR_IN_SECONDS);
        set_transient($transient_key, $css, DAY_IN_SECONDS);
        return $css;
    }
}
