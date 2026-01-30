<?php
/**
 * Block Inspector Module
 * Injects identification data into blocks for the frontend scanner.
 */
declare(strict_types=1);

namespace SystemDeck\Modules\Inspectors;

if (!defined('ABSPATH')) { exit; }

class BlockInspector
{
    public static function init(): void
    {
        // Only run on frontend, not in admin, not in AJAX unless specified
        if (!is_admin()) {
            add_filter('render_block', [self::class, 'inject_metadata'], 20, 2);
        }
    }

    public static function inject_metadata(string $content, array $block): string
    {
        // Check capability here to avoid early usage of wp_get_current_user()
        if (!current_user_can('manage_options')) return $content;

        if (empty($content) || empty($block['blockName'])) return $content;

        // Use WP_HTML_Tag_Processor (WP 6.2+) for robust injection
        if (class_exists('WP_HTML_Tag_Processor')) {
            $tags = new \WP_HTML_Tag_Processor($content);
            if ($tags->next_tag()) {
                $tags->set_attribute('data-sd-block', esc_attr($block['blockName']));
                $tags->add_class('sd-inspectable');
                return $tags->get_updated_html();
            }
        }

        return $content;
    }
}
