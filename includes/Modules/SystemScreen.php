<?php

/**
 * System Screen Module - Command Center
 * Modular workspace management, widget discovery, and system configuration.
 */

declare(strict_types=1);

namespace SystemDeck\Modules;

if (!defined('ABSPATH')) {
    exit;
}

class SystemScreen
{
    public static function init(): void {}

    public static function render(): void
    {
        $user_id = get_current_user_id();
        $user = wp_get_current_user();
        $username = $user->display_name;

        // Get user's last login info
        $last_login = get_user_meta($user_id, 'sd_last_login', true);
        $last_ip = get_user_meta($user_id, 'sd_last_login_ip', true);

        // Update current login
        update_user_meta($user_id, 'sd_last_login', current_time('mysql'));
        update_user_meta($user_id, 'sd_last_login_ip', $_SERVER['REMOTE_ADDR'] ?? 'Unknown');

        // Get workspaces
        $workspaces = self::get_user_workspaces($user_id);

        // Get widget scanner data
        $scanner_data = self::get_scanner_data($user_id);

        ob_start();
?>
        <div class="wrap sd-command-center">
            <h1><?php _e('SystemDeck Command Center', 'system-deck'); ?></h1>
            <p class="description"><?php _e('Manage workspaces, discover widgets, and configure your SystemDeck.', 'system-deck'); ?></p>

            <!-- Single Native Grid (Like Workspace) -->
            <div id="sd-command-grid" class="sd-native-grid">

                <!-- Workspace Cards (1/3 width each) -->
                <?php foreach ($workspaces as $workspace): ?>
                    <?php self::render_workspace_card($workspace); ?>
                <?php endforeach; ?>

                <!-- Tabbed Settings (Full Width) -->
                <div class="sd-grid-widget sd-grid-full-width">
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle"><?php _e('System Configuration', 'system-deck'); ?></h2>
                        </div>
                        <div class="inside">
                            <!-- Tab Navigation -->
                            <nav class="nav-tab-wrapper wp-clearfix" aria-label="<?php esc_attr_e('Command Center Tabs', 'system-deck'); ?>">
                                <a href="#sd-tab-welcome" class="nav-tab nav-tab-active" data-tab="welcome">
                                    <span class="dashicons dashicons-admin-home"></span>
                                    <?php _e('Welcome', 'system-deck'); ?>
                                </a>
                                <a href="#sd-tab-scanner" class="nav-tab" data-tab="scanner">
                                    <span class="dashicons dashicons-search"></span>
                                    <?php _e('Widget Scanner', 'system-deck'); ?>
                                </a>
                                <a href="#sd-tab-import-export" class="nav-tab" data-tab="import-export">
                                    <span class="dashicons dashicons-database-import"></span>
                                    <?php _e('Import/Export', 'system-deck'); ?>
                                </a>
                                <a href="#sd-tab-help" class="nav-tab" data-tab="help">
                                    <span class="dashicons dashicons-editor-help"></span>
                                    <?php _e('Help', 'system-deck'); ?>
                                </a>
                            </nav>

                            <!-- Tab Panels -->
                            <div class="sd-tab-panels">
                                <!-- Welcome Tab -->
                                <div id="sd-tab-welcome" class="sd-tab-panel active">
                                    <?php self::render_welcome_tab($username, $last_login, $last_ip); ?>
                                </div>

                                <!-- Scanner Tab -->
                                <div id="sd-tab-scanner" class="sd-tab-panel" style="display:none;">
                                    <?php self::render_scanner_tab($scanner_data); ?>
                                </div>

                                <!-- Import/Export Tab -->
                                <div id="sd-tab-import-export" class="sd-tab-panel" style="display:none;">
                                    <?php self::render_import_export_tab(); ?>
                                </div>

                                <!-- Help Tab -->
                                <div id="sd-tab-help" class="sd-tab-panel" style="display:none;">
                                    <?php self::render_help_tab(); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <style>
            /* Command Center Styles */
            .sd-command-center {
                max-width: 100%;
            }

            .sd-command-center .description {
                margin-bottom: 20px;
            }

            /* Workspace Card (1/3 width on large screens) */
            .sd-workspace-card {
                /* Inherits grid sizing from .sd-native-grid */
                /* Will be 1 column on mobile, 1/3 on desktop */
            }

            /* Full Width Settings */
            .sd-grid-full-width {
                grid-column: 1 / -1; /* Span all columns */
            }

            /* Workspace Card Styling (Like Widgets) */
            .sd-workspace-card .postbox {
                background: #fff;
                border: 1px solid #c3c4c7;
                box-shadow: 0 1px 1px rgba(0,0,0,0.04);
            }

            .sd-workspace-card .postbox-header {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 12px;
                border-bottom: 1px solid #c3c4c7;
                background: #f6f7f7;
                min-height: 44px;
            }

            .sd-workspace-card .postbox-header .hndle {
                flex: 1;
                font-size: 14px;
                font-weight: 600;
                margin: 0;
                cursor: move;
            }

            .sd-workspace-drag-handle {
                color: #787c82;
                font-size: 18px;
                cursor: move;
            }

            .sd-workspace-card-actions {
                display: flex;
                gap: 5px;
            }

            .sd-workspace-card-actions button {
                padding: 4px 8px;
                background: transparent;
                border: none;
                cursor: pointer;
                color: #50575e;
                font-size: 16px;
                line-height: 1;
            }

            .sd-workspace-card-actions button:hover {
                color: #2271b1;
            }

            .sd-workspace-card-actions button.delete:hover {
                color: #d63638;
            }

            .sd-workspace-card .inside {
                padding: 12px;
            }

            .sd-workspace-card-meta {
                font-size: 13px;
                color: #646970;
                line-height: 1.8;
            }

            .sd-workspace-card-meta div {
                margin: 3px 0;
            }

            /* Tab Styles */
            .nav-tab-wrapper {
                border-bottom: 1px solid #c3c4c7;
                margin: 0 -12px 20px -12px;
                padding: 0 12px;
            }

            .nav-tab {
                display: inline-flex;
                align-items: center;
                gap: 6px;
            }

            .nav-tab .dashicons {
                font-size: 16px;
                width: 16px;
                height: 16px;
            }

            .sd-tab-panel {
                padding: 0;
            }

            .sd-tab-panel.active {
                display: block !important;
            }

            /* Welcome Tab */
            .sd-welcome-greeting {
                background: #f6f7f7;
                border-left: 4px solid #2271b1;
                padding: 15px;
                margin-bottom: 20px;
            }

            .sd-welcome-greeting h3 {
                margin: 0 0 10px 0;
                font-size: 15px;
            }

            .sd-welcome-info {
                font-size: 13px;
                color: #646970;
                line-height: 1.8;
            }

            .sd-welcome-info p {
                margin: 5px 0;
            }

            .sd-create-workspace-section {
                margin-top: 20px;
            }

            .sd-create-workspace-form {
                display: flex;
                gap: 10px;
                align-items: center;
                margin-top: 10px;
            }

            .sd-create-workspace-form input[type="text"] {
                flex: 1;
                max-width: 400px;
            }

            /* Scanner Tab */
            .sd-scanner-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 15px;
            }

