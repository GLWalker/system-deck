<?php
/**
 * Plugin Name: SystemDeck
 * Description: A SMART workspace drawer (Clean Framework).
 * Version: 1.4.0
 * Author: GL Walker
 * Text Domain: system-deck
 * Requires PHP: 8.0
 */

declare(strict_types=1);

namespace SystemDeck;

if (!defined('ABSPATH')) {
    exit;
}

// Define Constants
define('SD_VERSION', '1.4.0');
define('SD_PATH', plugin_dir_path(__FILE__));
define('SD_URL', plugin_dir_url(__FILE__));

// Autoload Kernel
if (file_exists(SD_PATH . 'includes/Autoloader.php')) {
	require_once SD_PATH . 'includes/Autoloader.php';
	\SystemDeck\Autoloader::register();
	\SystemDeck\Core\Boot::instance()->init();
} else {
	// Fallback error handler if autoloader is missing
	add_action('admin_notices', function () {
		echo '<div class="notice notice-error"><p>SystemDeck: Autoloader missing. Please reinstall.</p></div>';
	});
}
