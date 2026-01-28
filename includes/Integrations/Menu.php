<?php
/**
 * SystemDeck Menu Integration
 * Injects the SystemDeck toggle and menu items into the Admin Bar.
 *
 * @package SystemDeck
 */

declare(strict_types=1);

namespace SystemDeck\Integrations;

if (!defined('ABSPATH')) {
    exit;
}

class Menu
{
    public static function init(): void
    {
        add_action('admin_bar_menu', [self::class, 'register_toolbar_node'], 999);
    }

    public static function register_toolbar_node(\WP_Admin_Bar $wp_admin_bar): void
    {



        $wp_admin_bar->add_node([
            'id'    => 'system-deck-toggle',
            'title' => '<span class="ab-icon dashicons-index-card"></span><span class="ab-label">SystemDeck</span>',
            'href'  => '#',
            'meta'  => [
                'title' => 'Toggle SystemDeck',
                'onclick' => 'return false;', // Prevent default nav, let JS handle it
            ]
        ]);
    }
}
