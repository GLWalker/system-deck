<?php
/**
 * SystemDeck Registry (Dynamic Redux)
 */
declare(strict_types=1);
namespace SystemDeck\Core;
if (!defined('ABSPATH')) { exit; }

class Registry {
    private static ?Registry $instance = null;
    private array $workspaces = []; // Kept for legacy compat
    private array $widgets = [];
    private array $pin_items = [];

    public static function instance(): Registry {
        if (is_null(self::$instance)) {
            self::$instance = new self();
            self::$instance->init();
        }
        return self::$instance;
    }

    private function init(): void {
        add_action('system_deck_init', [$this, 'discover_widgets']);
        add_action('sd_register_widgets', [$this, 'register_core_widgets']);
    }

    public function discover_widgets(): void {
        // 1. Scan Widgets directory
        $dir = SD_PATH . 'includes/Widgets/';
        if (is_dir($dir)) {
            $files = glob($dir . '*.php');
            foreach ($files as $file) {
                require_once $file; // Ensure file is loaded
                $class = 'SystemDeck\\Widgets\\' . basename($file, '.php');
                if (class_exists($class) && method_exists($class, 'init')) {
                    call_user_func([$class, 'init']);
                }
            }
        }

        // 2. Allow third parties to register
        do_action('sd_register_widgets');
    }

    public function register_core_widgets(): void {
        // Core widgets are now initialized via their own class init() methods
    }

    /**
     * Legacy support: Prevents fatal error if Defaults.php calls this.
     */
    public function register_workspace(string $id, array $args): void {
        $this->workspaces[$id] = $args;
    }

    public static function register_widget(string $id, array $args): void {
        $instance = self::instance();
        $defaults = [
            'id' => $id,
            'title' => 'Untitled',
            'callback' => null,
            'icon' => 'dashicons-admin-generic',
            'context' => 'normal',
            'origin' => 'internal'
        ];
        $instance->widgets[$id] = array_merge($defaults, $args);
    }

    public static function register_pin_item(string $id, array $args): void {
        self::instance()->pin_items[$id] = $args;
    }

    public function get_widgets(): array { return $this->widgets; }

    public function get_pin_items(): array { return $this->pin_items; }

    /**
     * Dynamic Workspace Lookup (User Meta First)
     */
    public function get_workspace(string $id): ?array {
        $user_id = get_current_user_id();
        $target_slug = sanitize_title($id);

        if ($id === 'system') return null;

        // 1. Alias Mapping
        if ($target_slug === 'system_main' || $target_slug === 'default' || empty($target_slug)) {
            $target_slug = 'default';
        }

        // 2. Fetch User Workspaces
        $workspaces = get_user_meta($user_id, 'sd_workspaces', true) ?: [];

        // Normalize Default if missing (matches SystemScreen logic)
        if (empty($workspaces) || !is_array($workspaces)) {
            $workspaces = ['default' => ['id'=>'default', 'name'=>'Default']];
        }

        $found_data = null;
        $found_key = null;

        foreach ($workspaces as $key => $data) {
            // Legacy Name-Key Support
            $is_new = is_array($data) && isset($data['name']);
            $name = $is_new ? $data['name'] : $key;
            $ws_id = $is_new ? ($data['id'] ?? $key) : $key;

            // Match against ID (Key) OR Slugified Name (Legacy)
            if (sanitize_title($ws_id) === $target_slug || sanitize_title($name) === $target_slug) {
                $found_data = $data;
                $found_key = $key;
                break;
            }
        }

        // 3. Fallback to Default
        if (!$found_data) {
             if ($target_slug === 'default') {
                 $found_data = ['id'=>'default', 'name'=>'Default'];
                 $found_key = 'default';
             } else {
                 return null;
             }
        }

        // Extract Title and ID
        $is_new = is_array($found_data) && isset($found_data['name']);
        $real_title = $is_new ? $found_data['name'] : $found_key;
        $real_id = $is_new ? ($found_data['id'] ?? $found_key) : $found_key;
        $real_slug = sanitize_title($real_id); // Use ID as slug for storage now

        // 3. Support Legacy storage (slug based on NAME) if ID storage empty?
        // Actually moving forward we should use ID. But for backward compat...
        // Let's check both.
        $context = new Context((int)$user_id, $real_slug);
        $saved_layout = StorageEngine::get('layout', $context);

        if (!$saved_layout) {
            // Try legacy name-based slug
            $legacy_slug = sanitize_title($real_title);
            $legacy_context = new Context((int)$user_id, $legacy_slug);
            $saved_layout = StorageEngine::get('layout', $legacy_context);
        }

        // 4. Default Layout from Class Constant if empty
        if ($saved_layout === false || $saved_layout === '') {
            $saved_layout = Defaults::get_default_layout();
        }

        return ['id' => $real_id, 'title' => $real_title, 'layout' => $saved_layout];
    }