            .sd-widget-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 10px;
                margin: 15px 0;
            }

            .sd-widget-option {
                display: block;
                padding: 10px;
                border: 1px solid #c3c4c7;
                background: #f9f9f9;
                border-radius: 4px;
                cursor: pointer;
                transition: all 0.2s;
            }

            .sd-widget-option:hover {
                background: #f0f0f1;
                border-color: #2271b1;
            }

            .sd-widget-option input[type="checkbox"] {
                margin-right: 8px;
            }

            .sd-manual-builder {
                margin-top: 20px;
                border-top: 1px solid #dcdcde;
                padding-top: 15px;
            }

            /* Import/Export Tab */
            .sd-import-export-section {
                margin-bottom: 30px;
            }

            .sd-import-export-section h3 {
                margin: 0 0 10px 0;
                font-size: 14px;
                font-weight: 600;
            }

            .sd-import-export-actions {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
            }

            /* Help Tab */
            .sd-help-placeholder {
                text-align: center;
                padding: 40px 20px;
                color: #646970;
            }

            .sd-help-placeholder .dashicons {
                font-size: 48px;
                width: 48px;
                height: 48px;
                opacity: 0.3;
            }

            /* Sortable Placeholder */
            .sd-workspace-card-placeholder {
                background: #f0f6fc;
                border: 2px dashed #2271b1;
                visibility: visible !important;
                height: 150px;
            }

            /* Sortable Helper */
            .sd-workspace-card.ui-sortable-helper {
                opacity: 0.8;
                box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Tab Switching
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                var tab = $(this).data('tab');

                // Update nav tabs
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');

                // Update panels
                $('.sd-tab-panel').removeClass('active').hide();
                $('#sd-tab-' + tab).addClass('active').show();
            });

            // Workspace Card Sorting (Same as workspace widgets)
            $('#sd-command-grid').sortable({
                items: '.sd-workspace-card',
                handle: '.postbox-header',
                placeholder: 'sd-workspace-card-placeholder',
                tolerance: 'pointer',
                cursor: 'move',
                update: function(event, ui) {
                    // Get new order
                    var order = [];
                    $('#sd-command-grid .sd-workspace-card').each(function() {
                        order.push($(this).data('workspace-id'));
                    });

                    // Live Update Sidebar Menu (if present)
                    var menuContainer = $('#sd-menu-workspaces .wp-submenu');
                    if (menuContainer.length) {
                        var menuItems = menuContainer.children('li').not('.wp-submenu-head');
                        var itemsById = {};

                        // Map existing items
                        menuItems.each(function() {
                            var link = $(this).find('a');
                            var id = link.data('workspace');
                            if (id) itemsById[id] = $(this);
                        });

                        // Re-append in new order
                        $.each(order, function(index, id) {
                            if (itemsById[id]) {
                                menuContainer.append(itemsById[id]);
                            }
                        });
                    }

                    // Save order via AJAX
                    $.post(sd_vars.ajax_url, {
                        action: 'sd_update_workspace_order',
                        nonce: '<?php echo wp_create_nonce('sd_load_shell'); ?>',
                        order: order
                    });
                }
            });

            // Create Workspace Toggle
            $('#sd-btn-create-workspace').on('click', function() {
                $('#sd-create-workspace-form').slideToggle();
                $('#sd-new-workspace-name').focus();
            });

            $('#sd-cancel-create-workspace').on('click', function() {
                $('#sd-create-workspace-form').slideUp();
                $('#sd-new-workspace-name').val('');
            });

            // Create Workspace Submit
            $('#sd-save-create-workspace').on('click', function() {
                var name = $('#sd-new-workspace-name').val().trim();
                if (!name) {
                    alert('<?php _e('Please enter a workspace name', 'system-deck'); ?>');
                    return;
                }

                var btn = $(this);
                btn.prop('disabled', true).text('<?php _e('Creating...', 'system-deck'); ?>');

                $.post(sd_vars.ajax_url, {
                    action: 'sd_create_workspace',
                    nonce: '<?php echo wp_create_nonce('sd_load_shell'); ?>',
                    name: name
                }, function(response) {
                    if (response.success) {
                        // Inject Card
                        var $grid = $('#sd-command-grid');
                        var $settings = $grid.find('.sd-grid-full-width');
                        var $html = $(response.data.html);

                        // Insert Before Settings Panel
                        if ($settings.length) {
                            $html.insertBefore($settings);
                        } else {
                            $grid.append($html);
                        }

                        // Refresh Sortable
                        $grid.sortable('refresh');

                        // Reset Form
                        $('#sd-new-workspace-name').val('');
                        $('#sd-create-workspace-form').slideUp();
                        btn.prop('disabled', false).text('<?php _e('Create', 'system-deck'); ?>');

                        // Add to Sidebar Menu
                        var menuContainer = $('#sd-menu-workspaces .wp-submenu');
                        if (menuContainer.length && response.data.workspace) {
                            var ws = response.data.workspace;
                            // Simple slugify for link (doesn't have to be perfect, mostly for visual logic)
                            // Ideally backend sends this, but this is sufficient for immediate feedback
                            var slug = ws.name.toLowerCase().replace(/[^a-z0-9]+/g, '-');
                            menuContainer.append('<li><a href="#workspace-'+slug+'" data-workspace="'+ws.id+'">'+ws.name+'</a></li>');
                        }

                    } else {
                        alert(response.data.message || '<?php _e('Error creating workspace', 'system-deck'); ?>');
                        btn.prop('disabled', false).text('<?php _e('Create', 'system-deck'); ?>');
                    }
                });
            });

            // Delete Workspace
            $(document).on('click', '.sd-delete-workspace', function() {
                if (!confirm('<?php _e('Are you sure you want to delete this workspace? This cannot be undone.', 'system-deck'); ?>')) {
                    return;
                }

                var card = $(this).closest('.sd-workspace-card');
                var workspaceId = card.data('workspace-id');

                $.post(sd_vars.ajax_url, {
                    action: 'sd_delete_workspace',
                    nonce: '<?php echo wp_create_nonce('sd_load_shell'); ?>',
                    workspace_id: workspaceId
                }, function(response) {
                    if (response.success) {
                        // Remove Card
                        card.fadeOut(function() {
                            $(this).remove();
                        });

                        // Remove from Sidebar
                        var menuContainer = $('#sd-menu-workspaces .wp-submenu');
                        if (menuContainer.length) {
                             menuContainer.find('a[data-workspace="'+workspaceId+'"]').closest('li').remove();
                        }
                    } else {
                        alert(response.data.message || '<?php _e('Error deleting workspace', 'system-deck'); ?>');
                    }
                });
            });

            // Rename Workspace
            $(document).on('click', '.sd-rename-workspace', function() {
                var card = $(this).closest('.sd-workspace-card');
                var currentName = card.find('.hndle').text();
                var newName = prompt('<?php _e('Enter new workspace name:', 'system-deck'); ?>', currentName);

                if (!newName || newName === currentName) return;

                var workspaceId = card.data('workspace-id');

                $.post(sd_vars.ajax_url, {
                    action: 'sd_rename_workspace',
                    nonce: '<?php echo wp_create_nonce('sd_load_shell'); ?>',
                    workspace_id: workspaceId,
                    name: newName
                }, function(response) {
                    if (response.success) {
                        card.find('.hndle').text(newName);

                        // Update Sidebar
                        var menuContainer = $('#sd-menu-workspaces .wp-submenu');
                        if (menuContainer.length) {
                             menuContainer.find('a[data-workspace="'+workspaceId+'"]').text(newName);
                        }
                    } else {
                        alert(response.data.message || '<?php _e('Error renaming workspace', 'system-deck'); ?>');
                    }
                });
            });

            // Trigger React mount (if needed)
            $(document).trigger('sd_system_screen_rendered');
        });
        </script>
