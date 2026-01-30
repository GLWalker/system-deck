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
            'site_url' => home_url('/'),
            // Pre-load preferences from StorageEngine
            'state'    => StorageEngine::get('pref_retail_state', new Context($user_id, 'retail', 'global', 'global'))
        ]);

        // Styles
        wp_enqueue_style('sd-common');
    }
}
