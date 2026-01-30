<?php
/**
 * Pin Manager Module
 * Handles saving and retrieving pinned items.
 */
declare(strict_types=1);

namespace SystemDeck\Modules;

if (!defined('ABSPATH')) { exit; }

use SystemDeck\Core\StorageEngine;
use SystemDeck\Core\Context;

class PinManager
{
    public static function init(): void
    {
        // Handled by AjaxHandler
    }

    public static function ajax_toggle_pin(): void
    {
        check_ajax_referer('sd_load_shell', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        $pin_raw = $_POST['pin_data'] ?? '';
        if (!$pin_raw) wp_send_json_error(['message' => 'No data']);

        // Decode payload
        $pin_data = json_decode(stripslashes($pin_raw), true);
        if (!$pin_data || !isset($pin_data['id'])) wp_send_json_error(['message' => 'Invalid JSON']);

        $user_id = get_current_user_id();
        $workspace = sanitize_text_field($_POST['workspace'] ?? 'Default');
        $workspace_slug = sanitize_title($workspace);
        $context = new Context((int)$user_id, $workspace_slug);

        // Get current pins
        $pins = StorageEngine::get('pins', $context) ?: [];

        // Toggle Logic (Add or Remove)
        $id = $pin_data['id'];
        $action = 'added';

        if (isset($pins[$id])) {
            unset($pins[$id]);
            $action = 'removed';

            // Also remove from layout state to ensure it stays unpinned
            $layout = StorageEngine::get('layout', $context) ?: [];
            if (is_array($layout)) {
                $layout = array_values(array_filter($layout, fn($item) => $item['id'] !== $id));
                StorageEngine::save('layout', $layout, $context);
            }
        } else {
            $pins[$id] = $pin_data;
        }

        StorageEngine::save('pins', $pins, $context);

        wp_send_json_success([
            'action' => $action,
            'pins'   => array_values($pins) // Return indexed array for React
        ]);
    }

    public static function ajax_save_layout(): void
    {
        check_ajax_referer('sd_load_shell', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        $layout_raw = $_POST['layout'] ?? '';
        if (!$layout_raw) wp_send_json_error(['message' => 'No layout data']);

        // Decode layout
        $layout = json_decode(stripslashes($layout_raw), true);
        if (!is_array($layout)) wp_send_json_error(['message' => 'Invalid layout data']);

        $user_id = get_current_user_id();
        $workspace = sanitize_text_field($_POST['workspace'] ?? 'Default');
        $workspace_slug = sanitize_title($workspace);
        $context = new Context((int)$user_id, $workspace_slug);

        // Save layout to StorageEngine
        error_log("[SD DEBUG] ajax_save_layout - ID: $user_id, Workspace: $workspace_slug, Items: " . count($layout));
        $saved = StorageEngine::save('layout', $layout, $context);
        error_log("[SD DEBUG] ajax_save_layout - Save Success: " . ($saved ? 'YES' : 'NO'));

        wp_send_json_success([
            'message' => 'Layout saved',
            'layout' => $layout
        ]);
    }
}
