=== SystemDeck ===
Contributors: GL Walker
Tags: admin, workspace, performance, developer-tools
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 1.1.7
License: GPLv2 or later

A high-performance, context-aware administrative workspace engine.

== Description ==

SystemDeck is a next-generation admin workspace that provides a "context-resolver" engine, allowing stateful UI persistence across different viewports, templates, and post types. It utilizes a custom-table architecture and a transient write-buffer to ensure zero impact on frontend performance while delivering high-fidelity developer tools.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/system-deck` directory.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Access the SystemDeck drawer via the Admin Bar or the configured toggle.

== Changelog ==

= 1.1.7 =
* Shifted to high-performance custom table architecture (StorageEngine).
* Implemented Context Signature resolution (Cascading State).
* Added Telemetry Harvester for theme.json design tokens.
* Implemented Transient Write Buffer for high-frequency UI updates.
* Added persistent widget width and collapse controls.
* Removed legacy Locker.php in favor of strict intent-based routing.

= 1.0.0 =
* Initial public release.
