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
        if (!current_user_can('manage_options')) return;

        // Only run on frontend, not in admin, not in AJAX unless specified
        if (!is_admin()) {
            add_filter('render_block', [self::class, 'inject_metadata'], 20, 2);
        }
    }

    public static function inject_metadata(string $content, array $block): string
    {
        if (empty($content) || empty($block['blockName'])) return $content;

        if (class_exists('WP_HTML_Tag_Processor')) {
            $tags = new \WP_HTML_Tag_Processor($content);
            if ($tags->next_tag()) {
                $tags->set_attribute('data-sd-block', esc_attr($block['blockName']));

                // Add specific class for easy targeting
                $tags->add_class('sd-inspectable');

                return $tags->get_updated_html();
            }
        }

        return $content;
    }
}
