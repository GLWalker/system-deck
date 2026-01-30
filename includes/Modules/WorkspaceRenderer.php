<?php
/**
 * Workspace Renderer (React Data Provider)
 * Status: FIXED - Now hydrates widgets (executes PHP) before sending to React.
 */
declare(strict_types=1);
namespace SystemDeck\Modules;
if (!defined('ABSPATH')) { exit; }
use SystemDeck\Core\Registry;

class WorkspaceRenderer {
    public static function init(): void {
        // Hydration logic moved to AjaxHandler::handle_hydrate_widget
    }

    public static function render(string $workspace_id = 'Default'): void {
        $registry = Registry::instance();
        $manifest = $registry->hydrate_manifest($workspace_id);

        if (empty($manifest)) {
            echo '<div class="sd-error">Workspace not found.</div>';
            return;
        }

        // Output
        echo '<script>window.SD_Manifest = ' . json_encode($manifest) . ';</script>';
        echo '<div id="sd-react-root" class="sd-workspace-canvas"></div>';

        if (isset($manifest['workspace'])) {
            echo '<script>document.getElementById("sd-workspace-title").textContent = "' . esc_js($manifest['workspace']) . '";</script>';
        }
    }
}