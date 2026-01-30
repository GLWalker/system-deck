<?php
/**
 * SystemDeck Harvester
 * Specialized tool for extracting and caching structural metrics (theme.json).
 */

declare(strict_types=1);

namespace SystemDeck\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Harvester
{
    /**
     * Run the harvest operation.
     * Extracts theme.json settings and stores them in the Context State.
     */
    public static function harvest(Context $context): array
    {
        $metrics = [
            'theme'     => get_stylesheet(),
            'colors'    => self::get_palette(),
            'typography' => self::get_typography(),
            'spacing'   => self::get_spacing(),
            'timestamp' => time()
        ];

        // Store in the StorageEngine as a telemetry snapshot
        StorageEngine::save('telemetry', $metrics, $context);

        return $metrics;
    }

    /**
     * Extract the color palette from wp_get_global_settings.
     */
    private static function get_palette(): array
    {
        if (!function_exists('wp_get_global_settings')) {
            return [];
        }

        $settings = wp_get_global_settings(['color', 'palette']);
        return $settings['theme'] ?? $settings['default'] ?? [];
    }

    /**
     * Extract typography settings.
     */
    private static function get_typography(): array
    {
        if (!function_exists('wp_get_global_settings')) {
            return [];
        }

        return wp_get_global_settings(['typography']) ?: [];
    }

    /**
     * Extract spacing settings.
     */
    private static function get_spacing(): array
    {
        if (!function_exists('wp_get_global_settings')) {
            return [];
        }

        return wp_get_global_settings(['spacing']) ?: [];
    }

    /**
     * Determine if a harvest is needed based on theme version or cache.
     */
    public static function needs_harvest(Context $context): bool
    {
        $last_harvest = StorageEngine::get('telemetry', $context);

        if (!$last_harvest) {
            return true;
        }

        // Re-harvest if the theme changed
        return ($last_harvest['theme'] ?? '') !== get_stylesheet();
    }
}
