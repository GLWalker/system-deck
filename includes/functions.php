<?php
/**
 * SystemDeck Helper Functions
 * One-stop shop for third-party developers to register their components.
 *
 * @package SystemDeck
 */

if (!defined('ABSPATH')) {
    exit;
}

use SystemDeck\Core\Registry;

/**
 * Register a Custom Workspace.
 *
 * @param string $id Unique ID.
 * @param array $args Configuration arguments.
 */
function sd_register_workspace(string $id, array $args): void
{
    Registry::instance()->register_workspace($id, $args);
}

/**
 * Register a Custom Widget.
 *
 * @param string $id Unique ID.
 * @param array $args Configuration arguments.
 */
function sd_register_widget(string $id, array $args): void
{
    Registry::instance()->register_widget($id, $args);
}

/**
 * Register a Pin Item.
 *
 * @param string $id Unique ID.
 * @param array $args Configuration arguments.
 */
function sd_register_pin_item(string $id, array $args): void
{
    Registry::instance()->register_pin_item($id, $args);
}
