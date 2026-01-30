<?php
/**
 * SystemDeck User Preferences
 * Manages user-specific settings like Incognito Mode.
 *
 * @package SystemDeck
 */

declare(strict_types=1);

namespace SystemDeck\Core;

if (!defined('ABSPATH')) {
    exit;
}

class UserPreferences
{
    /**
     * Initialize User Preferences.
     */
    public static function init(): void
    {
        // Add field to user profile
        add_action('show_user_profile', [self::class, 'render_profile_fields']);
        add_action('edit_user_profile', [self::class, 'render_profile_fields']);

        // Save fields
        add_action('personal_options_update', [self::class, 'save_profile_fields']);
        add_action('edit_user_profile_update', [self::class, 'save_profile_fields']);
    }

    /**
     * Render the profile fields.
     */
    public static function render_profile_fields(\WP_User $user): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $incognito = get_user_meta($user->ID, 'sd_incognito_mode', true) === 'true';
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">SystemDeck</th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php _e('SystemDeck Settings', 'system-deck'); ?></span></legend>
                        <label for="sd_incognito_mode">
                            <input name="sd_incognito_mode" type="checkbox" id="sd_incognito_mode" value="true" <?php checked($incognito); ?>>
                            <?php _e('Enable Incognito Mode (Dock fades out when minimized)', 'system-deck'); ?>
                        </label>
                        <br><br>
                        <label for="sd_default_dock">
                            <?php _e('Default Dock State:', 'system-deck'); ?>
                            <select name="sd_default_dock" id="sd_default_dock">
                                <?php
                                $default_dock = get_user_meta($user->ID, 'sd_default_dock', true) ?: 'standard-dock';
                                $options = [
                                    'standard-dock'    => 'Standard Dock (Default)',
                                    'full-dock'        => 'Full Screen',
                                    'left-dock'        => 'Left Side',
                                    'right-dock'       => 'Right Side',
                                    'base-dock'        => 'Base Dock (Bottom)',
                                    'left-base-dock'   => 'Base Dock (Left)',
                                    'right-base-dock'  => 'Base Dock (Right)',
                                    'min-dock'         => 'Min Dock (Circle)',
                                ];
                                foreach ($options as $value => $label) {
                                    echo sprintf('<option value="%s" %s>%s</option>', esc_attr($value), selected($default_dock, $value, false), esc_html($label));
                                }
                                ?>
                            </select>
                        </label>
                    </fieldset>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save the profile fields.
     */
    public static function save_profile_fields(int $user_id): void
    {
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }

        if (isset($_POST['sd_incognito_mode']) && $_POST['sd_incognito_mode'] === 'true') {
            update_user_meta($user_id, 'sd_incognito_mode', 'true');
        } else {
            delete_user_meta($user_id, 'sd_incognito_mode');
        }

        if (isset($_POST['sd_default_dock'])) {
            $allowed = [
                'standard-dock',
                'full-dock',
                'left-dock',
                'right-dock',
                'base-dock',
                'left-base-dock',
                'right-base-dock',
                'min-dock'
            ];
            if (in_array($_POST['sd_default_dock'], $allowed, true)) {
                update_user_meta($user_id, 'sd_default_dock', sanitize_text_field($_POST['sd_default_dock']));
            }
        }
    }

    /**
     * Check if Incognito Mode is enabled for the current user.
     */
    public static function is_incognito_active(): bool
    {
        if (!is_user_logged_in()) {
            return false;
        }
        return get_user_meta(get_current_user_id(), 'sd_incognito_mode', true) === 'true';
    }

    /**
     * Get the default dock state for the current user.
     */
    public static function get_default_dock(): string
    {
        if (!is_user_logged_in()) {
            return 'standard-dock';
        }
        return get_user_meta(get_current_user_id(), 'sd_default_dock', true) ?: 'standard-dock';
    }
}
