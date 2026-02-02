<?php
/**
 * SystemDeck RetailSystem
 * The engine behind the Frontend Responsive Inspector & Floating Menu.
 */
declare(strict_types=1);

namespace SystemDeck\Modules;

if (!defined('ABSPATH')) { exit; }

class RetailSystem
{
    public static function init(): void
    {
        if (is_admin()) return;

        add_action('wp_footer', [self::class, 'render_floating_menu']);

        // Initialize Inspectors
        if (class_exists('SystemDeck\Modules\Inspectors\BlockInspector')) {
            \SystemDeck\Modules\Inspectors\BlockInspector::init();
        }
    }

    public static function render_floating_menu(): void
    {
        if (!current_user_can('manage_options')) return;
        if (isset($_GET['sd_preview'])) return; // Don't show in iframe

        ?>
        <div id="sd-retail-dock" class="sd-dock-controls detatched" style="position:fixed; bottom:20px; left:20px; z-index:999999;">
            <button type="button" class="sd-btn-icon" id="sd-retail-open" title="Open Retail System">
                <span class="dashicons dashicons-smartphone"></span>
            </button>
        </div>
        <?php
    }
}
