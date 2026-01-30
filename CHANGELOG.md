# Changelog - SystemDeck

All notable changes to this project will be documented in this file.

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
