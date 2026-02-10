### **File: `master.md**`

# SystemDeck: Unified Architecture & Implementation Plan

## Purpose

This document defines the complete architectural blueprint for rebuilding SystemDeck as a portable, extensible, Gutenberg-aligned runtime system.

**Status:** Phase 2 Complete (Shell & Menu). Entering Phase 3 (Canvas Runtime).

It is intended to be handoff-ready for a team of developers, providing:
• Clear mental models
• Explicit file structure
• Defined contracts
• State ownership rules
• Extensibility guarantees
• Zero feature regression

---

## 1. System Identity

SystemDeck is not a dashboard plugin.

SystemDeck is a runtime shell that:
• Runs in wp-admin, frontend, FSE, iframe, responsive preview
• Hosts widgets, inspectors, grids, pins
• Persists state declaratively
• Is extensible by third parties
• Is role-aware and permission-gated

It behaves closer to Gutenberg DevTools than wp-admin widgets.

---

## 2. Core Principles (Non-Negotiable)

1.  **Single Source of Truth**
    - All UI mutations must be reflected in state.
2.  **Declarative Layout, Not Imperative DOM Mutation**
    - Movement, resize, collapse = state diffs.
3.  **Shell Isolation**
    - The **Shell** (Container) is immutable, PHP-driven, and environment-agnostic.
    - The **Canvas** (Runtime) is React-driven and state-aware.
4.  **Environment-Agnostic Runtime**
    - No `wp-ajax` dependency for the shell.
    - Works in admin, frontend, iframe, FSE.
5.  **Extensibility First**
    - Widgets, pins, workspaces, menu items are registries.
6.  **Role-Aware, Workspace-Scoped Permissions**
    - Visibility and access are enforced centrally via `systemdeck_user_can_boot`.
7.  **Zero Regression**
    - Frontend must have parity with Backend (AjaxURL, Nonces).
    - If React fails, the Shell (Menu/Dock) must still function.

---

## 3. Core Architecture: The Trinity

The system is composed of three distinct engines managed by a single bootloader.

### A. The Bootloader (`systemdeck.php`)

- **Role:** The "Big Init" & Container.
- **Responsibilities:**
    - **Permissions:** Integrated capability check.
    - **Assets:** Registers "Bridge Assets" (WP Components, Shell CSS/JS).
    - **Payload:** Generates the JSON Payload (Config + Shell HTML).
    - **Injection:** Injects the Shell into the DOM via `window.SYSTEMDECK_BOOTSTRAP`.
    - **Dynamic Styling:** Generates and injects Admin Color Scheme variables.

### B. The Navigator (`core/MenuEngine.php`)

- **Role:** The Left Brain.
- **Responsibilities:**
    - Builds the Navigation Tree (System + Workspaces).
    - Handles `sd_menu_items` filtering.
    - Renders the `<ul>` structure into the Shell's `<aside>`.

### C. The Constructor (`core/CanvasEngine.php`)

- **Status:** _Pending Phase 3_
- **Role:** The Right Brain (Runtime).
- **Responsibilities:**
    - Wakes up the System (loads `core/Autoloader.php`).
    - Prepares the Stage (`<div id="sd-react-root">`).
    - Hydrates State (`window.SYSTEMDECK_STATE`).
    - Loads React (`build/systemdeck.js`).

---

## 4. File & Module Structure

```text
systemdeck/
├── assets/                     # Shell Assets (Immutable)
│   ├── systemdeck-shell.css    # Layout, Docking, Variables (THE LAW)
│   └── systemdeck-shell.js     # Toggle, Resizing, UI Logic
│
├── build/                      # Compiled React Runtime (Future)
│   ├── systemdeck.js
│   └── systemdeck.css
│
├── core/                       # Logic Engines
│   ├── MenuEngine.php          # Navigation Logic
│   ├── CanvasEngine.php        # (Phase 3) Runtime Bridge
│   └── Autoloader.php          # (Phase 3) PSR-4 Loader
│
├── php/                        # Data & Helpers
│   ├── bootstrap.php           # Payload Generators & Workspace Reg
│   ├── permissions.php         # Capability Gates (Legacy Support)
│   └── storage.php             # Persistence Handlers
│
├── systemdeck.php              # Main Bootloader (Init, Perms, Payload)
├── master.md                   # This Document (Source of Truth)
└── CHANGELOG.md                # Audit Trail (Immutable)

```

