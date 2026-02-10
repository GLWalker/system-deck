<?php

/**
 * Plugin Name: SystemDeck
 * Plugin URI:  https://systemdeck.dev
 * Description: High-performance runtime shell for WordPress. Connects wp-admin to a unified React workspace.
 * Version:     1.7.0
 * Author:      SystemDeck
 * Text Domain: systemdeck
 * Domain Path: /languages
 * License:     GPL-2.0-or-later
 * Requires PHP: 8.0
 */

defined('ABSPATH') || exit;

// Constants
define('SYSTEMDECK_VERSION', '1.7.0');
define('SYSTEMDECK_MIN_WP', '6.7');
define('SYSTEMDECK_MIN_PHP', '8.0');
define('SYSTEMDECK_PATH', plugin_dir_path(__FILE__));
define('SYSTEMDECK_URL', plugin_dir_url(__FILE__));

// SAFETY CHECKS

if (version_compare(PHP_VERSION, SYSTEMDECK_MIN_PHP, '<')) {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__('SystemDeck requires PHP 8.0 or higher.', 'systemdeck');
        echo '</p></div>';
    });
    return;
}

if (version_compare(get_bloginfo('version'), SYSTEMDECK_MIN_WP, '<')) {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__('SystemDeck requires WordPress 6.7 or higher.', 'systemdeck');
        echo '</p></div>';
    });
    return;
}

// Core Includes
// We manual require MenuEngine because Autoloader is off until Phase 3 (CanvasEngine)
if (file_exists(SYSTEMDECK_PATH . 'core/MenuEngine.php')) {
    require_once SYSTEMDECK_PATH . 'core/MenuEngine.php';
}
// Permissions (Keep this for the safety check)
function systemdeck_user_can_boot(): bool
{
    // Must be logged in
    if (!is_user_logged_in()) {
        return false;
    }

    $user_id = get_current_user_id();
    $user = wp_get_current_user();

    if (!$user || !$user->exists()) {
        return false;
    }

    // Default boot: admin-only
    $can_boot = user_can($user, 'manage_options');

    /**
     * Filter to allow other roles to boot SystemDeck.
     * Example usage: allow editors or authors to boot.
     *
     * add_filter('systemdeck_user_can_boot', function($can_boot, $user_id) {
     *     $user = get_user_by('id', $user_id);
     *     if ($user && in_array('editor', $user->roles, true)) {
     *         return true; // allow editor role
     *     }
     *     return $can_boot; // fallback to default
     * }, 10, 2);
     */
    return (bool) apply_filters('systemdeck_user_can_boot', $can_boot, $user_id);
}

class SystemDeck_Assets
{
    const CSS_CACHE_KEY = 'sd_css_cache_v5_';

    /**
     * Boot the System.
     * Hooked into 'init' to wait for User Session.
     */
    public static function run(): void
    {
        if (function_exists('systemdeck_user_can_boot') && !systemdeck_user_can_boot()) {
            return;
        }

        add_action('enqueue_block_editor_assets', [self::class, 'register_assets']);
        add_action('wp_enqueue_scripts', [self::class, 'register_assets']);
        add_action('admin_enqueue_scripts', [self::class, 'register_assets']);
        add_action('updated_user_meta', [self::class, 'detect_color_change'], 10, 4);

        // Load Text Domain
        load_plugin_textdomain('systemdeck', false, dirname(plugin_basename(__DIR__)) . '/languages');

        // Admin Bar Toggle
        add_action('admin_bar_menu', [self::class, 'register_admin_bar'], 999);
    }

