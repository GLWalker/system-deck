<?php
/**
 * SystemDeck Dashboard Proxy
 * Captures native WordPress Dashboard widgets and delegates rendering to the Tunnel.
 */
declare(strict_types=1);

namespace SystemDeck\Widgets;

if (!defined('ABSPATH')) { exit; }

use SystemDeck\Core\Registry;
use SystemDeck\Modules\DashboardTunnel;

class DashboardProxy
{
    public static function init(): void
    {
        add_action('sd_register_widgets', [self::class, 'setup_proxy'], 20);
    }

    public static function setup_proxy(): void
    {
        $user_id = get_current_user_id();
        $active_proxies = get_user_meta($user_id, 'sd_active_proxy_widgets', true) ?: [];

        foreach ($active_proxies as $id) {
            // Beautify Title
            $title = ucwords(str_replace(['wpseo-', 'aioseo-', 'dashboard_', 'wc-', '-', '_'], ['Yoast ', 'AIOSEO ', '', 'Woo ', ' ', ' '], $id));

            Registry::register_widget('sd_proxy_' . $id, [
                'id'       => 'sd_proxy_' . $id,
                'title'    => trim($title),
                'callback' => function() use ($id) {
                     // Tell the frontend this is a tunneled widget
                     if (class_exists(DashboardTunnel::class)) {
                         DashboardTunnel::iframe($id);
                     } else {
                         echo 'Tunnel Module Missing';
                     }
                },
                'icon'     => 'dashicons-wordpress',
                'origin'   => $id // Critical: Used by JS to build tunnel URL
            ]);
        }
    }
}
