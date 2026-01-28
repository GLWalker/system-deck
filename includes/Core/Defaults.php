<?php
/**
 * Defaults Configuration
 * Defines the starting state for new workspaces and layouts.
 */
declare(strict_types=1);

namespace SystemDeck\Core;

if (!defined('ABSPATH')) { exit; }

class Defaults
{
    /**
     * Get the default widget layout for a new workspace.
     */
    public static function get_default_layout(): array
    {
        return [
            // Row 0: Full width - System Diagnostics
            0 => [
                'type' => 'full',
                'widgets' => ['sd_widget_diagnostics']
            ],
            // Row 1: Split - Notes (Left) & Test (Right)
            1 => [
                'type' => 'split',
                'widgets_left' => ['sd_widget_notes'],
                'widgets_right' => ['sd_widget_test']
            ],
            // Row 2: Split - Test Widget A (Left) & Test Widget B (Right)
            2 => [
                'type' => 'split',
                'widgets_left' => ['sd_widget_test_a'],
                'widgets_right' => ['sd_widget_test_b']
            ]
        ];
    }
}

// Legacy hook-based registration for backward compatibility
add_action('system_deck_init', function() {

    // 1. Register Default "System" Workspace
    sd_register_workspace('system_main', [
        'label' => 'System Overview',
        'icon' => 'dashicons-dashboard',
        'layout' => ['full', 'split', 'full'],
        'context' => 'admin',
    ]);

    // 2. Register a "Welcome" Widget
    sd_register_widget('sd_welcome', [
        'title' => 'Welcome to SystemDeck',
        'context' => 'normal',
        'priority' => 'high',
        'callback' => function() {
            echo '<div class="sd-welcome-panel">';
            echo '<h2>SystemDeck Ready</h2>';
            echo '<p>Your workspace is initialized. Extensions can now register tools here.</p>';
            echo '</div>';
        }
    ]);

    // 3. Register a "Health Status" Widget (Using our Bridge)
    sd_register_widget('sd_health_status', [
        'title' => 'Site Health',
        'context' => 'side',
        'callback' => function() {
            try {
                $status = \SystemDeck\Widgets\HealthBridge::get_status();

                // Validate status is a non-empty string
                if (empty($status) || !is_string($status)) {
                    error_log('[SystemDeck] Health widget: Invalid status returned: ' . var_export($status, true));
                    $status = 'unknown';
                }

                // Determine color based on status
                $colors = [
                    'good' => 'var(--wp--preset--color--vivid-green-cyan)',
                    'critical' => 'var(--wp--preset--color--vivid-red)',
                    'recommended' => 'var(--wp--preset--color--luminous-vivid-amber)',
                ];
                $color = $colors[$status] ?? 'var(--wp--preset--color--cyan-bluish-gray)';

                echo '<div style="text-align:center; padding: 20px;">';
                echo '<span class="dashicons dashicons-heart" style="font-size: 48px; width: 48px; height: 48px; color: ' . esc_attr($color) . ';"></span>';
                echo '<p>Status: <strong>' . esc_html(ucfirst($status)) . '</strong></p>';
                echo '</div>';

            } catch (\Throwable $e) {
                error_log('[SystemDeck] Health widget error: ' . $e->getMessage());
                echo '<div class="notice notice-warning"><p>' . esc_html__('Health status unavailable.', 'system-deck') . '</p></div>';
            }
        }
    ]);

});
