<?php
/**
 * SystemDeck StorageEngine
 * Core controller for high-performance state persistence.
 * Status: PATCHED (Fixes Data Loss Bug)
 */

declare(strict_types=1);

namespace SystemDeck\Core;

if (!defined('ABSPATH')) {
    exit;
}

class StorageEngine
{
    private static array $write_buffer = [];

    /**
     * Save data based on intent and context.
     */
    public static function save(string $key, array $data, Context $context): bool
    {
        $intent = self::resolve_intent($key);

        if ($intent === 'state' || $intent === 'telemetry') {
            return self::buffer_write($key, $data, $context);
        }

        return self::persist($intent, $key, $data, $context);
    }

    /**
     * Get data based on intent and context.
     */
    public static function get(string $key, Context $context)
    {
        $intent = self::resolve_intent($key);

        $buffered = self::buffer_read($key, $context);
        if ($buffered !== null) {
            return $buffered;
        }

        if ($intent === 'items') {
            return self::fetch_items($key, $context);
        }

        return self::fetch_cascading($intent, $key, $context);
    }

    /**
     * Fetch items with strict filtering.
     * Fix: Ensures 'layout' only returns unpinned widgets, and 'pins' only returns pinned ones.
     */
    private static function fetch_items(string $key, Context $context): ?array
    {
        global $wpdb;
        $table_items = $wpdb->prefix . 'sd_items';
        $table_ws = $wpdb->prefix . 'sd_workspaces';

        $ws_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_ws WHERE slug = %s", $context->workspace_id));
        if (!$ws_id) {
            return null;
        }

        // Strict Filtering
        $where = '';
        if ($key === 'pins') {
            $where = ' AND is_pinned = 1';
        } elseif ($key === 'layout') {
            $where = ' AND is_pinned = 0';
        }

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT widget_id as id, settings, position, is_pinned FROM $table_items WHERE workspace_id = %d $where",
            $ws_id
        ), ARRAY_A);

        if (empty($results)) {
            return null;
        }

        return array_map(function($item) {
            $item['settings'] = json_decode($item['settings'], true) ?: [];
            $item['position'] = json_decode($item['position'], true) ?: [];
            $item['is_pinned'] = (bool) $item['is_pinned'];
            $item['type'] = 'widget';
            return $item;
        }, $results);
    }

    private static function resolve_intent(string $key): string
    {
        if (in_array($key, ['layout', 'items', 'pins'])) return 'items';
        if (str_starts_with($key, 'pref_')) return 'pref';
        if ($key === 'telemetry') return 'telemetry';
        return 'state';
    }

    private static function buffer_write(string $key, array $data, Context $context): bool
    {
        $sig = $context->get_signature() . '_' . $key;
        self::$write_buffer[$sig] = ['context' => $context, 'key' => $key, 'data' => $data];
        return set_transient('sd_buffer_' . $sig, $data, 30);
    }

    private static function buffer_read(string $key, Context $context)
    {
        $sig = $context->get_signature() . '_' . $key;
        if (isset(self::$write_buffer[$sig])) return self::$write_buffer[$sig]['data'];
        return get_transient('sd_buffer_' . $sig) ?: null;
    }

    private static function persist(string $intent, string $key, array $data, Context $context): bool
    {
        global $wpdb;

        if ($intent === 'pref') {
            return (bool) update_user_meta($context->user_id, 'sd_' . $key, $data);
        }

        // FIX: Pass $key to persist_items to enable targeted deletion
        if ($intent === 'items') {
            return self::persist_items($key, $data, $context);
        }

        if ($intent === 'state' || $intent === 'telemetry') {
            return self::persist_state($key, $data, $context);
        }

        return false;
    }

    private static function persist_state(string $key, array $data, Context $context): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'sd_context_state';

        if ($key === 'telemetry') {
            $column = 'telemetry_snapshot';
            $json_data = json_encode($data);
        } else {
            $column = 'active_overlay_state';
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT active_overlay_state FROM $table
                 WHERE user_id = %d AND workspace_id = %s AND context_type = %s AND context_id = %s AND viewport = %s",
                $context->user_id, $context->workspace_id, $context->context_type, $context->context_id, $context->viewport
            ));
            $state = $existing ? json_decode($existing, true) : [];
            $state[$key] = $data;
            $json_data = json_encode($state);
        }

        return (bool) $wpdb->query($wpdb->prepare(
            "INSERT INTO $table (user_id, workspace_id, context_type, context_id, viewport, $column)
             VALUES (%d, %s, %s, %s, %s, %s)
             ON DUPLICATE KEY UPDATE $column = VALUES($column), updated_at = CURRENT_TIMESTAMP",
            $context->user_id, $context->workspace_id, $context->context_type, $context->context_id, $context->viewport, $json_data
        ));
    }

    /**
     * Persist Items with Smart Deletion (Fixes Data Loss)
     */
    private static function persist_items(string $key, array $items, Context $context): bool
    {
        global $wpdb;
        $table_items = $wpdb->prefix . 'sd_items';
        $table_ws = $wpdb->prefix . 'sd_workspaces';

        $ws_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_ws WHERE slug = %s", $context->workspace_id));
        if (!$ws_id) {
            $wpdb->insert($table_ws, ['slug' => $context->workspace_id, 'title' => ucfirst($context->workspace_id), 'type' => 'dashboard']);
            $ws_id = $wpdb->insert_id;
        }

        // FIX: Only delete the items we are about to replace
        $delete_where = ['workspace_id' => $ws_id];
        $delete_format = ['%d'];

        if ($key === 'pins') {
            $delete_where['is_pinned'] = 1;
            $delete_format[] = '%d';
        } elseif ($key === 'layout') {
            $delete_where['is_pinned'] = 0;
            $delete_format[] = '%d';
        }
        // If key is 'items', we might mean "everything", so we leave filters off (dangerous but flexible)

        $wpdb->delete($table_items, $delete_where, $delete_format);

        // Insert new items
        foreach ($items as $item) {
            $wpdb->insert($table_items, [
                'workspace_id' => $ws_id,
                'widget_id'    => $item['id'] ?? '',
                'settings'     => json_encode($item['settings'] ?? []),
                'position'     => json_encode($item['position'] ?? []),
                'is_pinned'    => (int) ($item['is_pinned'] ?? ($key === 'pins' ? 1 : 0))
            ]);
        }

        return true;
    }

    private static function fetch_cascading(string $intent, string $key, Context $context)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'sd_context_state';
        $column = ($key === 'telemetry') ? 'telemetry_snapshot' : 'active_overlay_state';

        $hierarchy = [
            ['type' => 'post',     'id' => (string) get_the_ID()],
            ['type' => 'template', 'id' => self::detect_template_context()],
            ['type' => 'global',   'id' => 'global']
        ];

        foreach ($hierarchy as $layer) {
            $row = $wpdb->get_var($wpdb->prepare(
                "SELECT $column FROM $table
                 WHERE user_id = %d AND workspace_id = %s AND context_type = %s AND context_id = %s AND viewport IN (%s, 'all')
                 ORDER BY (viewport = %s) DESC, updated_at DESC LIMIT 1",
                $context->user_id, $context->workspace_id, $layer['type'], $layer['id'], $context->viewport, $context->viewport
            ));

            if ($row) return json_decode($row, true);
        }
        return null;
    }

    private static function detect_template_context(): string
    {
        if (is_front_page()) return 'front-page';
        if (is_single())     return 'single-' . get_post_type();
        if (is_page())       return 'page';
        if (is_archive())    return 'archive';
        if (is_search())     return 'search';
        return 'default-template';
    }

    public static function flush(): void
    {
        if (empty(self::$write_buffer)) return;
        foreach (self::$write_buffer as $sig => $buffer) {
            self::persist(self::resolve_intent($buffer['key']), $buffer['key'], $buffer['data'], $buffer['context']);
            delete_transient('sd_buffer_' . $sig);
        }
        self::$write_buffer = [];
    }

    public static function init(): void
    {
        add_action('shutdown', [self::class, 'flush']);
        $current_version = get_option('sd_db_version', '0');
        if (version_compare($current_version, SD_VERSION, '<')) {
            self::create_tables();
            update_option('sd_db_version', SD_VERSION);
        }
    }

    public static function create_tables(): void
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset_collate = $wpdb->get_charset_collate();

        $table_workspaces = $wpdb->prefix . 'sd_workspaces';
        $sql_workspaces = "CREATE TABLE $table_workspaces (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            slug varchar(100) NOT NULL,
            title varchar(255) NOT NULL,
            type varchar(50) NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug)
        ) $charset_collate;";

        $table_items = $wpdb->prefix . 'sd_items';
        $sql_items = "CREATE TABLE $table_items (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            workspace_id bigint(20) NOT NULL,
            widget_id varchar(100) NOT NULL,
            settings longtext NOT NULL,
            position longtext NOT NULL,
            is_pinned tinyint(1) DEFAULT 0 NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY ws_widget (workspace_id, widget_id),
            KEY workspace_id (workspace_id)
        ) $charset_collate;";

        $table_state = $wpdb->prefix . 'sd_context_state';
        $sql_state = "CREATE TABLE $table_state (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            workspace_id varchar(100) NOT NULL,
            context_type varchar(50) NOT NULL,
            context_id varchar(100) NOT NULL,
            viewport varchar(50) NOT NULL,
            active_overlay_state longtext,
            telemetry_snapshot longtext,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY context_signature (user_id, workspace_id, context_type, context_id, viewport)
        ) $charset_collate;";

        dbDelta($sql_workspaces);
        dbDelta($sql_items);
        dbDelta($sql_state);
    }
}
