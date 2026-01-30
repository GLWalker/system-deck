<?php
/**
 * Time Monitor Widget
 * Visualizes Server, WP, and Browser time drifts.
 */
declare(strict_types=1);

namespace SystemDeck\Widgets;

if (!defined('ABSPATH')) { exit; }

use SystemDeck\Core\Registry;
use SystemDeck\Core\Telemetry;
use SystemDeck\Core\Assets;

class TimeMonitorWidget {

    public static function init(): void {
        Registry::register_widget('sd_widget_time_monitor', [
            'id'       => 'sd_widget_time_monitor',
            'title'    => __('Time Monitor', 'system-deck'),
            'callback' => [self::class, 'render'],
            'icon'     => 'dashicons-clock',
            'enqueue_assets' => [
                'css' => ['sd-time-monitor-css'],
                'js'  => ['sd-time-monitor-js']
            ]
        ]);

        // Register Assets
        add_action('wp_enqueue_scripts', [self::class, 'register_assets']);
        add_action('admin_enqueue_scripts', [self::class, 'register_assets']);
    }

    public static function register_assets(): void {
        wp_register_style('sd-time-monitor-css', \SD_URL . 'assets/css/sd-time-monitor.css', [], \SD_VERSION);
        wp_register_script('sd-time-monitor-js', \SD_URL . 'assets/js/sd-time-monitor.js', ['jquery'], \SD_VERSION, true);

        // ALWAYS Enqueue for now to ensure availability during AJAX transitions
        // In the future, we can make this conditional on whether the widget is actually in the active workspace.
        if (current_user_can('manage_options')) {
            wp_enqueue_style('sd-time-monitor-css');
            wp_enqueue_script('sd-time-monitor-js');
        }
    }

    public static function render(): void {
        // We reuse the existing Telemetry engine to get time diagnostics
        // But the current Telemetry class keeps get_time_diagnostics private!
        // We might need to make it public or access via get_all_metrics -> but get_all_metrics formats it.
        // Let's use get_all_metrics and parse, or just replicate the simple logic here for speed/independence.
        // Actually, looking at Telemetry class, 'time_srv' and 'time_wp' are formatted strings.
        // We need raw timestamps for the JS ticker.

        // Quick access to raw times
        $server_ts = time();
        $wp_ts = current_time('timestamp');
        $tz_server = date_default_timezone_get();
        $tz_wp = get_option('timezone_string') ?: 'Offset ' . get_option('gmt_offset');

        // For Uptime, we can check Telemetry if we modify it, or just use simple check here
        $uptime = 'N/A';
        if (@is_readable('/proc/uptime')) {
            $u = @file_get_contents('/proc/uptime');
             if ($u) {
                $parts = explode(' ', trim($u));
                $uptime_sec = (int)$parts[0];
                $dtF = new \DateTime('@0');
                $dtT = new \DateTime("@$uptime_sec");
                $uptime = $dtF->diff($dtT)->format('%ad %hh %im');
            }
        }

        ?>
        <div id="sd-time-module" data-server-ts="<?php echo esc_attr((string)$server_ts); ?>" data-wp-ts="<?php echo esc_attr((string)$wp_ts); ?>">
            <div class="sd-time-grid">
                <!-- Server Column -->
                <div class="sd-time-col">
                    <span class="sd-label"><span class="dashicons dashicons-admin-site"></span> <?php _e('Server', 'system-deck'); ?></span>
                    <span class="sd-val" data-role="server-time">--:--:--</span>
                    <div class="sd-sub"><?php echo esc_html($tz_server); ?></div>
                </div>

                <!-- WP Column -->
                <div class="sd-time-col" style="text-align:center">
                    <span class="sd-label"><span class="dashicons dashicons-wordpress"></span> <?php _e('WP Local', 'system-deck'); ?></span>
                    <span class="sd-val" data-role="wp-time">--:--:--</span>
                    <div class="sd-sub"><?php echo esc_html($tz_wp); ?></div>
                </div>

                <!-- Browser Column -->
                <div class="sd-time-col text-right">
                    <span class="sd-label"><span class="dashicons dashicons-laptop"></span> <?php _e('Browser', 'system-deck'); ?></span>
                    <span class="sd-val" data-role="user-time">--:--:--</span>
                    <div class="sd-sub"><?php _e('Local', 'system-deck'); ?></div>
                </div>
            </div>

            <div class="sd-drift-container">
                <div class="sd-drift-info">
                    <span><span class="dashicons dashicons-backup"></span> <?php _e('Uptime:', 'system-deck'); ?> <strong><?php echo esc_html($uptime); ?></strong></span>
                    <span id="sd-ping-val" class="sd-val" style="font-size:11px; font-weight:bold;">-- ms</span>
                </div>
                <button type="button" id="sd-time-ping" class="button button-small" style="margin-left:auto;"><?php _e('Ping', 'system-deck'); ?></button>
            </div>
        </div>
        <?php

        // Just-in-time enqueue if not already handled by registry logic perfectly (Registry logic might depend on how we implement it later)
        // For now, let's manually enqueue to be safe, though Init does it via hook.
        wp_enqueue_style('sd-time-monitor-css');
        wp_enqueue_script('sd-time-monitor-js');
    }
}
