<?php
/**
 * System Diagnostics (Ported from Admin Drawer)
 * PHP and server environment details widget.
 */
declare(strict_types=1);

namespace SystemDeck\Widgets;

if (!defined('ABSPATH')) { exit; }

use SystemDeck\Core\Registry;
use SystemDeck\Core\Telemetry;
use SystemDeck\Core\UI;
use SystemDeck\Core\Locker;

class SystemStatusWidget {

    public static function init(): void {
        Registry::register_widget('sd_widget_sys_status', [
            'id'       => 'sd_widget_sys_status',
            'title'    => __('System Diagnostics', 'system-deck'),
            'callback' => [self::class, 'render'],
            'icon'     => 'dashicons-heart',
        ]);
    }

    public static function render(): void {
        $metrics = Telemetry::get_all_metrics();
        $user_id = get_current_user_id();

        // Retrieve saved pins
        $context = new \SystemDeck\Core\Context($user_id, 'default');
        $pins = \SystemDeck\Core\StorageEngine::get('pins', $context) ?: [];

        echo '<div class="sd-pin-wrapper">';
        echo '<div id="sd-system-diagnostics-root" class="sd-widget-pin-grid ui-sortable">';

        foreach ($metrics as $key => $data) {
            $metric_id = 'metric_' . $key;
            $is_pinned = isset($pins[$metric_id]);

            // Data for pinning
            $pin_data = [
                'id'    => $metric_id,
                'type'  => 'metric',
                'label' => $data['label'],
                'value' => strip_tags((string)$data['value']),
                'icon'  => $data['icon']
            ];

            // Render using standard UI helper
            UI::render_pin_item(
                $metric_id,
                $data['label'],
                (string)$data['value'],
                $data['icon'],
                $pin_data,
                $is_pinned
            );
        }

        echo '</div>'; // End grid
        echo '</div>'; // End wrapper
    }
}
