<?php
/**
 * SystemDeck Notes Module
 * A quick-access notepad widget for the workspace.
 *
 * @package SystemDeck
 */

declare(strict_types=1);

namespace SystemDeck\Widgets;

if (!defined('ABSPATH')) {
    exit;
}

use SystemDeck\Core\Registry;

class Notes
{
    private const CPT = 'sd_note';

    public static function init(): void
    {
        $self = new self();
        add_action('init', [$self, 'register_cpt']);

        // Register widget with the Registry
        Registry::register_widget('sd_widget_notes', [
            'id'       => 'sd_widget_notes',
            'title'    => __('Quick Notes', 'system-deck'),
            'callback' => [$self, 'render'],
            'context'  => 'normal',
            'icon'     => 'dashicons-edit-page'
        ]);

        // AJAX Handlers handled by AjaxHandler
    }

    public function register_cpt(): void
    {
        register_post_type(self::CPT, [
            'label'           => __('SystemDeck Note', 'system-deck'),
            'public'          => false,
            'show_ui'         => false,
            'capability_type' => 'post',
            'supports'        => ['title', 'editor', 'author'],
            'map_meta_cap'    => true,
            'can_export'      => true
        ]);
    }

    public function render($args = []): void
    {
        ?>
        <div class="sd-notes-wrapper" id="sd-notes-widget">
            <div class="sd-notes-toolbar">
                <div class="sd-notes-tools-left">
                    <label class="sd-checkbox-label" title="<?php esc_attr_e('New notes will include current URL', 'system-deck'); ?>">
                        <input type="checkbox" id="sd-note-capture" value="1">
                        <?php _e('Capture URL', 'system-deck'); ?>
                    </label>
                    <a href="#" id="sd-note-visit-link" target="_blank" class="button button-small" style="display:none;">
                        <span class="dashicons dashicons-external"></span> <?php _e('Visit', 'system-deck'); ?>
                    </a>
                </div>
                <button type="button" class="button button-small" id="sd-note-new"><?php _e('New Note', 'system-deck'); ?></button>
            </div>

            <div class="sd-note-form">
                <input type="hidden" id="sd-note-id" value="">
                <div class="sd-input-group">
                    <input type="text" id="sd-note-title" class="widefat" placeholder="<?php esc_attr_e('Title', 'system-deck'); ?>" autocomplete="off">
                </div>
                <div class="sd-input-group flex-grow">
                    <textarea id="sd-note-content" class="widefat" placeholder="<?php esc_attr_e('Type your note here...', 'system-deck'); ?>"></textarea>
                </div>
                <div class="sd-form-footer">
                    <button type="button" class="button-link-delete" id="sd-note-delete" style="display:none;"><?php _e('Delete', 'system-deck'); ?></button>
                    <div class="sd-save-wrapper">
                        <span class="sd-spinner"></span>
                        <button type="button" class="button button-primary" id="sd-note-save"><?php _e('Save Note', 'system-deck'); ?></button>
                    </div>
                </div>
            </div>

            <div class="sd-notes-recent">
                <div class="sd-notes-list-header">
                    <span><?php _e('Recent Notes', 'system-deck'); ?></span>
                </div>
                <ul id="sd-notes-list" class="sd-panel-list">
                    <li class="loading-text"><?php _e('Loading...', 'system-deck'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX: Get Notes
     */
    public function ajax_get_notes(): void
    {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Unauthorized', 'system-deck'));
        }

        $user_id = get_current_user_id();
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 5;

        $args = [
            'post_type'      => self::CPT,
            'post_status'    => 'private',
            'author'         => $user_id,
            'posts_per_page' => $limit,
            'meta_query'     => [
                'relation' => 'OR',
                'pinned_clause' => [
                    'key' => '_sd_is_pinned',
                    'compare' => 'EXISTS'
                ],
                'not_pinned_clause' => [
                    'key' => '_sd_is_pinned',
                    'compare' => 'NOT EXISTS'
                ]
            ],
            'orderby' => ['pinned_clause' => 'DESC', 'modified' => 'DESC']
        ];

        $query = new \WP_Query($args);
        $notes = [];

        while ($query->have_posts()) {
            $query->the_post();
            $id = get_the_ID();
            $notes[] = [
                'id'        => $id,
                'title'     => get_the_title() ?: __('(Untitled)', 'system-deck'),
                'content'   => get_the_content(),
                'date'      => get_the_modified_date('M j'),
                'is_pinned' => (bool) get_post_meta($id, '_sd_is_pinned', true)
            ];
        }
        wp_reset_postdata();

        wp_send_json_success(['notes' => $notes]);
    }

    /**
     * AJAX: Save Note
     */
    public function ajax_save_note(): void
    {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Unauthorized', 'system-deck'));
        }

        // Verify nonce should be handled by caller or generic verification if added to sd-deck.js
        // For now, simple permission check suffices for this port.

        $id = intval($_POST['id'] ?? 0);
        $title = sanitize_text_field($_POST['title'] ?? '');
        $content = wp_kses_post($_POST['content'] ?? '');

        if (!$title && !$content) {
            wp_send_json_error(__('Empty note', 'system-deck'));
        }

        $post_data = [
            'post_title'   => $title,
            'post_content' => $content,
            'post_status'  => 'private',
            'post_type'    => self::CPT
        ];

        if ($id > 0) {
            $post = get_post($id);
            if (!$post || (int)$post->post_author !== get_current_user_id()) {
                wp_send_json_error(__('Permission denied', 'system-deck'));
            }
            $post_data['ID'] = $id;
            $result = wp_update_post($post_data);
        } else {
            $post_data['post_author'] = get_current_user_id();
            $result = wp_insert_post($post_data);
        }

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(['id' => $result]);
    }

    /**
     * AJAX: Pin Note
     */
    public function ajax_pin_note(): void
    {
        if (!current_user_can('edit_posts')) wp_send_json_error();

        $id = intval($_POST['id'] ?? 0);
        $post = get_post($id);

        if (!$post || (int)$post->post_author !== get_current_user_id()) wp_send_json_error();

        $current = get_post_meta($id, '_sd_is_pinned', true);
        if ($current) {
            delete_post_meta($id, '_sd_is_pinned');
        } else {
            update_post_meta($id, '_sd_is_pinned', 1);
        }
        wp_send_json_success();
    }

    /**
     * AJAX: Delete Note
     */
    public function ajax_delete_note(): void
    {
        if (!current_user_can('edit_posts')) wp_send_json_error();

        $id = intval($_POST['id'] ?? 0);
        $post = get_post($id);

        if ($post && (int)$post->post_author === get_current_user_id()) {
            wp_delete_post($id, true);
            wp_send_json_success();
        }
        wp_send_json_error();
    }
}
