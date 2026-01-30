<?php
/**
 * SystemDeck Harvester
 * Specialized tool for extracting and caching structural metrics (theme.json).
 * PHASE 3 FIX: Adds RGB normalization and Font Families.
 */
declare(strict_types=1);

namespace SystemDeck\Core;

use SystemDeck\Utils\Color;

if (!defined('ABSPATH')) { exit; }

class Harvester
{
    public static function init(): void {
        add_action('after_switch_theme', [self::class, 'invalidate_cache']);
    }

    /**
     * Run the harvest operation.
     */
    public static function harvest(Context $context): array
    {
        $theme_data = self::parse_theme_json();
        StorageEngine::save('telemetry', $theme_data, $context);
        return $theme_data;
    }

    private static function parse_theme_json(): array {
        if (!class_exists('WP_Theme_JSON_Resolver')) return [];

        $theme = \WP_Theme_JSON_Resolver::get_merged_data();
        $settings = $theme->get_settings();

        // 1. Process Palette (The RGB Fix)
        $palette = $settings['color']['palette']['theme'] ?? $settings['color']['palette']['default'] ?? [];
        foreach ($palette as &$color) {
            if (isset($color['color']) && class_exists('SystemDeck\Utils\Color')) {
                // Normalize to RGB string for frontend matching "rgb(r, g, b)"
                $c = new Color($color['color']);
                $color['rgb'] = $c->hex_to_rgb($color['color']);
            }
        }

        // 2. Process Typography (Added Font Families)
        $typography = [
            'fontSizes' => $settings['typography']['fontSizes']['theme'] ?? [],
            'fontFamilies' => $settings['typography']['fontFamilies']['theme'] ?? []
        ];

        // 3. Process Shadows (New)
        $shadows = $settings['shadow']['presets']['theme'] ?? $settings['shadow']['presets']['default'] ?? [];

        // 4. Process Variations
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
            'palette'    => $palette,
            'spacing'    => $settings['spacing']['spacingScale'] ?? [],
            'typography' => $typography,
            'shadows'    => $shadows,
            'layout'     => $settings['layout'] ?? [],
            'variations' => $variations,
            'harvested_at' => time()
        ];
    }

    public static function needs_harvest(Context $context): bool
    {
        $last_harvest = StorageEngine::get('telemetry', $context);
        if (!$last_harvest || empty($last_harvest)) return true;
        return ($last_harvest['theme'] ?? '') !== get_stylesheet();
    }

    public static function invalidate_cache(): void { }
}
