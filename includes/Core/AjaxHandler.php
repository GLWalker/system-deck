<?php
/**
 * SystemDeck AJAX Handler (Ultimate Merger Edition)
 * Centralized entry point for all JS communication.
 *
 * Capabilities:
 * 1. Core Routing (Shell, Layouts, Workspaces)
 * 2. Generic Data Store (Headless Widgets)
 * 3. Telemetry Streaming (Live Data)
 * 4. System Utilities (Cache Management)
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
        // 1. Core System Actions
        $actions = [
            'load_shell',
            'save_layout',
            'save_pins',
            'get_manifest',
            'render_system_screen',
            'render_workspace',
            'render_widget',
            'create_workspace',
            'delete_workspace',
            'rename_workspace',
            'update_workspace_order',
            'get_workspaces',
            'refresh_menu',
            'save_proxy_selection',
            'toggle_pin',
            'get_notes',
            'get_all_notes',
            'save_note',
            'delete_note',
            'pin_note',
            'export_workspaces',
            'import_workspaces',
            'ping_latency',
            // --- ULTIMATE MERGER ADDITIONS ---
            'get_telemetry',      // Live Data Stream
            'save_widget_data',   // Generic Data Store (Write)
            'get_widget_data',    // Generic Data Store (Read)
            'clear_cache'         // System Utility
        ];

        foreach ($actions as $action) {
            add_action("wp_ajax_sd_{$action}", [self::class, "handle_{$action}"]);
            // NOTE: We do not add nopriv hooks per new directive security policy.
        }

        // 2. Open Architecture (Hook for 3rd Party Widgets)
        do_action('sd_register_ajax_actions', self::class);
    }

    /**
     * Helper for 3rd party devs to register secure actions.
     */
    public static function register_external_action(string $action, callable $callback): void
    {
        add_action("wp_ajax_sd_{$action}", function() use ($callback) {
            self::verify_request(); // Enforce SystemDeck Security
            call_user_func($callback);
        });
    }

    /**
     * Centralized security & capability check.
     */
    private static function verify_request(): void
    {
        $action = $_POST['action'] ?? 'unknown';

        // 1. NONCE PROTECTION
        if (!check_ajax_referer('sd_load_shell', 'nonce', false)) {
            wp_send_json_error(['message' => 'Security validation failed'], 403);
        }

        // 2. CAPABILITY PROTECTION
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
        }
    }

    /* ==========================================================================
       1. TELEMETRY STREAM (Live Data)
       ========================================================================== */

    /**
     * AJAX: Get Raw Telemetry (JSON)
     * Used by: Time Monitor, System Diagnostics (Live Mode)
     */
    public static function handle_get_telemetry(): void
    {
        self::verify_request();

        if (!class_exists('\SystemDeck\Core\Telemetry')) {
            wp_send_json_error('Telemetry module missing');
        }

        // Prefer Raw Metrics if available, fallback to HTML metrics
        if (method_exists('\SystemDeck\Core\Telemetry', 'get_raw_metrics')) {
            $metrics = \SystemDeck\Core\Telemetry::get_raw_metrics();
        } else {
            $metrics = \SystemDeck\Core\Telemetry::get_all_metrics();
        }

        // Optional: Filter keys to save bandwidth
        if (isset($_POST['keys']) && is_array($_POST['keys'])) {
            $filtered = [];
            foreach ($_POST['keys'] as $key) {
                $key = sanitize_text_field($key);
                if (isset($metrics[$key])) {
                    $filtered[$key] = $metrics[$key];
                }
            }
            wp_send_json_success($filtered);
        }

        wp_send_json_success($metrics);
    }

    /* ==========================================================================
       2. GENERIC DATA API (Widget State Store)
       ========================================================================== */

    /**
     * AJAX: Save Generic Widget Data
     * Allows any widget to save JSON state without custom PHP.
     */
    public static function handle_save_widget_data(): void
    {
        self::verify_request();

        $widget_id = sanitize_key($_POST['widget_id'] ?? '');
        $key = sanitize_text_field($_POST['key'] ?? '');
        $value = $_POST['value'] ?? null;

        if (!$widget_id || !$key) {
            wp_send_json_error(['message' => 'Missing widget_id or key']);
        }

        // Recursive sanitization wrapper
        $clean_value = self::sanitize_deep($value);
        $user_id = get_current_user_id();

        // Use StorageEngine with 'global' context for widget data
        $context = new Context($user_id, 'global');
        $data = StorageEngine::get("widget_data_{$widget_id}", $context) ?: [];
        if (!is_array($data)) $data = [];

        $data[$key] = $clean_value;
        StorageEngine::save("widget_data_{$widget_id}", $data, $context);

        wp_send_json_success(['message' => 'Data saved']);
    }

    /**
     * AJAX: Get Generic Widget Data
     */
    public static function handle_get_widget_data(): void
    {
        self::verify_request();

        $widget_id = sanitize_key($_POST['widget_id'] ?? '');
        $key = sanitize_text_field($_POST['key'] ?? '');

        if (!$widget_id) {
            wp_send_json_error(['message' => 'Missing widget_id']);
        }

        $user_id = get_current_user_id();
        $context = new Context($user_id, 'global');
        $data = StorageEngine::get("widget_data_{$widget_id}", $context) ?: [];

        if ($key) {
            $val = isset($data[$key]) ? $data[$key] : null;
            wp_send_json_success(['value' => $val]);
        } else {
            wp_send_json_success(['data' => $data]);
        }
    }

    private static function sanitize_deep($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::sanitize_deep($value);
            }
            return $data;
        }
        return is_scalar($data) ? sanitize_text_field((string)$data) : '';
    }

    /* ==========================================================================
       3. SYSTEM UTILITIES
       ========================================================================== */

    /**
     * AJAX: Clear System Caches
     */
    public static function handle_clear_cache(): void
    {
        self::verify_request();

        $type = sanitize_text_field($_POST['type'] ?? 'all');
        $cleared = [];

        // 1. Object Cache
        if ($type === 'all' || $type === 'object') {
            wp_cache_flush();
            $cleared[] = 'Object Cache';
        }

        // 2. Transients (User Specific & System)
        if ($type === 'all' || $type === 'transients') {
            global $wpdb;
            $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_sd_%' OR option_name LIKE '_transient_timeout_sd_%'");
            $cleared[] = 'SystemDeck Transients';
        }

        // 3. CSS/Asset Cache
        if ($type === 'all' || $type === 'css') {
            delete_transient('sd_dynamic_css_cache');
            $cleared[] = 'CSS Cache';
        }

        wp_send_json_success([
            'message' => 'Cleared: ' . implode(', ', $cleared)
        ]);
    }

    /* ==========================================================================
       4. CORE HANDLERS (Standard SystemDeck)
       ========================================================================== */

    public static function handle_load_shell(): void
    {
        self::verify_request();
        if (class_exists('\SystemDeck\Modules\Renderer')) {
            \SystemDeck\Modules\Renderer::ajax_load_shell();
        }
    }

    public static function handle_save_layout(): void
    {
        self::verify_request();
        $workspace_id = sanitize_key($_POST['workspaceId'] ?? 'default');
        $layout = isset($_POST['layout']) ? json_decode(stripslashes($_POST['layout']), true) : [];
        $user_id = (int)get_current_user_id();

        $context = new Context($user_id, $workspace_id);
        StorageEngine::save('layout', $layout, $context);

        // Sync summary
        $workspaces = get_user_meta($user_id, 'sd_workspaces', true) ?: [];
        $found_key = isset($workspaces[$workspace_id]) ? $workspace_id : null;

        if (!$found_key) {
             foreach ($workspaces as $key => $ws) {
                 if (is_array($ws) && isset($ws['id']) && $ws['id'] === $workspace_id) {
                     $found_key = $key; break;
                 }
             }
        }

        if ($found_key) {
            $widget_ids = [];
            foreach ($layout as $item) {
                if (($item['type'] ?? '') === 'widget') $widget_ids[] = $item['id'];
            }
            $workspaces[$found_key]['widgets'] = $widget_ids;
            update_user_meta($user_id, 'sd_workspaces', $workspaces);
        }

        wp_send_json_success(['message' => 'Layout saved']);
    }

    public static function handle_save_pins(): void
    {
        self::verify_request();
        $workspace_id = sanitize_key($_POST['workspaceId'] ?? 'default');
        $pins = isset($_POST['pins']) ? json_decode(stripslashes($_POST['pins']), true) : [];

        $context = new Context((int)get_current_user_id(), $workspace_id);
        StorageEngine::save('pins', $pins, $context);

        wp_send_json_success(['message' => 'Pins saved']);
    }

    public static function handle_get_manifest(): void
    {
        self::verify_request();
        $workspace_id = sanitize_key($_POST['workspaceId'] ?? 'default');
        $registry = Registry::instance();
        $manifest = $registry->hydrate_manifest($workspace_id);
        wp_send_json_success($manifest);
    }

    public static function handle_render_system_screen(): void
    {
        self::verify_request();
        \SystemDeck\Modules\SystemScreen::render();
    }

    public static function handle_render_workspace(): void
    {
        self::verify_request();
        $name = sanitize_text_field($_POST['name'] ?? 'Default');
        ob_start();
        \SystemDeck\Modules\WorkspaceRenderer::render($name);
        $html = ob_get_clean();

        // Get the real title for the response
        $registry = \SystemDeck\Core\Registry::instance();
        $ws = $registry->get_workspace($name);
        $display_name = $ws ? $ws['title'] : $name;

        wp_send_json_success(['html' => $html, 'name' => $display_name]);
    }

    public static function handle_create_workspace(): void
    {
        self::verify_request();
        $name = sanitize_text_field($_POST['name'] ?? '');
        if (!$name) wp_send_json_error('Invalid Name');

        $user_id = get_current_user_id();
        $workspaces = get_user_meta($user_id, 'sd_workspaces', true);
        if (empty($workspaces) || !is_array($workspaces)) $workspaces = [];

        $id = 'ws_' . uniqid();
        $max_order = 0;
        foreach ($workspaces as $ws) {
            if (is_array($ws) && isset($ws['order']) && $ws['order'] > $max_order) $max_order = $ws['order'];
        }

        $workspaces[$id] = [
            'id' => $id, 'name' => $name, 'widgets' => [],
            'created' => current_time('mysql'), 'order' => $max_order + 1
        ];

        // Handle Pre-Populated Layout
        $layout_json = $_POST['layout'] ?? '';
        if ($layout_json) {
            $layout = json_decode(stripslashes($layout_json), true);
            if (is_array($layout)) {
                // Save layout state
                $context = new Context($user_id, sanitize_title($id));
                StorageEngine::save('layout', $layout, $context);
                // Extract widget IDs for summary
                $widget_ids = [];
                foreach ($layout as $item) {
                     if (($item['type'] ?? '') === 'widget') $widget_ids[] = $item['id'];
                }
                $workspaces[$id]['widgets'] = $widget_ids;
            }
        }

        update_user_meta($user_id, 'sd_workspaces', $workspaces);

        ob_start();
        \SystemDeck\Modules\SystemScreen::render_workspace_card($workspaces[$id]);
        $html = ob_get_clean();

        wp_send_json_success(['message' => 'Created', 'workspace' => $workspaces[$id], 'html' => $html]);
    }

    public static function handle_delete_workspace(): void
    {
        self::verify_request();
        $workspace_id = sanitize_text_field($_POST['workspace_id'] ?? '');
        if ($workspace_id === 'default') wp_send_json_error('Cannot delete default');

        $user_id = get_current_user_id();
        $workspaces = get_user_meta($user_id, 'sd_workspaces', true) ?: [];

        if (isset($workspaces[$workspace_id])) {
            unset($workspaces[$workspace_id]);
            update_user_meta($user_id, 'sd_workspaces', $workspaces);
            wp_send_json_success();
        }
        wp_send_json_error('Not found');
    }

    public static function handle_get_workspaces(): void
    {
        self::verify_request();
        $user_id = get_current_user_id();
        $workspaces_data = get_user_meta($user_id, 'sd_workspaces', true) ?: ['Default' => []];
        $workspaces = [];

        uasort($workspaces_data, function($a, $b) {
            return ($a['order'] ?? 0) - ($b['order'] ?? 0);
        });

        foreach ($workspaces_data as $key => $data) {
            $is_new = is_array($data) && isset($data['name']);
            $workspaces[] = [
                'id' => $is_new ? ($data['id'] ?? $key) : $key,
                'name' => $is_new ? $data['name'] : $key,
                'slug' => sanitize_title($is_new ? $data['name'] : $key),
                'order' => $data['order'] ?? 0
            ];
        }
        wp_send_json_success(['workspaces' => $workspaces]);
    }

    public static function handle_refresh_menu(): void
    {
        self::verify_request();
        ob_start();
        (new MenuEngine())->render();
        wp_send_json_success(['html' => ob_get_clean()]);
    }

    public static function handle_save_proxy_selection(): void
    {
        self::verify_request();
        $widgets = isset($_POST['widgets']) ? (array)$_POST['widgets'] : [];
        update_user_meta(get_current_user_id(), 'sd_active_proxy_widgets', array_map('sanitize_text_field', $widgets));
        wp_send_json_success();
    }

    public static function handle_toggle_pin(): void
    {
        self::verify_request();
        $pin_data = json_decode(stripslashes($_POST['pin_data'] ?? ''), true);
        if (!$pin_data || !isset($pin_data['id'])) wp_send_json_error(['message' => 'Invalid JSON']);

        $user_id = get_current_user_id();
        $workspace = sanitize_title($_POST['workspace'] ?? 'Default');
        $context = new Context($user_id, $workspace);

        $pins = StorageEngine::get('pins', $context) ?: [];
        $id = $pin_data['id'];
        $action = 'added';

        if (isset($pins[$id])) {
            unset($pins[$id]);
            $action = 'removed';
        } else {
            $pins[$id] = $pin_data;
        }

        StorageEngine::save('pins', $pins, $context);
        wp_send_json_success(['action' => $action, 'pins' => array_values($pins)]);
    }

    public static function handle_render_widget(): void
    {
        self::verify_request();
        $widget_id = sanitize_text_field($_POST['widget'] ?? '');
        $widget = Registry::instance()->get_widget($widget_id);

        if (!$widget) wp_send_json_error('Widget not found');

        wp_send_json_success([
            'html' => $widget['content'] ?? '',
            'content' => $widget['content'] ?? '',
            'title' => $widget['title'] ?? '',
        ]);
    }



    // Notes Handlers
    public static function handle_get_notes(): void { self::verify_request(); (new \SystemDeck\Widgets\Notes())->ajax_get_notes(); }
    public static function handle_get_all_notes(): void { self::verify_request(); (new \SystemDeck\Widgets\Notes())->ajax_get_all_notes(); }
    public static function handle_save_note(): void { self::verify_request(); (new \SystemDeck\Widgets\Notes())->ajax_save_note(); }
    public static function handle_delete_note(): void { self::verify_request(); (new \SystemDeck\Widgets\Notes())->ajax_delete_note(); }
    public static function handle_pin_note(): void { self::verify_request(); (new \SystemDeck\Widgets\Notes())->ajax_pin_note(); }



    public static function handle_rename_workspace(): void {
        self::verify_request();
        $id = sanitize_text_field($_POST['workspace_id'] ?? '');
        $name = sanitize_text_field($_POST['name'] ?? '');
        $user_id = get_current_user_id();
        $ws = get_user_meta($user_id, 'sd_workspaces', true) ?: [];
        if(isset($ws[$id])) {
            $ws[$id]['name'] = $name;
            update_user_meta($user_id, 'sd_workspaces', $ws);
            wp_send_json_success();
        }
        wp_send_json_error();
    }

    public static function handle_update_workspace_order(): void {
        self::verify_request();
        $order = $_POST['order'] ?? [];
        $user_id = get_current_user_id();
        $ws = get_user_meta($user_id, 'sd_workspaces', true) ?: [];
        foreach($order as $idx => $id) {
            if(isset($ws[$id])) $ws[$id]['order'] = $idx;
        }
        update_user_meta($user_id, 'sd_workspaces', $ws);
        wp_send_json_success();
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
            $context = new Context($user_id, $slug);

            // Layout
            $layout = StorageEngine::get('layout', $context);
            if ($layout) {
                $deep_data[$slug]['layout'] = $layout;
            }

            // Pins
            $pins = StorageEngine::get('pins', $context);
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
                    $context = new Context((int)$user_id, $slug);

                    if ($config) {
                        if (!empty($config['layout'])) {
                            StorageEngine::save('layout', $config['layout'], $context);
                        }
                        if (!empty($config['pins'])) {
                            StorageEngine::save('pins', $config['pins'], $context);
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

    public static function handle_ping_latency(): void {
        self::verify_request();
        wp_send_json_success(['ts' => microtime(true)]);
    }
}
