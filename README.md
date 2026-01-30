# SystemDeck 🛰️

**SystemDeck** is a high-performance, context-aware administrative workspace engine for WordPress. It transforms the standard admin experience into a fluid, stateful environment designed for developers and power users.

## 🚀 Key Architectural Breakthroughs

### 1. The Context Resolver & Cascading State

Unlike traditional plugins that store data in a flat `usermeta` structure, SystemDeck uses a **Multi-Dimensional Context Signature**.

- **Signature**: `User` + `Workspace` + `Context Type` (Post/Template/Global) + `Viewport`.
- **Cascading Inheritance**: The system automatically resolves state by checking for specific URL overrides first, falling back to Template defaults (e.g., all "Posts"), and finally a Global workspace state.

### 2. Transient Write Buffer (Hot Storage)

To ensure zero frontend drag, SystemDeck implements a **Write Buffer**:

- **Drags/Resizes**: High-frequency UI events are written to a short-lived WordPress Transient (the "Hot Buffer").
- **Shutdown Flush**: The final application state is committed to the custom database tables only during the `shutdown` hook, reducing database writes by up to 90% during active editing.

### 3. Telemetry Harvester & Design Tokens

SystemDeck proactively crawls `theme.json` and active theme metrics to generate a **Telemetry Snapshot**.

- **Instant UI**: Design tokens (Color, Spacing, Typography) are stored as JSON blobs, allowing the UI to render with theme-native aesthetics instantly, without redundant file parsing.

## 🛠️ Tech Stack

- **Persistence**: Custom MySQL Tables (`sd_workspaces`, `sd_items`, `sd_context_state`).
- **Logic**: Strict PHP 8.0+ with autoloaded PSR-4 namespaces.
- **Frontend**: WordPress Native React Components (`@wordpress/components`).
- **Communication**: Optimized AJAX Handlers with Nonce & Capability validation.

## 📦 Installation & Setup

1. Clone the repository into `wp-content/plugins/system-deck`.
2. Activate the plugin via the WordPress Admin.
3. Database tables are automatically provisioned via `dbDelta` on activation.

## ⚖️ License

GPLv2 or later.
