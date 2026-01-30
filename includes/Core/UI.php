<?php
/**
 * SystemDeck UI Components
 * Standardized reusable UI elements for widgets and the shell.
 */
declare(strict_types=1);

namespace SystemDeck\Core;

if (!defined('ABSPATH')) { exit; }

class UI
{
    /**
     * Render a Standard Widget Pin Item (Row)
     * Matches the "Gold Standard" markup for consistency.
     *
     * @param string $key        Unique ID for the item (within the widget context).
     * @param string $label      The user-facing label/title.
     * @param string $value      (Optional) Value to display on the right. Can contain HTML.
     * @param string $icon       (Optional) Dashicon class (e.g., 'dashicons-heart') or raw SVG string.
     * @param array  $pin_data   (Optional) Custom data for the pin JSON. If provided, must include 'id'.
     *                           If not provided, a default metric-style blob is generated.
     * @param bool   $is_pinned  (Check state) Is this item currently pinned?
     */
    public static function render_pin_item(
        string $key,
        string $label,
        string $value = '',
        string $icon = '',
        array $pin_data = [],
        bool $is_pinned = false
    ): void {
        // 1. Prepare Classes & ID
        // 'sd-pin-item' is the core class for the row layout
        $item_classes = ['sd-pin-item'];
        if ($is_pinned) {
            $item_classes[] = 'pinned';
        }
        $class_str = esc_attr(implode(' ', $item_classes));

        // 2. Prepare Icon HTML
        $icon_html = '';
        if (!empty($icon)) {
            if (str_starts_with(trim($icon), '<svg')) {
                // Render custom SVG (Sanitization assumed by caller or rigid svg logic needed?)
                // For now, trusting internal calls.
                $icon_html = '<span class="sd-stat-icon custom-svg">' . $icon . '</span>';
            } else {
                // Standard Dashicon
                $icon_html = '<span class="dashicons ' . esc_attr($icon) . ' sd-stat-icon"></span>';
            }
        }

        // 3. Prepare Pin Data
        if (empty($pin_data['id'])) {
             // Fallback ID generation if not provided (Metric convention)
             $pin_data['id'] = 'metric_' . $key;
        }
        // Ensure minimal fields exist
        $pin_data = wp_parse_args($pin_data, [
            'type'  => 'metric',
            'label' => $label,
            'value' => strip_tags($value), // Strip HTML for the JSON blob ( Ribbon display is simpler)
            'icon'  => $icon
        ]);
        $json_attr = esc_attr(json_encode($pin_data));

        // 4. Determine Toggle Icon
        // Visual feedback based on user preference: Checkmark=Pinned, PinIcon=Unpinned
        // Or strictly consistent icon? Previous logic used 'dashicons-yes' for Pinned state.
        $toggle_icon = $is_pinned ? 'dashicons-yes' : 'dashicons-admin-post';

        // 5. Output HTML
        echo "<div class=\"{$class_str}\" data-key=\"" . esc_attr($key) . "\">";

        // Label Column
        echo '<span class="sd-stat-label">';
        echo $icon_html;
        echo esc_html($label);
        echo '</span>';

        // Value Column
        // Use wp_kses_post to allow bolding/coloring spans passed in $value
        echo '<span class="sd-stat-value">' . wp_kses_post($value) . '</span>';

        // Actions Column
        echo '<div class="sd-stat-actions">';
        echo "<span class=\"dashicons {$toggle_icon} sd-pin-toggle\" title=\"Toggle Pin\" data-pin-json=\"{$json_attr}\"></span>";
        echo '</div>';

        echo '</div>'; // End Item
    }

    /**

     *
     * @param array $pin_data  The data blob saved for this pin.
     *                         Expected keys: id, type, label, value, icon, w (width), h (height), html (optional custom content).
     */
    public static function render_pinned_item(array $pin_data): void
    {
        $id    = esc_attr($pin_data['id'] ?? uniqid('pin_'));
        $type  = esc_attr($pin_data['type'] ?? 'metric');
        $w     = (int)($pin_data['w'] ?? 1);
        $h     = (int)($pin_data['h'] ?? 1);
        $icon  = $pin_data['icon'] ?? '';
        $label = $pin_data['label'] ?? '';
        $value = $pin_data['value'] ?? '';
        $html  = $pin_data['html'] ?? '';

        // Grid Style logic
        // Use inline styles for spans to keep CSS simple, or classes if preferred.
        // User requested "variable sized items".
        $style = "grid-column: span {$w}; grid-row: span {$h};";

        echo "<div class=\"sd-pinned-item\" id=\"pin-{$id}\" data-id=\"{$id}\" style=\"{$style}\">";

        // Unpin Action (Always available)
        // Using dashicons-dismiss or dashicons-no-alt
        echo '<span class="dashicons dashicons-dismiss sd-pin-remove" title="Unpin" data-id="' . $id . '"></span>';

        if (!empty($html)) {
            // Custom Layout (e.g. Weather Card)
            // Sanitize? We generally trust saved user meta for admins, but wp_kses_post is safer.
            echo '<div class="sd-pin-content custom">';
            echo wp_kses_post($html);
            echo '</div>';
        } else {
            // Default Layout (Metric Card)
            echo '<div class="sd-pin-content default">';

            // Icon
            if (!empty($icon)) {
                if (str_starts_with(trim($icon), '<svg')) {
                    echo '<span class="sd-pin-icon custom">' . $icon . '</span>';
                } else {
                    echo '<span class="dashicons ' . esc_attr($icon) . ' sd-pin-icon"></span>';
                }
            }

            echo '<div class="sd-pin-meta">';
            echo '<span class="sd-pin-label">' . esc_html($label) . '</span>';
            echo '<span class="sd-pin-value">' . wp_kses_post($value) . '</span>';
            echo '</div>';

            echo '</div>';
        }

        echo '</div>';
    }
}
