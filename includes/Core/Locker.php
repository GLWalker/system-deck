<?php
/**
 * SystemDeck Locker (Persistence Layer)
 * Abstracted storage for large state packets.
 */

declare(strict_types=1);

namespace SystemDeck\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Locker
{
    /**
     * Save workspace state (Layout or Pins).
     */
    public static function save_state(int $user_id, string $workspace_id, string $key, array $data): bool
    {
        $meta_key = "sd_{$key}_{$workspace_id}";

        $existing = self::get_state($user_id, $workspace_id, $key);
        if ($existing === $data) {
            return true;
        }

        // FUTURE: Insert into custom `sd_locker` table here if it exists.
        return (bool) update_user_meta($user_id, $meta_key, $data);
    }

    /**
     * Get workspace state.
     */
    public static function get_state(int $user_id, string $workspace_id, string $key)
    {
        $meta_key = "sd_{$key}_{$workspace_id}";

        // FUTURE: Select from custom `sd_locker` table if meta is empty.
        return get_user_meta($user_id, $meta_key, true);
    }

    /**
     * Initialize the DB table (to be called on activation).
     */
    public static function create_table(): void
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sd_locker';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            workspace_id varchar(100) NOT NULL,
            state_key varchar(50) NOT NULL,
            state_data longtext NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_workspace (user_id, workspace_id),
            KEY state_unique (user_id, workspace_id, state_key)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
