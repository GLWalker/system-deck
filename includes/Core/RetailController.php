<?php
/**
 * SystemDeck Retail Controller
 * Manages the "Retail Mode" (Frontend) logic and rendering.
 */
declare(strict_types=1);

namespace SystemDeck\Core;

if (!defined('ABSPATH')) { exit; }

class RetailController {
    public static function init(): void {
        // 0. AJAX Bridge (Active for all)
        add_action('wp_ajax_sd_export_theme_json', [self::class, 'handle_export_theme_json']);

        if (is_admin()) return;

        // Ensure preview parameter persists through server-side redirects
        add_filter('wp_redirect', [self::class, 'intercept_preview_redirect']);
        add_filter('redirect_post_location', [self::class, 'intercept_preview_redirect']);

        // 1. Detect Preview Mode (The Iframe)
        if (isset($_GET['sd_preview'])) {
            add_action('init', [self::class, 'clean_preview_mode']);

            // Phase 3: Intercept Theme JSON to swap styles
            if (!empty($_GET['sd_style'])) {
                add_filter('wp_theme_json_data_theme', [self::class, 'inject_variation']);
            }
        }

        // 2. Normal Retail Mode
        add_action('wp_footer', [self::class, 'render_shell'], 20);
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_assets']);
        add_action('wp_footer', [self::class, 'trigger_harvest'], 5);

        // 3. Initialize Retail Modules
        if (class_exists('SystemDeck\Modules\RetailSystem')) {
            \SystemDeck\Modules\RetailSystem::init();
        }
    }

    /**
     * Inject a specific style variation into the current page load.
     * This happens entirely in memory - no database writes needed.
     */
    public static function inject_variation($theme_json) {
        $slug = sanitize_text_field($_GET['sd_style']);

        if (class_exists('WP_Theme_JSON_Resolver')) {
            $variations = \WP_Theme_JSON_Resolver::get_style_variations();
            foreach ($variations as $v) {
                if (($v['slug'] ?? sanitize_title($v['title'])) === $slug) {
                    $theme_json->update_with($v);
                    break;
                }
            }
        }
        return $theme_json;
    }

    /**
     * clean_preview_mode
     * Ensures the iframe is clean (No Admin Bar, No Drawer).
     */
    public static function clean_preview_mode(): void {
        // Safe check: inside 'init' hook, pluggable functions are available
        if (!current_user_can('manage_options')) return;

        // Hide Admin Bar
        add_filter('show_admin_bar', '__return_false');

        // Prevent Drawer from loading inside itself
        remove_action('wp_footer', [self::class, 'render_shell'], 20);

        // Enqueue the Inspector Engine (The Magic Mouse) - Only if specifically requested
        if (isset($_GET['sd_inspect'])) {
            add_action('wp_enqueue_scripts', function() {
                $settings = [];
                if (class_exists('\WP_Theme_JSON_Resolver')) {
                    $settings = \WP_Theme_JSON_Resolver::get_theme_data()->get_settings();
                }

                wp_enqueue_script(
                    'sd-inspector-engine',
                    SD_URL . 'assets/js/sd-inspector-engine.js',
                    [],
                    SD_VERSION . '.' . time(),
                    true
                );

                wp_localize_script('sd-inspector-engine', 'sd_env', [
                    'blockDefinitions' => self::get_block_definitions(),
                    'layout' => $settings['layout'] ?? [],
                    'spacing' => $settings['spacing'] ?? [],
                    'isEditor' => false,
                    'debug' => true,
                ]);
            });
        }

        // Add class for CSS targeting
        add_filter('body_class', function($classes) {
            $classes[] = 'sd-is-preview';
            return $classes;
        });
    }

    public static function trigger_harvest(): void {
        if (!current_user_can('manage_options')) return;

        // Auto-harvest on frontend load to keep cache fresh
        $user_id = get_current_user_id();
        $context = new Context($user_id, 'retail', 'global', 'global');
        if (Harvester::needs_harvest($context)) {
            Harvester::harvest($context);
        }
    }

    public static function render_shell(): void {
        if (!current_user_can('manage_options')) return;
        if (class_exists('SystemDeck\Modules\Renderer')) {
            \SystemDeck\Modules\Renderer::render_shell();
        }
    }

