<?php
/**
 * SystemDeck Bootstrapper
 * Initializes the plugin kernel and autoloads core components.
 *
 * @package SystemDeck
 */

declare(strict_types=1);

namespace SystemDeck\Core;

if (!defined('ABSPATH')) {
    exit;
}

final class Boot
{
    private static ?Boot $instance = null;

    public static function instance(): Boot
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init(): void
    {
        // 1. GATEKEEPER: Stop recursion immediately (Fixes Memory Leak)
        if (isset($_GET['page']) && $_GET['page'] === 'sd-dashboard-tunnel') {
            require_once plugin_dir_path(dirname(__DIR__)) . 'includes/Modules/DashboardTunnel.php';
            if (class_exists('SystemDeck\\Modules\\DashboardTunnel')) {
                \SystemDeck\Modules\DashboardTunnel::init();
            }
            return; // <--- CRITICAL: STOPS EVERYTHING ELSE
        }

        // 2. Load Autoloader & Dependencies
        $this->load_dependencies();

        // 3. Initialize Core Components
        StorageEngine::init();
        Registry::instance();
        UserPreferences::init();
        AjaxHandler::init();
        HtmlAttributes::get_instance();
        Assets::init();
        RetailController::init();

        // Modules
        \SystemDeck\Modules\Renderer::init();
        \SystemDeck\Modules\WorkspaceRenderer::init();
        \SystemDeck\Modules\PinManager::init();
        \SystemDeck\Modules\SystemScreen::init();

        // ADDED: Initialize Tunnel in main flow so the page is registered
        if (class_exists('SystemDeck\\Modules\\DashboardTunnel')) {
            \SystemDeck\Modules\DashboardTunnel::init();
        }

        // 4. Hook into WordPress
        add_action('plugins_loaded', [$this, 'on_plugins_loaded']);
        add_action('init', [$this, 'on_init']);
    }

    private function load_dependencies(): void
    {
        require_once plugin_dir_path(dirname(__DIR__)) . 'includes/Autoloader.php';
        \SystemDeck\Autoloader::register();

        require_once plugin_dir_path(dirname(__DIR__)) . 'includes/functions.php';
        require_once plugin_dir_path(dirname(__DIR__)) . 'includes/Core/Defaults.php';
    }

    public function on_plugins_loaded(): void
    {
        load_plugin_textdomain('system-deck', false, dirname(dirname(plugin_basename(__FILE__))) . '/languages');
        do_action('system_deck_loaded');
    }

    public function on_init(): void
    {
        \SystemDeck\Integrations\Menu::init();
        do_action('system_deck_init');
    }
}