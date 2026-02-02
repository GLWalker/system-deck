<?php
/**
 * SystemDeck Editor Controller
 * Manages the "Editor Mode" (FSE/Block Editor) logic and sidebar registration.
 */
declare(strict_types=1);

namespace SystemDeck\Core;

if (!defined('ABSPATH')) { exit; }

class EditorController {
    public static function init(): void {
        add_action('enqueue_block_editor_assets', [self::class, 'enqueue_editor_sidebar']);
        add_action('enqueue_block_assets', [self::class, 'enqueue_canvas_engine']);
    }

    public static function enqueue_editor_sidebar(): void {
        if (!current_user_can('edit_theme_options')) return;

        $path = 'assets/js/sd-fse-sidebar.js';
        $deps = ['wp-plugins', 'wp-edit-site', 'wp-element', 'wp-components', 'wp-data', 'jquery', 'wp-i18n'];

        $screen = get_current_screen();
        if ($screen && $screen->base !== 'site-editor') {
            $deps[] = 'wp-edit-post';
        }

        wp_enqueue_script(
            'sd-fse-sidebar',
            SD_URL . $path,
            $deps,
            SD_VERSION,
            true
        );

        wp_localize_script('sd-fse-sidebar', 'sd_editor_vars', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('sd_load_shell'),
            'export_nonce' => wp_create_nonce('sd_retail_nonce'),
            'telemetry' => StorageEngine::get('telemetry', new Context(get_current_user_id(), 'retail', 'global', 'global'))
        ]);

        wp_enqueue_style('sd-common', SD_URL . 'assets/css/sd-common.css', ['dashicons'], SD_VERSION);
    }

    public static function enqueue_canvas_engine(): void {
        // Enqueue into the block editor canvas (iframe)
        // Also used in the frontend Retail Mode preview iframe
        wp_enqueue_script(
            'sd-inspector-engine',
            SD_URL . 'assets/js/sd-inspector-engine.js',
            [], // Dependency on jQuery removed
            SD_VERSION,
            true
        );
    }
}
