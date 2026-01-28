<?php
/**
 * PSR-4 Autoloader for SystemDeck
 *
 * Automatically loads classes based on namespace and file structure.
 *
 * @package SystemDeck
 */

declare(strict_types=1);

namespace SystemDeck;

if (!defined('ABSPATH')) {
	exit;
}

final class Autoloader
{
	/**
	 * Base directory for autoloading.
	 */
	private string $base_dir;

	/**
	 * Namespace prefix.
	 */
	private string $namespace_prefix = 'SystemDeck\\';

	/**
	 * Register the autoloader.
	 */
	public static function register(): void
	{
		spl_autoload_register([new self(), 'autoload']);
	}

	/**
	 * Constructor.
	 */
	private function __construct()
	{
		$this->base_dir = __DIR__ . '/';
	}

	/**
	 * Autoload classes.
	 *
	 * @param string $class Fully qualified class name.
	 */
	private function autoload(string $class): void
	{
		// Check if class uses our namespace
		if (strpos($class, $this->namespace_prefix) !== 0) {
			return;
		}

		// Remove namespace prefix
		$relative_class = substr($class, strlen($this->namespace_prefix));

		// Convert namespace separators to directory separators
		$file = $this->base_dir . str_replace('\\', '/', $relative_class) . '.php';

		// Load file if it exists
		if (file_exists($file)) {
			require_once $file;
		}
	}
}