    /**
     * Centralized Hydration logic for the entire system.
     * Builds the complete manifest for a given workspace.
     *
     * UPDATED: Option 2 - Pre-loads ALL widget content server-side to bypass AJAX 403 issues.
     */
    public function hydrate_manifest(string $id): array
    {
        $user_id = get_current_user_id();
        $slug = sanitize_title($id);

        // 1. Base Configuration
        $workspace = $this->get_workspace($id);
        if (!$workspace) {
            return [];
        }

        // 2. Fetch User State
        $context = new Context((int)$user_id, $slug);
        $pins = StorageEngine::get('pins', $context) ?: [];
        $layout = StorageEngine::get('layout', $context);

        // 3. Fallback Layout if NO state exists
        if ($layout === false) {
            $layout = [];
            foreach ($pins as $pin) {
                $layout[] = ['id' => $pin['id'] ?? $pin, 'type' => 'pin', 'w' => 3];
            }
            $default_rows = Defaults::get_default_layout();
            foreach ($default_rows as $row) {
                $ids = array_merge($row['widgets'] ?? [], $row['widgets_left'] ?? [], $row['widgets_right'] ?? []);
                foreach ($ids as $lwid) {
                    $layout[] = ['id' => $lwid, 'type' => 'widget', 'w' => 6];
                }
            }
        }

        // 4. SELECTIVE HYDRATION (Hybrid Approach)
        $definitions = $this->get_widgets();
        $hydrated_widgets = [];

        // Map layout for fast lookup
        $active_map = [];
        if (is_array($layout)) {
            foreach ($layout as $item) {
                if (isset($item['type'], $item['id']) && $item['type'] === 'widget') {
                    $active_map[$item['id']] = $item;
                }
            }
        }

        foreach ($definitions as $wid => $widget) {
            // Standardize metadata
            $widget['content'] = '';
            $widget['active'] = isset($active_map[$wid]);

            // Merge saved properties (like 'w') into the definition if active
            if ($widget['active']) {
                $widget = array_merge($widget, $active_map[$wid]);

                // Property Standardisation: Map span -> w
                if (!isset($widget['w']) && isset($widget['span'])) {
                    $widget['w'] = $widget['span'];
                }
            }

            // FULL HYDRATION: Pre-load content for ALL widgets
            if (isset($widget['callback']) && is_callable($widget['callback'])) {

                // Asset Injection: Enqueue widget-specific assets before rendering
                if (isset($widget['enqueue_assets']) && is_array($widget['enqueue_assets'])) {
                    if (!empty($widget['enqueue_assets']['css'])) {
                        foreach ($widget['enqueue_assets']['css'] as $handle) wp_enqueue_style($handle);
                    }
                    if (!empty($widget['enqueue_assets']['js'])) {
                        foreach ($widget['enqueue_assets']['js'] as $handle) wp_enqueue_script($handle);
                    }
                }

                ob_start();
                try {
                    call_user_func($widget['callback']);
                    $widget['content'] = ob_get_clean();
                } catch (\Throwable $e) {
                    ob_end_clean();
                    $widget['content'] = (defined('WP_DEBUG') && WP_DEBUG)
                        ? "Error in $wid: " . $e->getMessage()
                        : 'Widget error';
                }
            }

            $hydrated_widgets[$wid] = $widget;
        }

        return [
            'workspace' => $workspace['title'],
            'slug'      => $slug,
            'config'    => $workspace,
            'user'      => [
                'layout' => $layout,
                'pins'   => $pins
            ],
            'registry'  => $hydrated_widgets,
            'available_pins' => $this->get_pin_items()
        ];
    }
}