---

## 5. The Data Flow (Payload Strategy)

1. **WP Init:** `systemdeck.php` runs `SystemDeck_Assets::run()`. Checks permissions.
2. **Asset Reg:** Registers Shell CSS/JS.
3. **Payload Build:**

- `MenuEngine` renders HTML.
- `systemdeck.php` captures HTML buffer.
- Config (Nonce, AjaxURL, User Info) is added.

4. **Injection:** `window.SYSTEMDECK_BOOTSTRAP` is injected into `<head>` via `wp_add_inline_script`.
5. **Client Boot:**

- `systemdeck-shell.js` (via inline injector) reads Payload.
- Injects Shell HTML into `<body>`.
- _Phase 3:_ React Runtime mounts to `#sd-react-root`.

---

## 6. Theme & CSS Variables (The Law)

The SystemDeck UI is strictly themed via CSS variables defined in `assets/systemdeck-shell.css` and injected dynamically by `systemdeck.php`.

**Hardcoded hex codes for UI elements are prohibited.**

**Core Variables:**
| Variable | Description |
| :--- | :--- |
| `--sd-menu-background` | Main Sidebar Background |
| `--sd-menu-text` | Primary Text Color |
| `--sd-menu-highlight-background` | Hover/Active Background |
| `--sd-menu-highlight-text` | Hover/Active Text |
| `--sd-menu-submenu-background` | Submenu/Dropdown Background |
| `--sd-menu-icon` | Dashicon Color |
| `--sd-link` | Link Color |
| `--sd-font-family` | System Font Stack |
| `--sd-header-h` | Header Height (40px) |
| `--sd-sidebar-w` | Sidebar Width (400px) |

**Usage Rule:**
Always use `var(--sd-variable-name)` in CSS.

---

## 7. State Architecture (Critical)

### 7.1 Store Shape

```js
store = {
	session: {},
	user: {},
	workspaces: {},
	layouts: {},
	widgets: {},
	pins: {},
	ui: {},
}
```

All UI actions dispatch actions, never mutate DOM directly.

### 7.2 Workspace Model

Workspaces are permission-gated, can be hidden/denied, and can be registered by 3rd parties via `systemdeck_register_core_workspaces`.

---

## 8. Protocols

### The "Append-Only" Implementation Plan

- **Protocol:** The Implementation Plan is a living "virtual workflow." NEVER delete completed sections.
- **Action:** When a phase is complete, mark it clearly (e.g., [COMPLETE]), but leave the text intact.

### The Immutable Changelog

- **File:** `CHANGELOG.md`
- **Format:** [Keep a Changelog](https://keepachangelog.com/en/1.0.0/)
- **Rule:** Every architectural change, file movement, or new feature MUST be logged.
- **Immutability:** Never delete past entries. We must be able to audit the decision-making process by reading the changelog from top to bottom.

### Zero Regression

1. **Never break the Shell:** If React fails, the Menu and Dock must still work.
2. **Frontend Parity:** `ajaxurl` and Nonces must be provided to the frontend via the Payload.
3. **Strict Namespacing:** PHP classes live in `SystemDeck\Core\`.

### Internationalization Protocol

1. **Rule:** All user-facing text MUST be wrapped in internationalization functions (`__`, `_e`, `esc_attr_e`, `esc_html__`).
2. **Text Domain:** `systemdeck`
3. **Automation:** We use a custom build script to generate the `.pot` template.

#### Updating Translations

When new strings are added to the codebase, run the following command to update the master template:

```bash
php devtools/build-pot.php
```

This will regenerate `languages/systemdeck.pot`.