    public static function register_assets(): void
    {
        // 1. Register Shell Assets (The Immutable Container)
        // These are the ONLY things this file cares about now.
        wp_register_style(
            'systemdeck-shell',
            SYSTEMDECK_URL . 'assets/systemdeck-shell.css',
            ['dashicons'],
            SYSTEMDECK_VERSION
        );
        wp_register_script(
            'systemdeck-shell',
            SYSTEMDECK_URL . 'assets/systemdeck-shell.js',
            ['jquery'],
            SYSTEMDECK_VERSION,
            true
        );

        // 2. Build Payload
        // We still need this to inject the HTML into the page via JS
        $config = [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('systemdeck_shell'),
            'user' => [
                'id' => get_current_user_id(),
                'name' => wp_get_current_user()->display_name
            ]
        ];

        $json_payload = wp_json_encode([
            'config' => $config,
            'shell_html' => self::get_deck_template()
        ]);

        // 3. Injector Script
        // NOTE: We attach this to 'systemdeck-shell' now, not 'systemdeck-runtime'
        $injector_script = "
            window.SYSTEMDECK_BOOTSTRAP = {$json_payload};
            (function() {
                if (document.getElementById('systemdeck')) return;

                var div = document.createElement('div');
                div.innerHTML = window.SYSTEMDECK_BOOTSTRAP.shell_html;
                var shell = div.firstElementChild;

                function inject() {
                    if (!document.body.contains(shell)) {
                        document.body.appendChild(shell);
                    }
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', inject);
                } else {
                    inject();
                }
            })();
        ";

        wp_add_inline_script('systemdeck-shell', $injector_script, 'before');

        // 4. Enqueue The Shell
        wp_enqueue_style('systemdeck-shell');
        wp_enqueue_script('systemdeck-shell');

        // 5. Dynamic CSS (Admin Colors)
        wp_add_inline_style('systemdeck-shell', self::get_dynamic_css());
    }

