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

        // Register Assets
        add_action('wp_enqueue_scripts', [$self, 'register_assets']);
        add_action('admin_enqueue_scripts', [$self, 'register_assets']);

        // Register widget with the Registry
        Registry::register_widget('sd_widget_notes', [
            'id'       => 'sd_widget_notes',
            'title'    => __('Quick Notes', 'system-deck'),
            'callback' => [$self, 'render'],
            'context'  => 'normal',
            'icon'     => 'dashicons-edit-page',
            'enqueue_assets' => [
                'css' => ['sd-notes-css'],
                'js'  => ['sd-notes-js']
            ]
        ]);
    }

    public function register_assets(): void
    {
        wp_register_style('sd-notes-css', \SD_URL . 'assets/css/sd-notes.css', [], \SD_VERSION);
        wp_register_script('sd-notes-js', \SD_URL . 'assets/js/sd-notes.js', ['jquery'], \SD_VERSION, true);

        // Always enqueue for now to ensure availability during AJAX transitions
        if (current_user_can('manage_options')) {
            wp_enqueue_style('sd-notes-css');
            wp_enqueue_script('sd-notes-js');
        }
    }

    public function register_cpt(): void
    {
        register_post_type(self::CPT, [
            'label'           => __('SystemDeck Note', 'system-deck'),
            'public'          => false,
            'show_ui'         => false,
            'capability_type' => 'post',
            'supports'        => ['title', 'editor', 'author', 'excerpt'],
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
                    <label class="sd-checkbox-label" title="<?php esc_attr_e('Use code editor', 'system-deck'); ?>">
                        <input type="checkbox" id="sd-note-is-code" value="1">
                        <?php _e('Code', 'system-deck'); ?>
                    </label>
                    <a href="#" id="sd-note-visit-link" target="_blank" class="button-link" style="display:none; text-decoration:none; align-items:center;">
                        <span class="dashicons dashicons-external" style="margin-top:2px;"></span> <?php _e('Visit', 'system-deck'); ?>
                    </a>
                </div>
                <button type="button" class="button button-small" id="sd-note-new"><?php _e('New Note', 'system-deck'); ?></button>
            </div>

            <div class="sd-note-form">
                <input type="hidden" id="sd-note-id" value="">
                <input type="hidden" id="sd-note-excerpt" value="">
                <div class="sd-input-group">
                    <input type="text" id="sd-note-title" class="widefat" placeholder="<?php esc_attr_e('Title', 'system-deck'); ?>" autocomplete="off">
                </div>
                <div class="sd-input-group flex-grow" id="sd-note-content-wrapper">
                    <textarea id="sd-note-content" class="widefat" placeholder="<?php esc_attr_e('Type your note here...', 'system-deck'); ?>"></textarea>
                </div>
                <div class="sd-input-group flex-grow" id="sd-note-code-wrapper" style="display:none;">
                    <div style="font-size:11px; font-weight:600; color:#8c8f94; margin: 10px 0 4px 0;"><?php _e('SOURCE CODE', 'system-deck'); ?></div>
                    <textarea id="sd-note-code-content" class="widefat"></textarea>
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
                <div class="sd-notes-list-header" style="display:flex; align-items:center;">
                    <span style="flex-grow:1;"><?php _e('Recent Notes', 'system-deck'); ?></span>
                    <button type="button" class="button-link sd-context-filter-btn" id="sd-note-context-filter" title="<?php esc_attr_e('Show notes for this page only', 'system-deck'); ?>" style="text-decoration:none; display:flex; align-items:center; margin-right:10px; color:#666;">
                        <span class="dashicons dashicons-filter" style="margin-right:2px;"></span> <?php _e('This Page', 'system-deck'); ?>
                    </button>
                    <a href="#" id="sd-note-trigger-view-all" style="text-decoration:none;font-size:12px;"><?php _e('View All', 'system-deck'); ?> &rarr;</a>
                </div>
                <ul id="sd-notes-list" class="sd-panel-list">
                    <li class="loading-text"><?php _e('Loading...', 'system-deck'); ?></li>
                </ul>
            </div>

            <!-- Slide Up Drawer -->
            <div class="sd-slide-drawer" id="sd-note-view-all-drawer">
                <div class="sd-drawer-header">
                    <h3><?php _e('All Notes', 'system-deck'); ?></h3>
                    <span class="dashicons dashicons-no-alt sd-btn-icon sd-drawer-close"></span>
                </div>
                <div class="sd-drawer-content">
                    <ul id="sd-notes-all-list" class="sd-panel-list"></ul>
                </div>
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

        $context_url = isset($_POST['context']) ? $_POST['context'] : ''; // Allow raw for loose matching? Better to sanitize.
        // Actually, JS sends window.location.href.
        // If I use LIKE, I should be careful.

        $filter_closure = null;
        if ($context_url) {
            $filter_closure = function($where) use ($context_url) {
                global $wpdb;
                // Match if Excerpt (Saved URL) contains the Context URL (Current Page)
                // OR if Context URL contains the Excerpt?
                // Usually Saved URL is specific. Current page is specific.
                // Simple LIKE match.
                $like = '%' . $wpdb->esc_like($context_url) . '%';
                $where .= $wpdb->prepare(" AND {$wpdb->posts}.post_excerpt LIKE %s", $like);
                return $where;
            };
            add_filter('posts_where', $filter_closure);
        }

        $query = new \WP_Query($args);

        if ($filter_closure) {
            remove_filter('posts_where', $filter_closure);
        }
        $notes = [];

        while ($query->have_posts()) {
            $query->the_post();
            $id = get_the_ID();
            $notes[] = [
                'id'        => $id,
                'title'     => get_the_title() ?: __('(Untitled)', 'system-deck'),
                'content'   => get_the_content(),
                'excerpt'   => get_the_excerpt(),
                'date'      => get_the_modified_date('M j'),
                'is_pinned' => (bool) get_post_meta($id, '_sd_is_pinned', true),
                'is_code'   => (bool) get_post_meta($id, '_sd_note_is_code', true),
                'code_content' => get_post_meta($id, '_sd_note_code_content', true),
                'context'   => get_post_meta($id, '_sd_note_context', true)
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
        $excerpt = sanitize_text_field($_POST['excerpt'] ?? '');

        if (!$title && !$content) {
            wp_send_json_error(__('Empty note', 'system-deck'));
        }

        $post_data = [
            'post_title'   => $title,
            'post_content' => $content,
            'post_excerpt' => $excerpt,
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

        // Save Code Mode Meta
        $is_code = !empty($_POST['is_code']) ? 1 : 0;
        update_post_meta($result, '_sd_note_is_code', $is_code);

        // Save Code Content
        $code_content = $_POST['code_content'] ?? '';
        if (!current_user_can('unfiltered_html')) {
            $code_content = wp_kses_post($code_content);
        }
        update_post_meta($result, '_sd_note_code_content', $code_content);

        // Save Context
        $context = sanitize_text_field($_POST['context'] ?? '');
        update_post_meta($result, '_sd_note_context', $context);

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
    /**
     * AJAX: Get All Notes (Paginated/Limited)
     */
    public function ajax_get_all_notes(): void
    {
        if (!current_user_can('edit_posts')) wp_send_json_error();

        $user_id = get_current_user_id();

        // Use consistent query logic with get_notes (Pinned support)
        $args = [
            'post_type'      => self::CPT,
            'post_status'    => 'private',
            'author'         => $user_id,
            'posts_per_page' => -1,
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
                'content'   => get_the_content(), // Raw content
                'excerpt'   => get_the_excerpt(),
                'date'      => get_the_modified_date('M j'),
                'is_pinned' => (bool) get_post_meta($id, '_sd_is_pinned', true),
                'is_code'   => (bool) get_post_meta($id, '_sd_note_is_code', true),
                'code_content' => get_post_meta($id, '_sd_note_code_content', true),
                'context'   => get_post_meta($id, '_sd_note_context', true)
            ];
        }
        wp_reset_postdata();



        wp_send_json_success(['notes' => $notes]);
    }
}