<?php
        $html = ob_get_clean();
        wp_send_json_success(['html' => $html]);
    }

    private static function get_user_workspaces($user_id): array
    {
        $workspaces = get_user_meta($user_id, 'sd_workspaces', true);

        if (empty($workspaces) || !is_array($workspaces)) {
            // Create default workspace
            $workspaces = [
                'default' => [
                    'id' => 'default',
                    'name' => __('Default', 'system-deck'),
                    'widgets' => [],
                    'created' => current_time('mysql'),
                    'order' => 0
                ]
            ];
            update_user_meta($user_id, 'sd_workspaces', $workspaces);
        }

        // Normalize legacy data on read & Sync Widget Counts
        $is_dirty = false;

        foreach ($workspaces as $key => &$ws) {
            if (!is_array($ws)) $ws = [];

            // Fix missing name/id (Legacy format: ['Name' => ['created'=>123]])
            if (empty($ws['name'])) {
                $ws['name'] = is_string($key) ? $key : "Workspace $key";
                $is_dirty = true;
            }
            if (empty($ws['id'])) {
                $ws['id'] = is_string($key) ? $key : "ws_$key";
                $is_dirty = true;
            }

            // Sync Widget List from Layout State (Self-Healing)
            if (!isset($ws['widgets']) || !is_array($ws['widgets'])) {
                $slug = sanitize_title($ws['id']);
                // Use StorageEngine directly to find layout
                $context = new \SystemDeck\Core\Context($user_id, $slug);
                $layout = \SystemDeck\Core\StorageEngine::get('layout', $context);
                $ws['widgets'] = [];

                if (is_array($layout)) {
                    foreach ($layout as $item) {
                        if (($item['type'] ?? '') === 'widget' && !empty($item['id'])) {
                            $ws['widgets'][] = $item['id'];
                        }
                    }
                }
                $is_dirty = true;
            }
        }
        unset($ws); // Break reference

        // Save back if we healed data
        if ($is_dirty) {
            update_user_meta($user_id, 'sd_workspaces', $workspaces);
        }

        // Sort by order
        uasort($workspaces, function($a, $b) {
            return ($a['order'] ?? 0) - ($b['order'] ?? 0);
        });

        return $workspaces;
    }

    public static function render_workspace_card($workspace): void
    {
        $widget_count = is_array($workspace['widgets'] ?? null) ? count($workspace['widgets']) : 0;
        $created_date = isset($workspace['created']) ? mysql2date(get_option('date_format'), $workspace['created']) : __('Unknown', 'system-deck');
        $is_default = ($workspace['id'] ?? '') === 'default';
?>
        <div class="sd-workspace-card sd-grid-widget" data-workspace-id="<?php echo esc_attr($workspace['id']); ?>">
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle"><?php echo esc_html($workspace['name']); ?></h2>
                    <div class="sd-workspace-card-actions">
                        <button type="button" class="sd-load-ws-btn sd-btn-icon" data-workspace="<?php echo esc_attr($workspace['id']); ?>" data-name="<?php echo esc_attr($workspace['name']); ?>" title="<?php esc_attr_e('Open Workspace', 'system-deck'); ?>">
                            <span class="dashicons dashicons-external"></span>
                        </button>
                        <button type="button" class="sd-rename-workspace sd-btn-icon" title="<?php esc_attr_e('Rename', 'system-deck'); ?>">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        <?php if (!$is_default): ?>
                            <button type="button" class="sd-delete-workspace delete sd-btn-icon" title="<?php esc_attr_e('Delete', 'system-deck'); ?>">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="inside">
                    <div class="sd-workspace-card-meta">
                        <div><strong><?php _e('Widgets:', 'system-deck'); ?></strong> <?php echo $widget_count; ?></div>
                        <div><strong><?php _e('Created:', 'system-deck'); ?></strong> <?php echo esc_html($created_date); ?></div>
                    </div>
                </div>
            </div>
        </div>
<?php
    }

    private static function render_welcome_tab($username, $last_login, $last_ip): void
    {
        $formatted_date = $last_login ? mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $last_login) : __('First login', 'system-deck');
