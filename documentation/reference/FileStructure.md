# File Structure & Organization

Complete guide to SystemDeck's code organization, file naming conventions, and loading order.

## Table of Contents

1. [Directory Structure](#directory-structure)
2. [CSS Architecture](#css-architecture)
3. [JavaScript Files](#javascript-files)
4. [PHP Class Organization](#php-class-organization)
5. [Asset Loading Order](#asset-loading-order)
6. [Module System](#module-system)

---

## Directory Structure

```
system-deck/
├── assets/
│   ├── css/
│   │   ├── sd-core.css           # Framework & layout (LOCKED)
│   │   ├── sd-general.css        # UI components & styling
│   │   ├── sd-menumain.css       # Menu & navigation
│   │   └── sd-wpcolors.css       # WordPress UI bridge
│   └── js/
│       ├── sd-deck.js            # Core SystemDeck API
│       ├── sd-grid.js            # Widget grid & sortables
│       └── sd-workspace.js       # React workspace (pins)
├── includes/
│   ├── Core/
│   │   ├── Assets.php            # CSS/JS enqueuing & caching
│   │   ├── Defaults.php          # Default workspace & widgets
│   │   ├── Registry.php          # Module registration
│   │   └── UserPreferences.php   # State management
│   ├── Modules/
│   │   ├── HealthBridge.php      # WP Site Health integration
│   │   ├── IFrameEngine.php      # Responsive preview engine
│   │   ├── IFrameInspector.php   # Grid overlay inspector
│   │   └── Notes.php             # Notes widget
│   └── Utils/
│       └── Color.php             # Color utility functions
├── documentation/
│   ├── core/
│   ├── reference/
│   ├── hooks/
│   └── blueprints/
├── system-deck.php               # Main plugin file
└── README.md                     # Plugin overview
```

---

## CSS Architecture

### The 4-File Production System

SystemDeck uses a specialized 4-file CSS architecture for production, with color schemes dynamically injected via PHP.

#### Production Files (Loaded)

1. **sd-core.css** - Framework (LOCKED)
2. **sd-general.css** - UI Components
3. **sd-menumain.css** - Navigation
4. **sd-wpcolors.css** - WordPress UI Bridge

#### Reference Files (Not Loaded)

-   **[ColorSchemes_Reference.css](file:///Users/glwalker/DevKinsta/public/thedrawer/wp-content/plugins/system-deck/documentation/reference/ColorSchemes_Reference.css)** - Located in documentation/reference/, contains all 21 color scheme definitions for reference only. Not loaded in production; schemes are dynamically injected via PHP.

---

---

#### 1. **sd-core.css** - Framework (LOCKED)

**Purpose:** Core layout, positioning, and physics

**Contains:**

-   CSS variable definitions (dimensions, z-index, transitions)
-   Scoped resets
-   System framework (flexbox structure)
-   Docking system (all dock modes)
-   Resize handles
-   Minimal workspace structure

**Edit Policy:** 🔒 **LOCKED** - Only modify when changing core mechanics

**Example:**

```css
#systemdeck {
	--sd-header-h: 40px;
	--sd-sidebar-w: 400px;
	display: flex;
	flex-direction: column;
	position: fixed;
	bottom: 0;
	/* ... */
}
```

---

#### 2. **sd-general.css** - UI Components

**Purpose:** Visual styling and theming

**Contains:**

-   Typography
-   Buttons & controls
-   Grid & sortable styles
-   Dark mode theme
-   Widget: Notes

**Edit Policy:** ✅ Open for design iteration

**Sections:**

1. Basic Elements & Typography
2. Buttons & Controls
3. Grid & Sortable Styles
4. Full Dock Styles
5. Theme: Dark Mode
6. Widget: Notes

---

#### 3. **sd-menumain.css** - Navigation

**Purpose:** Menu and navigation styling

**Contains:**

-   Header bar
-   Sidebar menu (expanded/collapsed)
-   Submenu flyouts
-   Current/active states
-   Triangle pointer
-   Icons
-   Collapse button
-   Folded state
-   RTL overrides

**Sections:** 11 major sections

**Key Feature:** Syncs with WordPress admin color schemes

---

#### 4. **sd-wpcolors.css** - WordPress UI Bridge

**Purpose:** WordPress-compatible UI components

**Contains:**

-   Buttons matching `wp-core-ui`
-   Form elements (inputs, checkboxes, radios)
-   Notices & bubbles
-   List tables

**Key Feature:** Makes SystemDeck feel native to WordPress

**Example:**

```css
#systemdeck .sd-button:focus {
	box-shadow: 0 0 0 1px var(--wp--preset--color--white), 0 0 0 3px var(--sd-highlight-color);
}
```

---

### CSS Loading Order

```
1. sd-core.css       (Foundation)
2. [Inline Styles]   (Dynamic CSS, user color schemes)
3. sd-general.css    (UI layer)
4. sd-menumain.css   (Navigation)
5. sd-wpcolors.css   (WP bridge)
```

**Dependency Chain:**

```
sd-core.css (base variables)
    ↓
[Dynamic inline CSS] (21 color schemes, PHP-injected)
    ↓
sd-general.css (uses variables)
sd-menumain.css (uses variables)
sd-wpcolors.css (uses variables)
```

---

## JavaScript Files

### 1. **sd-deck.js** - Core API

**Purpose:** SystemDeck JavaScript API and core functionality

**Responsibilities:**

-   Theme toggling (light/dark)
-   Dock state management
-   Resize handling
-   Admin bar height detection
-   State persistence (localStorage)
-   Velocity snapping

**Global API:**

```javascript
window.SystemDeck = {
    init(),
    setTheme(theme),
    setDockState(dockClass),
    Resize: {
        init(),
        startResize(e),
        onMove(e),
        onEnd(e)
    }
}
```

**Dependencies:** jQuery, jQuery UI Resizable

---

### 2. **sd-grid.js** - Widget Grid

**Purpose:** Widget grid, sortable, and pin management

**Responsibilities:**

-   Render widgets from manifest
-   Initialize jQuery UI Sortables
-   Save layout changes
-   Pin/unpin widgets
-   Trigger events

**Global API:**

```javascript
SystemDeckGrid = {
    init(),
    renderWidgets(),
    initSortables(),
    saveLayout(),
    bindPinActions()
}
```

**Dependencies:** jQuery, jQuery UI Sortable, wp-api-fetch

**Events:**

```javascript
$(document).trigger("sd_pins_updated", [newPins])
```

---

### 3. **sd-workspace.js** - React Components

**Purpose:** React-based workspace components

**Responsibilities:**

-   Pin Ribbon component
-   State synchronization
-   Event listening

**Technology:** React (wp-element)

**Components:**

```javascript
PinRibbon() // Displays pinned widgets
```

**Dependencies:** wp-element, wp-i18n, jQuery

---

### JS Loading Order

```
1. jQuery               (WordPress core)
2. jQuery UI Sortable   (WordPress core)
3. wp-element           (WordPress core, for React)
4. sd-deck.js           (SystemDeck core)
5. sd-workspace.js      (React components)
6. sd-grid.js           (Widget system)
```

---

## PHP Class Organization

### Namespace Structure

```
SystemDeck\
├── Core\
│   ├── Assets
│   ├── Registry
│   └── UserPreferences
├── Modules\
│   ├── HealthBridge
│   ├── IFrameEngine
│   ├── IFrameInspector
│   └── Notes
└── Utils\
    └── Color
```

### Core Classes

#### **Assets.php**

```php
namespace SystemDeck\Core;

class Assets {
    public static function init(): void
    public static function enqueue_admin_assets(): void
    public static function get_dynamic_css(): string
    private static function get_wp_color_variables(string $scheme): array
}
```

**Responsibilities:**

-   Enqueue CSS/JS files
-   Generate dynamic user-specific CSS
-   Cache management
-   Color scheme variable injection

---

#### **Registry.php**

```php
namespace SystemDeck\Core;

class Registry {
    public static function init(): void
    public static function register_workspace(string $id, array $args): void
    public static function register_widget(string $id, array $args): void
    public static function get_workspaces(): array
    public static function get_widgets(): array
}
```

**Responsibilities:**

-   Workspace registration
-   Widget registration
-   Centralized data store

---

#### **UserPreferences.php**

```php
namespace SystemDeck\Core;

class UserPreferences {
    public static function init(): void
    public static function get_state(int $user_id, string $workspace_id): array
    public static function save_state(int $user_id, string $workspace_id, array $data): bool
}
```

**Responsibilities:**

-   Load/save user preferences
-   Dock state
-   Widget layout
-   Pinned items

---

### Module Pattern

All modules follow this structure:

```php
namespace SystemDeck\Modules;

class ModuleName {
    public static function init(): void {
        // Hook into WordPress
        add_action('...', [self::class, 'method_name']);
    }

    public static function method_name(): void {
        // Implementation
    }
}
```

**Registration:**

```php
// In system-deck.php
SystemDeck\Modules\ModuleName::init();
```

---

## Asset Loading Order

### Full Boot Sequence

```
1. WordPress Core Loaded
    ↓
2. Plugins Loaded
    ↓
3. system-deck.php (Main file)
    ↓
4. Autoloader registered
    ↓
5. Core Classes Init
    ├── Registry::init()
    ├── Assets::init()
    └── UserPreferences::init()
    ↓
6. Modules Init
    ├── HealthBridge::init()
    ├── IFrameEngine::init()
    └── Notes::init()
    ↓
7. Action: 'system_deck_init'
    ↓
8. Defaults.php (Registers workspace/widgets)
    ↓
9. admin_enqueue_scripts / wp_enqueue_scripts
    ↓
10. Assets::enqueue_admin_assets()
    ├── Enqueue: sd-core.css
    ├── Enqueue: sd-general.css
    ├── Enqueue: sd-deck.js
    ├── Enqueue: sd-workspace.js
    ├── Enqueue: sd-grid.js
    └── Inject: Dynamic CSS (inline)
    ↓
11. Render SystemDeck Shell (wp_footer/admin_footer)
```

### Enqueue Strategy

**CSS Dependencies:**

```php
wp_enqueue_style('sd-core-css', ..., ['dashicons']);
wp_enqueue_style('sd-general-css', ..., ['sd-core-css']);
```

**JS Dependencies:**

```php
wp_enqueue_script('sd-deck-js', ..., []);
wp_enqueue_script('sd-workspace-js', ..., ['wp-element', 'wp-i18n', 'jquery']);
wp_enqueue_script('sd-grid-js', ..., ['jquery', 'jquery-ui-sortable', 'wp-api-fetch']);
```

**Inline CSS:**

```php
wp_add_inline_style('sd-core-css', Assets::get_dynamic_css());
```

---

## Module System

### Registering a New Module

**1. Create Module File:**

```php
// includes/Modules/MyModule.php
<?php
namespace SystemDeck\Modules;

class MyModule {
    public static function init(): void {
        add_action('admin_footer', [self::class, 'render']);
    }

    public static function render(): void {
        echo '<div>My Module</div>';
    }
}
```

**2. Register in Main File:**

```php
// system-deck.php
SystemDeck\Modules\MyModule::init();
```

**3. Follow Naming Conventions:**

-   File: `MyModule.php` (PascalCase)
-   Class: `MyModule` (PascalCase)
-   Namespace: `SystemDeck\Modules`

---

## File Naming Conventions

### CSS Files

-   Prefix: `sd-`
-   Format: `sd-{purpose}.css`
-   Examples: `sd-core.css`, `sd-general.css`

### JavaScript Files

-   Prefix: `sd-`
-   Format: `sd-{purpose}.js`
-   Examples: `sd-deck.js`, `sd-grid.js`

### PHP Files

-   Format: `PascalCase.php`
-   Examples: `Assets.php`, `Registry.php`

### Documentation

-   Format: `PascalCase.md`
-   Examples: `StyleArchitecture.md`, `CSSVariables.md`

---

## See Also

-   [Style Architecture](./StyleArchitecture.md) - CSS strategy details
-   [CSS Variables](./CSSVariables.md) - Variable reference
-   [Function Reference](../reference/Functions.md) - PHP API documentation
