<?php
/**
 * Telemetry Harvester
 * Pre-parses Theme.json to populate the Context State table.
 */
declare(strict_types=1);

namespace SystemDeck\Core;

if (!defined('ABSPATH')) { exit; }

class Harvester
{
    public static function init(): void {
        add_action('after_switch_theme', [self::class, 'invalidate_cache']);
    }

    public static function needs_harvest(Context $context): bool {
        $data = StorageEngine::get('telemetry', $context);
        return empty($data);
    }

    public static function harvest(Context $context): array {
        $theme_data = self::parse_theme_json();
        StorageEngine::save('telemetry', $theme_data, $context);
        return $theme_data;
    }

    private static function parse_theme_json(): array {
        if (!class_exists('WP_Theme_JSON_Resolver')) return [];

        $theme = \WP_Theme_JSON_Resolver::get_merged_data();
        $settings = $theme->get_settings();

        // Get Variations
        $variations = [];
        if (method_exists('WP_Theme_JSON_Resolver', 'get_style_variations')) {
            $raw_variations = \WP_Theme_JSON_Resolver::get_style_variations();
            foreach ($raw_variations as $v) {
                $variations[] = [
                    'title' => $v['title'] ?? 'Untitled',
                    'slug' => $v['slug'] ?? sanitize_title($v['title'] ?? '')
                ];
            }
        }

        return [
            'theme'      => get_stylesheet(),
            'palette'    => $settings['color']['palette']['theme'] ?? $settings['color']['palette']['default'] ?? [],
            'spacing'    => $settings['spacing']['spacingScale'] ?? [],
            'typography' => $settings['typography']['fontSizes']['theme'] ?? [],
            'layout'     => $settings['layout'] ?? [],
            'variations' => $variations,
            'harvested_at' => time()
        ];
    }

    public static function invalidate_cache(): void {
        // Handled by StorageEngine / Context logic naturally on next load
    }
}
