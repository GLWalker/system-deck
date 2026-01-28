<?php
/**
 * SystemDeck Health Bridge
 * Provides a clean interface to WordPress Site Health data.
 *
 * @package SystemDeck
 */

declare(strict_types=1);

namespace SystemDeck\Widgets;

if (!defined('ABSPATH')) {
    exit;
}

class HealthBridge
{
    /**
     * Get the current site health status.
     * Returns a simplified status for the UI (good, recommended, critical).
     */
    public static function get_status(): string
    {
        $site_health = get_transient('health-check-site-status-result');

        if (false === $site_health) {
            // If no data, assume good or trigger a background check later
            return 'good';
        }

        $data = json_decode($site_health, true);

        // Defensive: Ensure JSON parsed correctly
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            error_log('[SystemDeck] HealthBridge: Invalid JSON in health check data');
            return 'good'; // Fail gracefully
        }

        // Refined detection: check counts if explicit status is ambiguous
        $status      = $data['status'] ?? 'good';
        $critical    = (int) ($data['critical'] ?? 0);
        $recommended = (int) ($data['recommended'] ?? 0);

        if ($critical > 0) {
            return 'critical';
        }

        if ($recommended > 0) {
            return 'recommended';
        }

        return $status;
    }

    /**
     * Get detailed issues list.
     */
    public static function get_issues(): array
    {
        // This would wrap WP_Site_Health::get_tests() in a real implementation
        // For now, returning a placeholder structure
        return [
            'critical' => [],
            'recommended' => []
        ];
    }
}
