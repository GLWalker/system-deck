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

        add_action('wp_footer', [self::class, 'render_shell'], 20);
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_assets']);
        add_action('wp_footer', [self::class, 'trigger_harvest'], 5);
    }

    /**
     * Trigger the Harvester if the user can manage options.
     */
    public static function trigger_harvest(): void {
        if (!current_user_can('manage_options')) return;

        // Construct a context for the current page
        $context = new Context(
            get_current_user_id(),
            'retail',
            'post',
            (string) get_the_ID(),
            'desktop' // TODO: Detect actual viewport in future
        );

        if (Harvester::needs_harvest($context)) {
            Harvester::harvest($context);
        }
    }

    public static function render_shell(): void {
        if (!current_user_can('manage_options')) return;

        // Delegate strictly to the Master Renderer
        // This ensures if we change the template path later, we only change it in ONE place.
        if (class_exists('SystemDeck\Modules\Renderer')) {
            \SystemDeck\Modules\Renderer::render_shell();
        }
    }

    public static function enqueue_assets(): void {
        if (!current_user_can('manage_options')) return;

        // 1. STACK REGISTRATION (Centralized)
        if (class_exists(Assets::class)) {
            Assets::register_all();
        }

        // 2. STYLES
        if (method_exists(Assets::class, 'get_core_styles')) {
            foreach (Assets::get_core_styles() as $style) {
                wp_enqueue_style($style['handle']);
            }
        }
        wp_enqueue_style('wp-components');
        wp_enqueue_style('react-grid-layout');

        // 3. SCRIPTS
        wp_enqueue_script('jquery');
        // React Grid Layout has its own physics engine, no need for UI Sortable

        if (function_exists('wp_enqueue_editor')) {
            wp_enqueue_editor();
        }

        // Enqueue from centralized handles
        wp_enqueue_script('sd-workspace-react');
        wp_enqueue_script('sd-deck-js');
        wp_enqueue_script('sd-system-js');
        wp_enqueue_script('sd-system-grid-js');
        wp_enqueue_script('sd-toolbox-toggle-js');

        // 4. DYNAMIC COLORS
        if (method_exists(Assets::class, 'get_dynamic_css')) {
            wp_add_inline_style('sd-core', Assets::get_dynamic_css());
        }
    }
}
