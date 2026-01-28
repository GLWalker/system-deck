<?php
/**
 * MenuEngine.php
 * Generates the SystemDeck admin menu structure.
 */
declare(strict_types=1);

namespace SystemDeck\Core;

if (!defined('ABSPATH')) { exit; }

class MenuEngine
{
    private $menu_items = [];

    public function __construct()
    {
        $this->build_menu_structure();
    }

    private function build_menu_structure(): void
    {
        $items = [];

        // 1. IMMUTABLE SYSTEM LINK (The "Admin Page" Trigger)
        $items[] = [
            'id'       => 'sd-menu-system',
            'title'    => __('System', 'system-deck'),
            'icon'     => 'dashicons-networking', // Network icon for the "Hub"
            'href'     => '#system', // Triggers the Config Screen
            'current'  => false, // State handled by JS
            'submenu'  => []
        ];

        // 2. WORKSPACE LINKS (Dynamic)
        // Fetched from UserMeta (replicating Admin Drawer logic)
        // 2. WORKSPACE LINKS (Dynamic)
        // Fetched from UserMeta (replicating Admin Drawer logic)
        $workspaces = get_user_meta(get_current_user_id(), 'sd_workspaces', true) ?: ['Default' => []];
        $workspace_subs = [];

        // Sort by order
        uasort($workspaces, function($a, $b) {
            $order_a = is_array($a) ? ($a['order'] ?? 0) : 0;
            $order_b = is_array($b) ? ($b['order'] ?? 0) : 0;
            return $order_a - $order_b;
        });

        foreach ($workspaces as $key => $data) {
            // Support legacy (name as key) vs New (ID as key)
            $is_new_format = is_array($data) && isset($data['name']);
            $title = $is_new_format ? $data['name'] : $key;
            $id = $is_new_format ? ($data['id'] ?? $key) : $key;
            $slug = sanitize_title($title); // Keep using title-slug for URL link for Aesthetics?
            // Or should we use ID for URL hash? #workspace-ws_123 vs #workspace-donkeyk
            // Using ID is safer for uniqueness.
            $id_slug = sanitize_title($id);

            $workspace_subs[] = [
                'title'   => $title,
                'href'    => '#workspace-' . $id_slug,
                'current' => false,
                'data'    => ['workspace' => $id, 'name' => $title] // Pass Name for UI
            ];
        }

        // Add the group
        $items[] = [
            'id'       => 'sd-menu-workspaces',
            'title'    => __('Workspaces', 'system-deck'),
            'icon'     => 'dashicons-archive',
            'href'     => '#',
            'current'  => false,
            'submenu'  => $workspace_subs
        ];



        // 4. ALLOW EXTERNAL ITEMS via Filter
        $this->menu_items = apply_filters('sd_menu_items', $items);
    }

    public function render(): void
    {
        echo '<div id="sd-menuwrap"><ul id="sd-menu">';

        foreach ($this->menu_items as $item) {
            $this->render_item($item);
        }

        $this->render_collapse_button();

        echo '</ul></div>';
    }

    private function render_item(array $item): void
    {
        $has_submenu = !empty($item['submenu']);
        $id_attr = isset($item['id']) ? ' id="' . esc_attr($item['id']) . '"' : '';
        $li_class = $has_submenu ? 'menu-top wp-has-submenu' : 'menu-top';

        echo '<li class="' . $li_class . '"' . $id_attr . '>';

        // Link Attributes
        $href = esc_attr($item['href']);
        $data_attr = '';
        if (isset($item['data']) && is_array($item['data'])) {
            foreach ($item['data'] as $k => $v) {
                $data_attr .= ' data-' . esc_attr($k) . '="' . esc_attr($v) . '"';
            }
        }

        echo '<a href="' . $href . '" class="menu-top"' . $data_attr . '>';
        echo '<div class="wp-menu-image dashicons-before ' . esc_attr($item['icon']) . '"><br></div>';
        echo '<div class="wp-menu-name">' . esc_html($item['title']) . '</div>';
        echo '</a>';

        if ($has_submenu) {
            echo '<ul class="wp-submenu wp-submenu-wrap">';
            echo '<li class="wp-submenu-head">' . esc_html($item['title']) . '</li>';
            foreach ($item['submenu'] as $sub) {
                $sub_href = esc_attr($sub['href']);
                // Sub-item data attrs
                $sub_data = '';
                if (isset($sub['data']) && is_array($sub['data'])) {
                    foreach ($sub['data'] as $k => $v) {
                        $sub_data .= ' data-' . esc_attr($k) . '="' . esc_attr($v) . '"';
                    }
                }
                echo '<li><a href="' . $sub_href . '"' . $sub_data . '>' . esc_html($sub['title']) . '</a></li>';
            }
            echo '</ul>';
        }

        echo '</li>';
    }

    private function render_collapse_button(): void
    {
        echo '<li id="sd-collapse-menu"><button type="button" id="sd-collapse-button"><span class="collapse-button-icon"></span><span class="collapse-button-label">' . __('Collapse Menu', 'system-deck') . '</span></button></li>';
    }
}
