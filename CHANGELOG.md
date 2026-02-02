# Changelog - SystemDeck

All notable changes to this project will be documented in this file.

## [1.6.0] - 2026-02-02

### Added

- **Inspector HUD (Retail/Visual Mode)**: Full React port of the FSE Forensic Sidebar to the frontend.
- **Forensic Engine**: Enhanced `sd-inspector-engine.js` with deep `theme.json` telemetry correlation for design tokens (palette, typography, spacing).
- **High-Fidelity Breadcrumbs**: Added document ancestry navigation (up to `html/body`) with live re-selection logic.
- **Unified Controls**: Integrated "Export Variation" tool and Dashboard launcher directly into the Visual Mode workspace.
- **Telemetry Mirroring**: Automatic localization of site telemetry to the frontend for 1:1 parity with the Site Editor.
- **Contextual Awareness**: Automatic detection and highlighting of global overrides (Links, Buttons, Headings).

### Changed

- **Visual Mode Architecture**: Moved floating toolbar out of the workspace container to escape stacking traps, setting a persistent `z-index: 10,000,001`.
- **UI Streamlining**: Removed redundant dividers and Dashboard buttons for a more industrial, compact toolbar aesthetic.
- **Z-Index Governance**: Standardized all workspace layering to respect the `--sd-z-drawer` system token.
- **Noise Filtering**: Scrubbed internal utility classes (`.sd-ghost-*`) from diagnostic outputs.
- **Dynamic Activation**: Implemented `sd_shell_loaded` event listening to ensure Visual Mode triggers persist across Dashboard refreshes.

### Fixed

- **Link Interception**: Resolved navigation collisions when selecting links during active inspection.
- **Toolbar Visibility**: Fixed "stacked hidden" bug by unbinding the toolbar from the workspace DOM.

## [1.1.7] - 2026-01-30

### Added

- **StorageEngine**: Major architectural pivot to high-performance custom MySQL tables.
- **Context Resolution**: Implemented Cascading Inheritance state logic (Post > Template > Global).
- **Write Buffer**: Implemented Transient-based buffering to reduce DB thrashing on UI events.
- **Telemetry Harvester**: Demand-driven `theme.json` parser for design token snapshots.
- **UI State Persistence**: Added persistent support for widget widths and header collapse states.
- **Context Signature**: Standardized `Context` object for multi-dimensional state keys.

### Changed

- **Registry**: Updated to resolve layouts and pins through the new `StorageEngine`.
- **AjaxHandler**: Unified all save/get operations under strict intent-based routing.
- **Boot**: Updated boot sequence to handle automated schema provisioning.

### Removed

- **Locker.php**: Deleted legacy meta-base persistence module.

## [1.0.0] - 2026-01-15

### Added

- Initial release with React-based workspace drawer.
- Support for WP Dashboard widget tunneling.
- Basic drag-and-drop support.
