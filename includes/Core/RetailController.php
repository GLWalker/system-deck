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
        if (is_admin()) return;

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

        // 4. Export Engine
        add_action('wp_ajax_sd_export_theme_json', [self::class, 'handle_export_theme_json']);
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

        // Enqueue the Inspector Engine (The Magic Mouse)
        add_action('wp_enqueue_scripts', function() {
            wp_enqueue_script(
                'sd-inspector-engine',
                SD_URL . 'assets/js/sd-inspector-engine.js',
                ['jquery'],
                SD_VERSION,
                true
            );
        });

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
        wp_enqueue_script('sd-retail-system', SD_URL . 'assets/js/sd-retail-system.js', ['jquery'], SD_VERSION, true);

        $user_id = get_current_user_id();
        wp_localize_script('sd-retail-system', 'sd_retail_vars', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('sd_load_shell'),
            'export_nonce' => wp_create_nonce('sd_retail_nonce'),
            'site_url' => home_url('/'),
            // Pre-load preferences from StorageEngine
            'state'    => StorageEngine::get('pref_retail_state', new Context($user_id, 'retail', 'global', 'global'))
        ]);

        // Styles
        wp_enqueue_style('sd-common');
    }

    /**
     * EXPORT ENGINE: Generates a valid theme.json from cached Telemetry.
     */
    public static function handle_export_theme_json() {
        // 1. Security & Permissions
        if (!current_user_can('edit_theme_options') || !check_ajax_referer('sd_retail_nonce', 'nonce', false)) {
            wp_die('Permission denied', 403);
        }

        // 2. Retrieve the "Universal Harvester" Data
        $user_id = get_current_user_id();
        $telemetry = \SystemDeck\Core\StorageEngine::get('telemetry', new Context($user_id, 'retail', 'global', 'global'));

        if (!$telemetry || empty($telemetry['settings'])) {
            wp_die('No telemetry found. Please load the Inspector first.', 404);
        }

        // 3. Construct Payload (Schema v3)
        $export = [
            '$schema'  => 'https://schemas.wp.org/trunk/theme.json',
            'version'  => 3,
            'title'    => ($telemetry['theme'] ?? 'Theme') . ' (SystemDeck Variation)',
            'settings' => self::clean_for_export($telemetry['settings']),
            'styles'   => self::clean_for_export($telemetry['styles']),
            'customTemplates' => $telemetry['customTemplates'] ?? [],
            'templateParts'   => $telemetry['templateParts'] ?? []
        ];

        // 4. Force Download
        $filename = 'theme-variation-' . date('Y-m-d-His') . '.json';

        header('Content-Description: File Transfer');
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');

        echo json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Sanitizer: Removes SystemDeck internal keys (like 'rgb') to ensure valid schema.
     */
    private static function clean_for_export($array) {
        if (!is_array($array)) return $array;

        foreach ($array as $key => &$value) {
            // Strip our internal logic keys
            if ($key === 'rgb' || $key === 'refId') {
                unset($array[$key]);
            } elseif (is_array($value)) {
                $value = self::clean_for_export($value);
            }
        }
        return $array;
    }
}
