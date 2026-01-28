<?php
/**
 * System Diagnostics Widget (Crash Fix)
 */
declare(strict_types=1);
namespace SystemDeck\Widgets;
if (!defined('ABSPATH')) { exit; }
use SystemDeck\Core\Registry;
use SystemDeck\Core\Telemetry;
use SystemDeck\Core\UI;

class SystemDiagnostics {
    public static function init(): void {
        Registry::register_widget('sd_widget_diagnostics', [
            'id'       => 'sd_widget_diagnostics', // Ensure ID is present
            'title'    => 'System Diagnostics',
            'callback' => [self::class, 'render'],
            'icon'     => 'dashicons-heart',
        ]);
    }

    public static function render(): void {
        $metrics = Telemetry::get_all_metrics();
        $user_id = get_current_user_id();
        $workspace_id = 'default';

        $pins = get_user_meta($user_id, 'sd_pins_' . $workspace_id, true) ?: [];
        $pinned_ids = is_array($pins) ? array_column($pins, 'id') : [];

        echo '<div class="sd-pin-wrapper">';
        echo '<div id="sd-system-diagnostics-root" class="sd-widget-pin-grid ui-sortable">';

        foreach ($metrics as $key => $data) {
            $metric_id = 'metric_' . $key;
            $is_pinned = in_array($metric_id, $pinned_ids);

            // Construct specific pin data for this metric
            $pin_data = [
                'id'    => $metric_id,
                'type'  => 'metric',
                'label' => $data['label'],
                'value' => $data['value'], // UI class strips tags for JSON
                'icon'  => $data['icon']
            ];

            // Render using the standardized UI helper
            UI::render_pin_item(
                $key,
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
