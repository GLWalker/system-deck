<?php
/**
 * SystemDeck AJAX Handler
 * Centralized entry point for all JS communication.
 *
 * @package SystemDeck
 */

declare(strict_types=1);

namespace SystemDeck\Core;

if (!defined('ABSPATH')) {
    exit;
}

class AjaxHandler
{
    /**
     * Initialize AJAX hooks.
     */
    public static function init(): void
    {
        $actions = [
            'load_shell', // <--- ADDED: Bridges the "Advanced" Frontend
            'save_layout',
            'save_pins',
            'get_manifest',
            'render_system_screen',
            'render_workspace',
            'render_widget', // <--- ADDED: Lazy-load widget content
            'create_workspace',
            'delete_workspace',
            'rename_workspace',
            'update_workspace_order',
            'get_workspaces',
            'refresh_menu',
            'save_proxy_selection',
            'toggle_pin',
            'get_notes',
            'save_note',
            'delete_note',
            'pin_note',
            'export_workspaces',
            'import_workspaces',
        ];

        foreach ($actions as $action) {
            add_action("wp_ajax_sd_{$action}", [self::class, "handle_{$action}"]);
            add_action("wp_ajax_nopriv_sd_{$action}", [self::class, "handle_{$action}"]);
        }
    }

    /**
     * Centralized security & capability check.
     */
    private static function verify_request(): void
    {
        $action = $_POST['action'] ?? 'unknown';

        // 1. NONCE PROTECTION
        if (!check_ajax_referer('sd_load_shell', 'nonce', false)) {
            error_log("SystemDeck: Security check failed for $action.");
            wp_send_json_error('Security validation failed', 403);
        }

        // 2. CAPABILITY PROTECTION
        if (!current_user_can('manage_options')) {
            error_log("SystemDeck: Unauthorized access attempt for $action.");
            wp_send_json_error('Unauthorized', 403);
        }
    }

    /**
     * AJAX: Load Shell HTML (Bridge for sd-deck.js)
     */
    public static function handle_load_shell(): void
    {
        self::verify_request();

        // Delegate to the Renderer to output the HTML
        if (class_exists('\SystemDeck\Modules\Renderer')) {
            \SystemDeck\Modules\Renderer::ajax_load_shell();
        }
    }

    /**
     * AJAX: Save Layout Configuration
     */
    public static function handle_save_layout(): void
    {
        self::verify_request();

        $workspace_id = sanitize_key($_POST['workspaceId'] ?? 'default');
        $layout = isset($_POST['layout']) ? json_decode(stripslashes($_POST['layout']), true) : [];
        $user_id = (int)get_current_user_id();

        // 1. Save detailed layout state
        Locker::save_state($user_id, $workspace_id, 'layout', $layout);

        // 2. Sync summary to main workspaces list (for Command Center counts)
        $workspaces = get_user_meta($user_id, 'sd_workspaces', true) ?: [];

        // Find workspace by ID (new) or Name (old) - Normalize ID first
        $found_key = null;
        if (isset($workspaces[$workspace_id])) {
            $found_key = $workspace_id;
        } else {
             // Search by ID field
             foreach ($workspaces as $key => $ws) {
                 if (is_array($ws) && isset($ws['id']) && $ws['id'] === $workspace_id) {
                     $found_key = $key;
                     break;
                 }
             }
        }

        if ($found_key) {
            // Extract widget IDs from layout for summary
            $widget_ids = [];
            foreach ($layout as $item) {
                if (($item['type'] ?? '') === 'widget') {
                    $widget_ids[] = $item['id'];
                }
            }

            $workspaces[$found_key]['widgets'] = $widget_ids;
            update_user_meta($user_id, 'sd_workspaces', $workspaces);
        }

        wp_send_json_success(['message' => 'Layout saved']);
    }

    /**
     * AJAX: Save Pinned Items
     */
    public static function handle_save_pins(): void
    {
        self::verify_request();

        $workspace_id = sanitize_key($_POST['workspaceId'] ?? 'default');
        $pins = isset($_POST['pins']) ? json_decode(stripslashes($_POST['pins']), true) : [];

        Locker::save_state((int)get_current_user_id(), $workspace_id, 'pins', $pins);

        wp_send_json_success(['message' => 'Pins saved']);
    }

    /**
     * AJAX: Get Workspace Manifest (Hydrated)
     */
    public static function handle_get_manifest(): void
    {
        self::verify_request();

        $workspace_id = sanitize_key($_POST['workspaceId'] ?? 'default');
        $registry = Registry::instance();
        $manifest = $registry->hydrate_manifest($workspace_id);

        if (empty($manifest)) {
            wp_send_json_error('Workspace not found', 404);
        }

        wp_send_json_success($manifest);
    }