    private static function get_deck_template(): string
    {
        $admin_color = get_user_option('admin_color') ?: 'fresh';
        ob_start();
        ?>

        <div id="systemdeck" role="dialog" aria-hidden="true"
            class="sd-closed wp-core-ui admin-color-<?php echo esc_attr($admin_color); ?> standard-dock"
            data-initial-theme="light" data-theme="light" data-default-dock="standard-dock">

            <!-- ================= HEADER BAR ================= -->
            <header id="sd-header-bar" class="nojq">
                <!-- Drawer Icon: Minimize Dock -->
                <button type="button" class="sd-drawer-icon sd-btn-icon"
                    title="<?php esc_attr_e('Minimize Dock', 'systemdeck'); ?>">
                    <span class="dashicons dashicons-index-card"></span>
                </button>

                <!-- Left: Workspace Title -->
                <div class="sd-header-left">
                    <h2 id="sd-workspace-title">SystemDeck</h2>
                </div>

                <!-- Right: Controls -->
                <div class="sd-header-right">

                    <!-- Dock Buttons -->
                    <div class="sd-dock-controls">
                        <button type="button" data-dock="left-dock" class="sd-btn-icon"
                            title="<?php esc_attr_e('Dock Left', 'systemdeck'); ?>">
                            <span class="dashicons dashicons-arrow-left-alt"></span>
                        </button>
                        <button type="button" data-dock="base-dock" class="sd-btn-icon"
                            title="<?php esc_attr_e('Dock Base', 'systemdeck'); ?>">
                            <span class="dashicons dashicons-minus"></span>
                        </button>
                        <button type="button" data-dock="right-dock" class="sd-btn-icon"
                            title="<?php esc_attr_e('Dock Right', 'systemdeck'); ?>">
                            <span class="dashicons dashicons-arrow-right-alt"></span>
                        </button>
                        <button type="button" data-dock="standard-dock" class="sd-btn-icon"
                            title="<?php esc_attr_e('Standard Dock', 'systemdeck'); ?>">
                            <span class="dashicons dashicons-randomize"></span>
                        </button>
                        <button type="button" data-dock="full-dock" class="sd-btn-icon"
                            title="<?php esc_attr_e('Full Screen', 'systemdeck'); ?>">
                            <span class="dashicons dashicons-fullscreen-alt"></span>
                        </button>
                    </div>

                    <!-- Close Button -->
                    <button type="button" id="sd-close-button" class="sd-btn-icon"
                        title="<?php esc_attr_e('Close', 'systemdeck'); ?>">
                        <span class="dashicons dashicons-no"></span>
                    </button>
                </div>
            </header>

            <!-- ================= MAIN WRAP ================= -->
            <div id="sd-wrap">

                <!-- ================= MENU ASIDE ================= -->
                <aside id="sd-menumain" role="navigation">
                    <?php
                    // Manual Render because we are in Phase 2
                    if (class_exists('SystemDeck\Core\MenuEngine')) {
                        try {
                            $menu_engine = new \SystemDeck\Core\MenuEngine();
                            $menu_engine->render();
                        } catch (\Exception $e) {
                            error_log('SystemDeck MenuEngine Error: ' . $e->getMessage());
                            echo '';
                        }
                    } elseif (file_exists(SYSTEMDECK_PATH . 'core/MenuEngine.php')) {
                        require_once SYSTEMDECK_PATH . 'core/MenuEngine.php';
                        if (class_exists('SystemDeck\Core\MenuEngine')) {
                            (new \SystemDeck\Core\MenuEngine())->render();
                        }
                    }
                    ?>
                </aside>

                <!-- ================= WORKSPACE CONTENT ================= -->
                <section id="sd-workspace-content">
                    <div id="sd-workspacewrap">
                        <!-- Dynamic workspace content loads here -->
                    </div>
                </section>

            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    public static function get_dynamic_css(): string
    {
        $user_id = get_current_user_id();
        $cache_key = 'sd_css_' . $user_id;
        $cache_group = 'system_deck';

        // Attempt object cache
        $cached = wp_cache_get($cache_key, $cache_group);
        if ($cached !== false) {
            return (string) $cached;
        }

        // Attempt transient cache
        $transient_key = self::CSS_CACHE_KEY . $user_id;
        $cached = get_transient($transient_key);
        if ($cached !== false) {
            wp_cache_set($cache_key, $cached, $cache_group, HOUR_IN_SECONDS);
            return (string) $cached;
        }

        // Get user's color scheme
        $scheme = get_user_option('admin_color') ?: 'fresh';

        /**
         * Color definitions extracted from WordPress's actual admin color scheme CSS files
         * (wp-admin/css/colors/{scheme}/colors.css)
         *
         * Keys: menu-bg, menu-text, menu-highlight-bg, menu-highlight-text,
         *       submenu-bg, submenu-text, submenu-focus, icon-base, icon-focus
         */
        $schemes = [
            // Default (fresh) - wp-admin/css/admin-menu.css
            'fresh' => [
                'menu-bg' => '#1d2327',
                'menu-text' => '#fff',
                'menu-highlight-bg' => '#2271b1',
                'menu-highlight-text' => '#fff',
                'submenu-bg' => '#2c3338',
                'submenu-text' => '#c3c4c7',
                'submenu-focus' => '#72aee6',
                'icon-base' => '#a7aaad',
                'icon-focus' => '#72aee6',
            ],
            'light' => [
                'menu-bg' => '#e5e5e5',
                'menu-text' => '#333',
                'menu-highlight-bg' => '#888',
                'menu-highlight-text' => '#fff',
                'submenu-bg' => '#f1f1f1',
                'submenu-text' => '#686868',
                'submenu-focus' => '#04a4cc',
                'icon-base' => '#999',
                'icon-focus' => '#ccc',
            ],
            'modern' => [
                'menu-bg' => '#1e1e1e',
                'menu-text' => '#fff',
                'menu-highlight-bg' => '#3858e9',
                'menu-highlight-text' => '#fff',
                'submenu-bg' => '#2f2f2f',
                'submenu-text' => '#bbbbbb',
                'submenu-focus' => '#7b90ff',
                'icon-base' => '#f3f1f1',
                'icon-focus' => '#fff',
            ],
            'blue' => [
                'menu-bg' => '#52accc',
                'menu-text' => '#fff',
                'menu-highlight-bg' => '#096484',
                'menu-highlight-text' => '#fff',
                'submenu-bg' => '#4796b3',
                'submenu-text' => '#e2ecf1',
                'submenu-focus' => '#fff',
                'icon-base' => '#e5f8ff',
                'icon-focus' => '#fff',
            ],
            'midnight' => [
                'menu-bg' => '#363b3f',
                'menu-text' => '#fff',
                'menu-highlight-bg' => '#e14d43',
                'menu-highlight-text' => '#fff',
                'submenu-bg' => '#25282b',
                'submenu-text' => '#c3c4c5',
                'submenu-focus' => '#e14d43',
                'icon-base' => '#f1f2f3',
                'icon-focus' => '#fff',
            ],
            'sunrise' => [
                'menu-bg' => '#cf4944',
                'menu-text' => '#fff',
                'menu-highlight-bg' => '#dd823b',
                'menu-highlight-text' => '#fff',
                'submenu-bg' => '#b43c38',
                'submenu-text' => '#f1c8c7',
                'submenu-focus' => '#fff',
                'icon-base' => '#f3f1f1',
                'icon-focus' => '#fff',
            ],
            'ectoplasm' => [
                'menu-bg' => '#523f6d',
                'menu-text' => '#fff',
                'menu-highlight-bg' => '#a3b745',
                'menu-highlight-text' => '#fff',
                'submenu-bg' => '#413256',
                'submenu-text' => '#cbc5d3',
                'submenu-focus' => '#a3b745',
                'icon-base' => '#ece6f6',
                'icon-focus' => '#fff',
            ],
            'ocean' => [
                'menu-bg' => '#738e96',
                'menu-text' => '#fff',
                'menu-highlight-bg' => '#9ebaa0',
                'menu-highlight-text' => '#fff',
                'submenu-bg' => '#627b83',
                'submenu-text' => '#d5dddf',
                'submenu-focus' => '#fff',
                'icon-base' => '#f2fcff',
                'icon-focus' => '#fff',
            ],
            'coffee' => [
                'menu-bg' => '#59524c',
                'menu-text' => '#fff',
                'menu-highlight-bg' => '#c7a589',
                'menu-highlight-text' => '#fff',
                'submenu-bg' => '#46403c',
                'submenu-text' => '#cdcbc9',
                'submenu-focus' => '#c7a589',
                'icon-base' => '#f3f2f1',
                'icon-focus' => '#fff',
            ],
        ];

        $colors = $schemes[$scheme] ?? $schemes['fresh'];

        // Build CSS variables - these map directly to what the CSS expects
        $css = "/** SystemDeck Colors | User: {$user_id} | Scheme: {$scheme} */\n#systemdeck {\n";
        $css .= "  --sd-menu-background: {$colors['menu-bg']};\n";
        $css .= "  --sd-menu-text: {$colors['menu-text']};\n";
        $css .= "  --sd-menu-highlight-background: {$colors['menu-highlight-bg']};\n";
        $css .= "  --sd-menu-highlight-text: {$colors['menu-highlight-text']};\n";
        $css .= "  --sd-menu-current-background: {$colors['menu-highlight-bg']};\n";
        $css .= "  --sd-menu-current-text: {$colors['menu-highlight-text']};\n";
        $css .= "  --sd-menu-current-icon: {$colors['menu-highlight-text']};\n";
        $css .= "  --sd-menu-submenu-background: {$colors['submenu-bg']};\n";
        $css .= "  --sd-menu-submenu-text: {$colors['submenu-text']};\n";
        $css .= "  --sd-menu-submenu-focus-text: {$colors['submenu-focus']};\n";
        $css .= "  --sd-menu-icon: {$colors['icon-base']};\n";
        $css .= "  --sd-menu-highlight-icon: {$colors['icon-focus']};\n";
        // Link colors - derived from highlight colors
        $css .= "  --sd-link: {$colors['menu-highlight-bg']};\n";
        $css .= "  --sd-link-focus: {$colors['submenu-focus']};\n";
        $css .= "  --sd-highlight-color: {$colors['menu-highlight-bg']};\n";
        // Button colors - for future use
        $css .= "  --sd-button-primary-bg: {$colors['menu-highlight-bg']};\n";
        $css .= "  --sd-button-primary-text: {$colors['menu-highlight-text']};\n";
        $css .= "  --sd-button-primary-hover: {$colors['submenu-focus']};\n";
        $css .= "  --sd-button-secondary-bg: transparent;\n";
        $css .= "  --sd-button-secondary-text: {$colors['menu-highlight-bg']};\n";
        $css .= "  --sd-button-secondary-border: {$colors['menu-highlight-bg']};\n";
        $css .= "}\n";

        wp_cache_set($cache_key, $css, $cache_group, HOUR_IN_SECONDS);
        set_transient($transient_key, $css, DAY_IN_SECONDS);

        return $css;
    }

    public static function detect_color_change($meta_id, $object_id, $meta_key, $meta_value): void
    {
        if ($meta_key === 'admin_color') {
            wp_cache_delete('sd_css_' . $object_id, 'system_deck');
            delete_transient(self::CSS_CACHE_KEY . $object_id);
        }
    }

    public static function register_admin_bar($wp_admin_bar): void
    {
        $wp_admin_bar->add_node([
            'id' => 'system-deck-toggle',
            'title' => '<span class="ab-icon dashicons-index-card"></span><span class="ab-label">SystemDeck</span>',
            'href' => '#',
            'meta' => ['title' => __('Toggle SystemDeck', 'systemdeck'), 'onclick' => 'return false;'],
        ]);
    }
}

// Wait for init
add_action('init', ['SystemDeck_Assets', 'run']);