    public static function enqueue_assets(): void {
        if (!current_user_can('manage_options')) return;

        // Load Core Assets
        if (class_exists(Assets::class)) Assets::register_all();

        // Enqueue Retail Engine
        wp_enqueue_script('sd-retail-system', SD_URL . 'assets/js/sd-retail-system.js', ['jquery'], SD_VERSION . '.' . time(), true);

        // Enqueue Core Dashboard (AdminDeck) for Frontend Use
        wp_enqueue_script('sd-deck-js');
        wp_enqueue_script('sd-system-js');
        wp_enqueue_script('sd-workspace-react');

        // Enqueue Inspector HUD (React)
        wp_enqueue_script('sd-inspector-hud', SD_URL . 'assets/js/sd-inspector-hud.js', ['wp-element', 'wp-components'], SD_VERSION . '.' . time(), true);

        // Ensure Core Styles are present
        foreach (Assets::get_core_styles() as $style) {
            wp_enqueue_style($style['handle']);
        }

        $user_id = get_current_user_id();
        $variations = [];
        if (class_exists('\WP_Theme_JSON_Resolver')) {
            $vars = \WP_Theme_JSON_Resolver::get_style_variations();
            foreach ($vars as $key => $var) {
                $slug = $var['slug'] ?? (isset($var['title']) ? sanitize_title($var['title']) : (string)$key);
                $title = $var['title'] ?? ucfirst($slug);
                $variations[] = ['title' => $title, 'slug' => $slug];
            }
        }

        $telemetry = StorageEngine::get('telemetry', new Context($user_id, 'retail', 'global', 'global'));

        wp_localize_script('sd-retail-system', 'sd_retail_vars', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('sd_load_shell'),
            'export_nonce' => wp_create_nonce('sd_retail_nonce'),
            'site_url' => home_url('/'),
            // Pre-load preferences from StorageEngine
            'state'    => StorageEngine::get('pref_retail_state', new Context($user_id, 'retail', 'global', 'global')),
            'blockDefinitions' => self::get_block_definitions(),
            'variations' => $variations,
            'telemetry' => $telemetry,
            'debug' => true
        ]);

        // Styles
        wp_enqueue_style('wp-components');
        wp_enqueue_style('sd-common');
    }

    /**
     * handle_export_theme_json
     * Streams sanitized telemetry as a theme-variation.json download.
     */
    public static function handle_export_theme_json(): void {
        if (!current_user_can('edit_theme_options')) {
            wp_die('Unauthorized');
        }

        // Verify Nonce
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'sd_retail_nonce')) {
            wp_die('Security check failed.');
        }

        $user_id = get_current_user_id();
        $context = new Context($user_id, 'retail', 'global', 'global');
        $telemetry = StorageEngine::get('telemetry', $context);

        if (empty($telemetry)) {
            wp_die('Telemetry data missing or empty.');
        }

        // Prepare variation schema (v3)
        $variation = [
            'version'  => 3,
            'settings' => $telemetry['settings'] ?? [],
            'styles'   => $telemetry['styles'] ?? [],
        ];

        // Strip Internal Metadata (Recursive cleanup)
        $variation = self::sanitize_variation($variation);

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="theme-variation.json"');
        echo json_encode($variation, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * get_block_definitions
     * Harvests lightweight block metadata for the Inspector Engine.
     */
    public static function get_block_definitions(): array {
        if (!class_exists('\WP_Block_Type_Registry')) return [];

        $registry = \WP_Block_Type_Registry::get_instance()->get_all_registered();
        $definitions = [];

        foreach ($registry as $name => $block_type) {
            $definitions[$name] = [
                'name'      => $name,
                'title'     => $block_type->title ?? $name,
                'selectors' => $block_type->selectors ?? null,
                'supports'  => $block_type->supports ?? null,
                'experimentalSelector' => $block_type->supports['__experimentalSelector'] ?? $block_type->{'__experimentalSelector'} ?? null
            ];
        }

        return $definitions;
    }

    /**
     * sanitize_variation
     * Recursively removes internal SystemDeck keys from export.
     */
    private static function sanitize_variation($data) {
        if (!is_array($data)) return $data;

        $internal_keys = ['refId', 'rgb', 'id', 'source', 'dna'];

        foreach ($data as $key => $value) {
            if (in_array($key, $internal_keys, true)) {
                unset($data[$key]);
                continue;
            }
            if (is_array($value)) {
                $data[$key] = self::sanitize_variation($value);
            }
        }
        return $data;
    }

    /**
     * intercept_preview_redirect
     * Ensures that if we are in a preview session, any redirect carries the flag forward.
     */
    public static function intercept_preview_redirect($location) {
        if (!isset($_GET['sd_preview']) && !isset($_SERVER['HTTP_REFERER'])) {
            return $location;
        }

        $is_preview = isset($_GET['sd_preview']) || (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'sd_preview=1') !== false);

        if ($is_preview) {
            $location = add_query_arg('sd_preview', '1', $location);

            // Carry style variation if present in current request or referer
            $style = $_GET['sd_style'] ?? null;
            if (!$style && isset($_SERVER['HTTP_REFERER'])) {
                parse_str(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY) ?? '', $ref_params);
                $style = $ref_params['sd_style'] ?? null;
            }

            if ($style) {
                $location = add_query_arg('sd_style', sanitize_text_field($style), $location);
            }
        }

        return $location;
    }
}
