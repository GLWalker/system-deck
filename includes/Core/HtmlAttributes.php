<?php
/**
 * HTML Attributes Manager
 *
 * @package SystemDeck
 */

declare(strict_types=1);

namespace SystemDeck\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manages HTML attributes for various elements.
 */
class HtmlAttributes
{
    /**
     * Instance.
     *
     * @var HtmlAttributes|null
     */
    private static $instance;

    /**
     * Get instance.
     *
     * @return HtmlAttributes
     */
    public static function get_instance(): HtmlAttributes
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_filter('sd_parse_attr', [$this, 'parse_attributes'], 10, 2);
    }

    /**
     * Output attributes string.
     *
     * @param string $context The context (e.g. 'system-deck').
     * @param array  $attributes Optional default attributes.
     */
    public static function do_attributes(string $context, array $attributes = []): void
    {
        echo self::get_attributes($context, $attributes);
    }

    /**
     * Get attributes string.
     *
     * @param string $context The context.
     * @param array  $attributes Optional default attributes.
     * @return string
     */
    public static function get_attributes(string $context, array $attributes = []): string
    {
        $attributes = apply_filters('sd_parse_attr', $attributes, $context);

        $out = '';
        foreach ($attributes as $name => $value) {
            if ($value === true) {
                $out .= esc_attr($name) . ' ';
            } elseif ($value !== false && $value !== null) {
                $out .= esc_attr($name) . '="' . esc_attr((string)$value) . '" ';
            }
        }

        return trim($out);
    }

    /**
     * Filter callback to modify attributes based on context.
     *
     * @param array  $attributes Attributes.
     * @param string $context    Context.
     * @return array
     */
    public function parse_attributes(array $attributes, string $context): array
    {
        switch ($context) {
            case 'system-deck':
                return $this->system_deck_attributes($attributes);
            case 'header':
                return $this->header_attributes($attributes);
            case 'workspace':
                return $this->workspace_attributes($attributes);
        }

        return $attributes;
    }

    /**
     * Attributes for main SystemDeck container.
     */
    private function system_deck_attributes(array $attributes): array
    {
        // 1. Base ID / Role
        $attributes['id'] = 'systemdeck';
        $attributes['role'] = 'dialog';
        $attributes['aria-hidden'] = 'false';

        // 2. Classes
        if (!isset($attributes['class'])) {
            $attributes['class'] = '';
        }

        // Get user's admin color scheme
        $scheme = get_user_option('admin_color');
        if (empty($scheme)) {
            $scheme = 'fresh';
        }

        // Standard WP classes
        $classes = ['js','sd-drawer-hidden', 'wp-core-ui', 'admin-color-' . $scheme];

        // Context Detection (Viewport Intelligence)
        if (is_admin()) {
            $classes[] = 'sd-context-admin';
            $attributes['data-context'] = 'admin';
        } else {
            $classes[] = 'sd-context-retail';
            $attributes['data-context'] = 'retail';
        }

        // Dock State
        $dock_state = 'standard-dock';
        if (class_exists('\SystemDeck\Core\UserPreferences')) {
            $dock_state = UserPreferences::get_default_dock();
        }
        $classes[] = $dock_state;

        // Incognito
        if (class_exists('\SystemDeck\Core\UserPreferences') && UserPreferences::is_incognito_active()) {
            $classes[] = 'incognito';
        }

        $attributes['class'] .= ' ' . implode(' ', $classes);

        // 3. Data Attributes
        $attributes['data-initial-theme'] = 'light';
        $attributes['data-theme'] = 'light';
        $attributes['data-default-dock'] = $dock_state;

        return $attributes;
    }

    /**
     * Attributes for Header.
     */
    private function header_attributes(array $attributes): array
    {
        $attributes['id'] = 'sd-header-bar';
        $attributes['class'] = 'nojq';
        return $attributes;
    }

    /**
     * Attributes for Workspace (Content Area).
     */
    private function workspace_attributes(array $attributes): array
    {
        $attributes['id'] = 'sd-workspace-content';
        return $attributes;
    }
}

/**
 * Helper function to output attributes.
 *
 * @param string $context
 * @param array  $attributes
 */
function sd_attr(string $context, array $attributes = []): void
{
    HtmlAttributes::do_attributes($context, $attributes);
}