?>
        <div class="sd-welcome-greeting">
            <h3><?php printf(__('Welcome to SystemDeck, %s!', 'system-deck'), esc_html($username)); ?></h3>
            <div class="sd-welcome-info">
                <p><strong><?php _e('Last Login:', 'system-deck'); ?></strong> <?php echo esc_html($formatted_date); ?></p>
                <p><strong><?php _e('IP Address:', 'system-deck'); ?></strong> <?php echo esc_html($last_ip ?: __('Unknown', 'system-deck')); ?></p>
            </div>
        </div>

        <div class="sd-create-workspace-section">
            <button id="sd-btn-create-workspace" class="button button-primary">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php _e('Create New Workspace', 'system-deck'); ?>
            </button>

            <div id="sd-create-workspace-form" class="sd-create-workspace-form" style="display:none;">
                <input type="text" id="sd-new-workspace-name" placeholder="<?php esc_attr_e('Workspace Name', 'system-deck'); ?>" class="regular-text">
                <button id="sd-save-create-workspace" class="button button-primary"><?php _e('Create', 'system-deck'); ?></button>
                <button id="sd-cancel-create-workspace" class="button"><?php _e('Cancel', 'system-deck'); ?></button>
            </div>
        </div>
<?php
    }

    private static function get_scanner_data($user_id): array
    {
        $active_proxies = get_user_meta($user_id, 'sd_active_proxy_widgets', true) ?: [];
        $available_widgets = [];

        // --- SOURCE 1: LIVE REGISTRY (Silenced) ---
        ob_start();
        try {
            if (!function_exists('wp_dashboard_setup')) require_once ABSPATH . 'wp-admin/includes/dashboard.php';
            if (!class_exists('WP_Screen')) require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
            if (!function_exists('get_current_screen')) require_once ABSPATH . 'wp-admin/includes/screen.php';

            global $pagenow;
            $pagenow = 'index.php';
            set_current_screen('dashboard');

            do_action('admin_menu');
            do_action('load-index.php');
            wp_dashboard_setup();
        } catch (\Throwable $e) {
            // Ignore simulation errors
        }
        ob_end_clean();

        // Harvest Live Widgets
        global $wp_meta_boxes;
        if (isset($wp_meta_boxes['dashboard']) && is_array($wp_meta_boxes['dashboard'])) {
            foreach ($wp_meta_boxes['dashboard'] as $context => $priorities) {
                foreach ($priorities as $priority => $boxes) {
                    foreach ($boxes as $id => $box) {
                        if (!$box) continue;
                        $title = isset($box['title']) ? strip_tags((string)$box['title']) : $id;
                        $available_widgets[$id] = $title;
                    }
                }
            }
        }

        // --- SOURCE 2: HISTORY (Database) ---
        $saved_order = get_user_meta($user_id, 'meta-box-order_dashboard', true);
        if (is_array($saved_order)) {
            foreach ($saved_order as $context => $id_string) {
                if (empty($id_string)) continue;
                $ids = explode(',', $id_string);
                foreach ($ids as $id) {
                    if (empty($id) || strpos($id, 'sd_') === 0) continue;
                    if (!isset($available_widgets[$id])) {
                        $available_widgets[$id] = self::humanize_id($id) . ' (History)';
                    }
                }
            }
        }

        // --- SOURCE 3: OPTIONS (Database) ---
        $widget_options = get_option('dashboard_widget_options');
        if (is_array($widget_options)) {
            foreach ($widget_options as $id => $settings) {
                if (empty($id) || strpos($id, 'sd_') === 0) continue;
                if (!isset($available_widgets[$id])) {
                    $available_widgets[$id] = self::humanize_id($id) . ' (Settings)';
                }
            }
        }

        return [
            'available' => $available_widgets,
            'active' => $active_proxies
        ];
    }

    private static function render_scanner_tab($scanner_data): void
    {
?>
        <div class="sd-scanner-header">
            <h3><?php _e('Dashboard Widget Discovery', 'system-deck'); ?></h3>
            <button type="button" class="button button-secondary sd-deep-scan-btn"><?php _e('Perform Deep Scan', 'system-deck'); ?></button>
        </div>

        <p><?php _e('Select widgets discovered from Live Registry, History, and Options.', 'system-deck'); ?></p>

        <form id="sd-proxy-manager-form">
            <div class="sd-widget-grid">
                <?php if (empty($scanner_data['available'])): ?>
                    <p class="sd-no-widgets-msg"><?php _e('No widgets found. Please visit your WP Dashboard once to populate history.', 'system-deck'); ?></p>
                <?php else: ?>
                    <?php foreach ($scanner_data['available'] as $id => $title): ?>
                        <label class="sd-widget-option">
                            <input type="checkbox" name="widgets[]" value="<?php echo esc_attr($id); ?>" <?php checked(in_array($id, $scanner_data['active'])); ?>>
                            <span style="font-weight:600; font-size:14px;"><?php echo esc_html($title); ?></span>
                            <div style="color:#646970; font-size:11px; margin-top:3px; font-family:monospace;"><?php echo esc_html($id); ?></div>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="sd-manual-builder">
                <h4 style="margin:0 0 10px 0; font-size:14px;"><?php _e('Manual Widget Builder', 'system-deck'); ?></h4>
                <div style="display:flex; gap:10px;">
                    <input type="text" id="sd-manual-widget-id" placeholder="<?php esc_attr_e('Widget ID (e.g. wpseo-dashboard-overview)', 'system-deck'); ?>" style="flex:1; max-width:400px;">
                    <button type="button" id="sd-manual-add-btn" class="button button-secondary"><?php _e('Add Custom', 'system-deck'); ?></button>
                </div>
                <p class="description" style="margin-top:5px;"><?php _e('Use this for stubborn widgets like Yoast if Deep Scan misses them.', 'system-deck'); ?></p>
            </div>

            <div style="margin-top:20px; border-top:1px solid #dcdcde; padding-top:15px;">
                <button type="button" id="sd-save-proxies" class="button button-primary"><?php _e('Save Selection', 'system-deck'); ?></button>
                <span class="spinner" style="float:none;"></span>
                <span id="sd-proxy-save-msg" style="margin-left:10px; font-weight:600;"></span>
            </div>
        </form>
<?php
    }

    private static function render_import_export_tab(): void
    {
?>
        <div class="sd-import-export-section">
            <h3><?php _e('Export Workspaces', 'system-deck'); ?></h3>
            <p><?php _e('Export your workspace configurations to back them up or share with others.', 'system-deck'); ?></p>
            <div class="sd-import-export-actions">
                <button type="button" id="sd-export-all" class="button button-primary">
                    <span class="dashicons dashicons-download"></span>
                    <?php _e('Export All Workspaces', 'system-deck'); ?>
                </button>
                <button type="button" id="sd-export-single" class="button">
                    <span class="dashicons dashicons-download"></span>
                    <?php _e('Export Current Workspace', 'system-deck'); ?>
                </button>
            </div>
        </div>

        <div class="sd-import-export-section">
            <h3><?php _e('Import Workspaces', 'system-deck'); ?></h3>
            <p><?php _e('Import workspace configurations. Missing widgets will be skipped automatically.', 'system-deck'); ?></p>
            <div class="sd-import-export-actions">
                <input type="file" id="sd-import-file" accept=".json" style="display:none;">
                <button type="button" id="sd-import-trigger" class="button button-primary">
                    <span class="dashicons dashicons-upload"></span>
                    <?php _e('Import Workspaces', 'system-deck'); ?>
                </button>
            </div>
            <div id="sd-import-status" style="margin-top:15px;"></div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Export All
            $('#sd-export-all').on('click', function() {
                window.location.href = sd_vars.ajax_url + '?action=sd_export_workspaces&nonce=<?php echo wp_create_nonce('sd_export'); ?>&type=all';
            });

            // Export Single
            $('#sd-export-single').on('click', function() {
                window.location.href = sd_vars.ajax_url + '?action=sd_export_workspaces&nonce=<?php echo wp_create_nonce('sd_export'); ?>&type=single';
            });

            // Import Trigger
            $('#sd-import-trigger').on('click', function() {
                $('#sd-import-file').click();
            });

            // Import File Handler
            $('#sd-import-file').on('change', function(e) {
                var file = e.target.files[0];
                if (!file) return;

                var reader = new FileReader();
                reader.onload = function(event) {
                    try {
                        var data = JSON.parse(event.target.result);

                        $.post(sd_vars.ajax_url, {
                            action: 'sd_import_workspaces',
                            nonce: '<?php echo wp_create_nonce('sd_load_shell'); ?>',
                            data: JSON.stringify(data)
                        }, function(response) {
                            if (response.success) {
                                $('#sd-import-status').html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                                setTimeout(function() {
                                    location.reload();
                                }, 2000);
                            } else {
                                $('#sd-import-status').html('<div class="notice notice-error"><p>' + (response.data.message || '<?php _e('Import failed', 'system-deck'); ?>') + '</p></div>');
                            }
                        });
                    } catch (err) {
                        $('#sd-import-status').html('<div class="notice notice-error"><p><?php _e('Invalid JSON file', 'system-deck'); ?></p></div>');
                    }
                };
                reader.readAsText(file);
            });
        });
        </script>
<?php
    }

    private static function render_help_tab(): void
    {
?>
        <div class="sd-help-placeholder">
            <span class="dashicons dashicons-book-alt"></span>
            <h3><?php _e('Documentation Coming Soon', 'system-deck'); ?></h3>
            <p><?php _e('User documentation and help resources will be available here.', 'system-deck'); ?></p>
        </div>
<?php
    }

    private static function humanize_id(string $id): string
    {
        $title = str_replace(
            ['wpseo-', 'aioseo-', 'dashboard_', 'wc-', '-', '_'],
            ['Yoast ', 'AIOSEO ', '', 'Woo ', ' ', ' '],
            $id
        );
        return ucwords(trim($title));
    }
}
