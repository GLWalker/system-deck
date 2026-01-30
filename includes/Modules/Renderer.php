<?php
/**
 * SystemDeck Renderer Module (The Single Source of Truth)
 * Handles the output of the SystemDeck UI into the footer.
 *
 * @package SystemDeck
 */

declare(strict_types=1);

namespace SystemDeck\Modules;

if (!defined('ABSPATH')) {
    exit;
}

class Renderer
{
    public static function init(): void
    {
        // Only hook Admin Footer here. Retail handles its own hooks.
        add_action('admin_footer', [self::class, 'render_shell']);

        // CLEANUP: Removed the direct AJAX hook.
        // This is now handled centrally by Core\AjaxHandler.
    }

    public static function render_shell(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Centralized Cookie Check
        if (isset($_COOKIE['sd_is_active']) && $_COOKIE['sd_is_active'] === 'true') {
            self::load_template();
        }
    }

    /**
     * AJAX Callback (Called by AjaxHandler)
     */
    public static function ajax_load_shell(): void
    {
        // Security is handled by AjaxHandler::verify_request() before this is called.

        ob_start();
        self::load_template();
        wp_send_json_success(['html' => ob_get_clean()]);
    }

    /**
     * THE SINGLE SOURCE OF TRUTH.
     * All shell loading requests (Admin, Retail, AJAX) must route through here.
     */
    public static function load_template(): void
    {
        if (file_exists(SD_PATH . 'templates/system-deck.php')) {
            include SD_PATH . 'templates/system-deck.php';
        }
    }
}