    /**
     * AJAX: Render System Screen (The Hard Scan)
     */
    public static function handle_render_system_screen(): void
    {
        self::verify_request();
        \SystemDeck\Modules\SystemScreen::render();
    }

    /**
     * AJAX: Render Workspace HTML
     */
    public static function handle_render_workspace(): void
    {
        self::verify_request();
        $name = sanitize_text_field($_POST['name'] ?? 'Default');

        ob_start();
        \SystemDeck\Modules\WorkspaceRenderer::render($name);
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html, 'name' => $name]);
    }

    /**
     * AJAX: Create Workspace
     */
    public static function handle_create_workspace(): void
    {
        self::verify_request();
        $name = sanitize_text_field($_POST['name'] ?? '');
        if (!$name) wp_send_json_error('Invalid Name');

        $user_id = get_current_user_id();
        $workspaces = get_user_meta($user_id, 'sd_workspaces', true);

        // Normalize workspaces if empty or legacy format
        if (empty($workspaces) || !is_array($workspaces)) {
            $workspaces = [];
        }

        // Generate unique ID
        $id = 'ws_' . uniqid();

        // Get max order
        $max_order = 0;
        foreach ($workspaces as $ws) {
            if (is_array($ws) && isset($ws['order']) && $ws['order'] > $max_order) {
                $max_order = $ws['order'];
            }
        }

        // Create new workspace
        $workspaces[$id] = [
            'id' => $id,
            'name' => $name,
            'widgets' => [],
            'created' => current_time('mysql'),
            'order' => $max_order + 1
        ];

        update_user_meta($user_id, 'sd_workspaces', $workspaces);

        // Render card HTML
        ob_start();
        \SystemDeck\Modules\SystemScreen::render_workspace_card($workspaces[$id]);
        $html = ob_get_clean();

        wp_send_json_success([
            'message' => __('Workspace created successfully', 'system-deck'),
            'workspace' => $workspaces[$id],
            'html' => $html
        ]);
    }

    /**
     * AJAX: Delete Workspace
     */
    public static function handle_delete_workspace(): void
    {
        self::verify_request();
        $workspace_id = sanitize_text_field($_POST['workspace_id'] ?? '');

        if (empty($workspace_id)) {
            wp_send_json_error('Workspace ID required');
        }

        if ($workspace_id === 'default') {
             wp_send_json_error('Cannot delete default workspace');
        }

        $user_id = get_current_user_id();
        $workspaces = get_user_meta($user_id, 'sd_workspaces', true) ?: [];

        if (isset($workspaces[$workspace_id])) {
            unset($workspaces[$workspace_id]);
            update_user_meta($user_id, 'sd_workspaces', $workspaces);

            // Cleanup legacy meta if exists (optional but good practice)
            // $slug = sanitize_title($workspaces[$workspace_id]['name'] ?? '');
            // if ($slug) {
            //     delete_user_meta($user_id, 'sd_layout_' . $slug);
            //     delete_user_meta($user_id, 'sd_pins_' . $slug);
            // }

            wp_send_json_success();
        }
        wp_send_json_error('Workspace not found');
    }

    /**
     * AJAX: Get Workspaces List
     */
    public static function handle_get_workspaces(): void
    {
        self::verify_request();
        $user_id = get_current_user_id();
        $workspaces_data = get_user_meta($user_id, 'sd_workspaces', true) ?: ['Default' => []];

        $workspaces = [];

        // Robust sort by order
        uasort($workspaces_data, function($a, $b) {
            $order_a = is_array($a) ? ($a['order'] ?? 0) : 0;
            $order_b = is_array($b) ? ($b['order'] ?? 0) : 0;
            return $order_a - $order_b;
        });

        foreach ($workspaces_data as $key => $data) {
            $is_new_format = is_array($data) && isset($data['name']);
            $name = $is_new_format ? $data['name'] : $key;
            $id = $is_new_format ? ($data['id'] ?? $key) : $key;

            $workspaces[] = [
                'id' => $id,
                'name' => $name,
                'slug' => sanitize_title($name),
                'created' => $data['created'] ?? null,
                'order' => $data['order'] ?? 0
            ];
        }

        wp_send_json_success(['workspaces' => $workspaces]);
    }

    /**
     * AJAX: Refresh Sidebar Menu
     */
    public static function handle_refresh_menu(): void
    {
        self::verify_request();
        ob_start();
        $menu = new MenuEngine();
        $menu->render();
        $html = ob_get_clean();
        wp_send_json_success(['html' => $html]);
    }

    /**
     * AJAX: Save Proxy Discovery Selection
     */
    public static function handle_save_proxy_selection(): void
    {
        self::verify_request();
        $widgets = isset($_POST['widgets']) ? (array)$_POST['widgets'] : [];
        $clean = array_map('sanitize_text_field', $widgets);

        update_user_meta(get_current_user_id(), 'sd_active_proxy_widgets', $clean);
        wp_send_json_success();
    }

    /**
     * AJAX: Toggle Pin State
     */
    public static function handle_toggle_pin(): void
    {
        self::verify_request();

        $pin_raw = $_POST['pin_data'] ?? '';
        if (!$pin_raw) wp_send_json_error(['message' => 'No data']);

        $pin_data = json_decode(stripslashes($pin_raw), true);
        if (!$pin_data || !isset($pin_data['id'])) wp_send_json_error(['message' => 'Invalid JSON']);

        $user_id = (int)get_current_user_id();
        $workspace = sanitize_text_field($_POST['workspace'] ?? 'Default');
        $workspace_id = sanitize_title($workspace);

        $pins = Locker::get_state($user_id, $workspace_id, 'pins') ?: [];
        $id = $pin_data['id'];
        $action = 'added';

        if (isset($pins[$id])) {
            unset($pins[$id]);
            $action = 'removed';

            $layout = Locker::get_state($user_id, $workspace_id, 'layout') ?: [];
            if (is_array($layout)) {
                $layout = array_values(array_filter($layout, fn($item) => $item['id'] !== $id));
                Locker::save_state($user_id, $workspace_id, 'layout', $layout);
            }
        } else {
            $pins[$id] = $pin_data;
        }

        Locker::save_state($user_id, $workspace_id, 'pins', $pins);
        wp_send_json_success(['action' => $action, 'pins' => array_values($pins)]);
    }

    public static function handle_get_notes(): void
    {
        self::verify_request();
        (new \SystemDeck\Widgets\Notes())->ajax_get_notes();
    }

    public static function handle_save_note(): void
    {
        self::verify_request();
        (new \SystemDeck\Widgets\Notes())->ajax_save_note();
    }

    public static function handle_delete_note(): void
    {
        self::verify_request();
        (new \SystemDeck\Widgets\Notes())->ajax_delete_note();
    }

    public static function handle_pin_note(): void
    {
        self::verify_request();
        (new \SystemDeck\Widgets\Notes())->ajax_pin_note();
    }

    /**
     * AJAX: Render Widget Content (Lazy Loading)
     */
    public static function handle_render_widget(): void
    {
        self::verify_request();
        $widget_id = sanitize_text_field($_POST['widget'] ?? '');

        if (!$widget_id) {
            wp_send_json_error('Widget ID required');
        }

        $registry = Registry::instance();
        $widget = $registry->get_widget($widget_id);

        if (!$widget) {
            wp_send_json_error('Widget not found');
        }

        // Return the widget content
        wp_send_json_success([
            'html' => $widget['content'] ?? '',
            'content' => $widget['content'] ?? '',
            'title' => $widget['title'] ?? '',
        ]);
    }
    /**
     * AJAX: Rename Workspace
     */
    public static function handle_rename_workspace(): void
    {
        self::verify_request();

        $workspace_id = sanitize_text_field($_POST['workspace_id'] ?? '');
        $name = sanitize_text_field($_POST['name'] ?? '');

        if (empty($workspace_id) || empty($name)) {
            wp_send_json_error(['message' => __('Workspace ID and name are required', 'system-deck')]);
        }

        $user_id = get_current_user_id();
        $workspaces = get_user_meta($user_id, 'sd_workspaces', true) ?: [];

        if (!isset($workspaces[$workspace_id])) {
            wp_send_json_error(['message' => __('Workspace not found', 'system-deck')]);
        }

        $workspaces[$workspace_id]['name'] = $name;
        update_user_meta($user_id, 'sd_workspaces', $workspaces);

        wp_send_json_success(['message' => __('Workspace renamed successfully', 'system-deck')]);
    }

    /**
     * AJAX: Update Workspace Order
     */
    public static function handle_update_workspace_order(): void
    {
        self::verify_request();

        $order = $_POST['order'] ?? [];
        if (!is_array($order)) {
            wp_send_json_error(['message' => __('Invalid order data', 'system-deck')]);
        }

        $user_id = get_current_user_id();
        $workspaces = get_user_meta($user_id, 'sd_workspaces', true) ?: [];

        foreach ($order as $index => $workspace_id) {
            $workspace_id = sanitize_text_field($workspace_id);
            if (isset($workspaces[$workspace_id])) {
                $workspaces[$workspace_id]['order'] = $index;
            }
        }

        update_user_meta($user_id, 'sd_workspaces', $workspaces);

        wp_send_json_success(['message' => __('Workspace order updated', 'system-deck')]);
    }

    /**
     * AJAX: Export Workspaces (Deep Export)
     */
    public static function handle_export_workspaces(): void
    {
        check_ajax_referer('sd_export', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die(__('Insufficient permissions', 'system-deck'));
        }

        $user_id = get_current_user_id();
        $type = sanitize_text_field($_GET['type'] ?? 'all');

        // 1. Fetch Workspaces Meta
        $all_workspaces = get_user_meta($user_id, 'sd_workspaces', true) ?: [];
        $export_list = [];

        // Filter if single (currently defaults to all/default)
        if ($type === 'single') {
            // If single requested without ID, export default
             if (isset($all_workspaces['default'])) {
                 $export_list['default'] = $all_workspaces['default'];
             }
        } else {
            $export_list = $all_workspaces;
        }

        // 2. Hydrate Layouts & Pins
        $deep_data = [];
        foreach ($export_list as $key => $ws) {
            $id = $ws['id'] ?? $key;
            $slug = sanitize_title($id);

            // Layout
            $layout = \SystemDeck\Core\Locker::get_state($user_id, $slug, 'layout');
            if ($layout) {
                $deep_data[$slug]['layout'] = $layout;
            }

            // Pins
            $pins = \SystemDeck\Core\Locker::get_state($user_id, $slug, 'pins');
            if ($pins) {
                $deep_data[$slug]['pins'] = $pins;
            }
        }

        $package = [
            'version' => '1.1',
            'created' => current_time('mysql'),
            'type' => $type,
            'workspaces' => $export_list,
            'configurations' => $deep_data
        ];

        // Send Download
        $filename = 'systemdeck-' . $type . '-workspaces-' . date('Y-m-d') . '.json';

        header('Content-Description: File Transfer');
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . strlen(json_encode($package)));

        echo json_encode($package);
        exit;
    }

    /**
     * AJAX: Import Workspaces
     */
    public static function handle_import_workspaces(): void
    {
        self::verify_request();

        $data_json = $_POST['data'] ?? '';
        if (empty($data_json)) {
            wp_send_json_error(['message' => __('No data provided', 'system-deck')]);
        }

        try {
            $import_data = json_decode($data_json, true);

            if (!is_array($import_data) || !isset($import_data['workspaces'])) {
                wp_send_json_error(['message' => __('Invalid import data format', 'system-deck')]);
            }

            $user_id = get_current_user_id();
            $existing_workspaces = get_user_meta($user_id, 'sd_workspaces', true) ?: [];

            $imported_count = 0;
            $skipped_count = 0;

            foreach ($import_data['workspaces'] as $key => $workspace) {
                // Handle legacy Key-based name
                $id = $workspace['id'] ?? $key;

                if (isset($existing_workspaces[$id])) {
                    $skipped_count++;
                    continue; // Do not overwrite existing workspaces to prevent data loss
                }

                // Add to meta
                $existing_workspaces[$id] = $workspace;
                $imported_count++;

                // Restore Deep Config (Layouts & Pins)
                if (isset($import_data['configurations'])) {
                    $slug = sanitize_title($id);
                    $config = $import_data['configurations'][$slug] ?? null;

                    if ($config) {
                        if (!empty($config['layout'])) {
                            \SystemDeck\Core\Locker::save_state((int)$user_id, $slug, 'layout', $config['layout']);
                        }
                        if (!empty($config['pins'])) {
                            \SystemDeck\Core\Locker::save_state((int)$user_id, $slug, 'pins', $config['pins']);
                        }
                    }
                }
            }

            if ($imported_count > 0) {
                update_user_meta($user_id, 'sd_workspaces', $existing_workspaces);
            }

            $message = sprintf(
                __('Import complete: %d workspaces imported, %d skipped', 'system-deck'),
                $imported_count,
                $skipped_count
            );

            wp_send_json_success(['message' => $message]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => __('Import failed: ' . $e->getMessage(), 'system-deck')]);
        }
    }

}