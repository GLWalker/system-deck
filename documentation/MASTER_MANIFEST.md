# SystemDeck Master Operational Manifest

This manifest serves as the master logic for analyzing and extending the **SystemDeck** ecosystem.

## I. Core Framework Architecture

- **The Deck:** The persistent navigation and global control layer. Managed via `MenuEngine.php` and `sd-deck.js`.
- **The Workspace:** The active React-powered canvas. Uses `WorkspaceRenderer.php` to hydrate server-side widget callbacks into the `window.SD_Manifest` data packet.
- **The Grid System:** A granular **12-column responsive engine**. Supports widget spanning selectors and shifts dynamically from 12 columns (desktop) to 6 (tablet) and 2 (mobile).
- **Viewport Intelligence (Next Phase):** Detecting context to optimize rendering between "Retail" (frontend) and "Admin" environments.

## II. Component & Data Definition

| Component                | Definition & Responsibility                                             | Source Reference                 |
| :----------------------- | :---------------------------------------------------------------------- | :------------------------------- |
| **Workspace Components** | Native-feeling UI wrappers using `wp.components` (Card, Panel, Button). | `sd-workspace.js`, `sd-temp.css` |
| **Bulldozer (Widgets)**  | Legacy PHP widgets hydrated into HTML strings.                          | `WorkspaceRenderer.php`          |
| **Pin-items (Internal)** | Metrics or interactive elements inside a widget's HTML.                 | `TestWidget.php`, `UI.php`       |
| **Pinned-items (Grid)**  | Extracted metrics in the grid, synced with sidebar for pruning.         | `PinManager.php`                 |

## III. Persistence & Hardening Protocols

- **Isolated Workspace Metadata:** Layout and pin metadata siloed by workspace ID (e.g., `sd_layout_ROCK-N-ROLL`).
- **Active Storage Engine:** `wp_usermeta` is the current engine; `wp_sd_locker` table is staged for future scaling.
- **Save Integrity:** Pre-checks in `Locker.php` eliminate redundant calls and "false negative" errors.
- **Security Layer:** Nonce verification and `manage_options` checks on all AJAX endpoints.

## IV. Analysis Procedures

1.  **Scanning Widgets:** Identify `callback` in `Registry.php` and hydration path in `WorkspaceRenderer.php`.
2.  **Evaluating the Grid:** Check `layout` array in `window.SD_Manifest` for spanning/ordering.
3.  **Styling Audit:** Reference `sd-temp.css` (experimental) and `sd-wpcolors.css` (WP overrides).
4.  **Extensibility:** Use verified registration patterns in `Registry.php` for custom widget injection.
