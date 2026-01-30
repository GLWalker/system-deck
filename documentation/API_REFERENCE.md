# SystemDeck API Reference

**Version:** 1.1.2
**Last Updated:** January 27, 2026

## 📡 AJAX Endpoints

All AJAX endpoints are registered in `includes/Core/AjaxHandler.php` and use the WordPress AJAX API.

### Authentication

All endpoints require:

- Valid WordPress nonce: `sd_load_shell`
- User capability: `manage_options`

### Base URL

```
/wp-admin/admin-ajax.php
```

---

## 🔌 Available Endpoints

### 1. `sd_load_shell`

Load the SystemDeck shell HTML.

**Action:** `sd_load_shell`
**Method:** POST
**Handler:** `AjaxHandler::handle_load_shell()`

**Request:**

```javascript
{
  action: 'sd_load_shell',
  nonce: 'your-nonce-here'
}
```

**Response:**

```json
{
	"success": true,
	"data": {
		"html": "<div id=\"systemdeck\">...</div>"
	}
}
```

---

### 2. `sd_save_layout`

Save workspace layout configuration.

**Action:** `sd_save_layout`
**Method:** POST
**Handler:** `AjaxHandler::handle_save_layout()`

**Request:**

```javascript
{
  action: 'sd_save_layout',
  nonce: 'your-nonce-here',
  workspaceId: 'default',
  layout: '[{"id":"widget_1","type":"widget","w":6}]'
}
```

**Response:**

```json
{
	"success": true,
	"data": {
		"message": "Layout saved"
	}
}
```

---

### 3. `sd_save_pins`

Save pinned items.

**Action:** `sd_save_pins`
**Method:** POST
**Handler:** `AjaxHandler::handle_save_pins()`

**Request:**

```javascript
{
  action: 'sd_save_pins',
  nonce: 'your-nonce-here',
  workspaceId: 'default',
  pins: '[{"id":"pin_1","label":"CPU","value":"45%"}]'
}
```

**Response:**

```json
{
	"success": true,
	"data": {
		"message": "Pins saved"
	}
}
```

---

### 4. `sd_get_manifest`

Get workspace manifest with hydrated data.

**Action:** `sd_get_manifest`
**Method:** POST
**Handler:** `AjaxHandler::handle_get_manifest()`

**Request:**

```javascript
{
  action: 'sd_get_manifest',
  nonce: 'your-nonce-here',
  workspaceId: 'default'
}
```

**Response:**

```json
{
  "success": true,
  "data": {
    "slug": "default",
    "name": "Default",
    "registry": {...},
    "user": {
      "layout": [...],
      "pins": [...]
    }
  }
}
```

---

### 5. `sd_render_system_screen`

Render the System Screen (widget scanner).

**Action:** `sd_render_system_screen`
**Method:** POST
**Handler:** `AjaxHandler::handle_render_system_screen()`

**Request:**

```javascript
{
  action: 'sd_render_system_screen',
  nonce: 'your-nonce-here'
}
```

**Response:**
HTML output (not JSON)

---

### 6. `sd_render_workspace`

Render workspace HTML.

**Action:** `sd_render_workspace`
**Method:** POST
**Handler:** `AjaxHandler::handle_render_workspace()`

**Request:**

```javascript
{
  action: 'sd_render_workspace',
  nonce: 'your-nonce-here',
  name: 'My Workspace'
}
```

**Response:**

```json
{
	"success": true,
	"data": {
		"html": "<div>...</div>",
		"name": "My Workspace"
	}
}
```

---

### 7. `sd_render_widget` ⭐ NEW

Lazy-load widget content.

**Action:** `sd_render_widget`
**Method:** POST
**Handler:** `AjaxHandler::handle_render_widget()`
**Added:** January 27, 2026

**Request:**

```javascript
{
  action: 'sd_render_widget',
  nonce: 'your-nonce-here',
  widget: 'dashboard_quick_press'
}
```

**Response:**

```json
{
	"success": true,
	"data": {
		"html": "<div>Widget content...</div>",
		"content": "<div>Widget content...</div>",
		"title": "Quick Draft"
	}
}
```

**Error Response:**

```json
{
	"success": false,
	"data": "Widget not found"
}
```

---

### 8. `sd_create_workspace`

Create a new workspace.

**Action:** `sd_create_workspace`
**Method:** POST
**Handler:** `AjaxHandler::handle_create_workspace()`

**Request:**

```javascript
{
  action: 'sd_create_workspace',
  nonce: 'your-nonce-here',
  name: 'My New Workspace'
}
```

**Response:**

```json
{
	"success": true,
	"data": {
		"name": "My New Workspace"
	}
}
```

---

### 9. `sd_delete_workspace`

Delete a workspace.

**Action:** `sd_delete_workspace`
**Method:** POST
**Handler:** `AjaxHandler::handle_delete_workspace()`

**Request:**

```javascript
{
  action: 'sd_delete_workspace',
  nonce: 'your-nonce-here',
  name: 'My Workspace'
}
```

**Response:**

```json
{
	"success": true
}
```

**Note:** Cannot delete "Default" workspace.

---

### 10. `sd_get_workspaces`

Get list of all workspaces.

**Action:** `sd_get_workspaces`
**Method:** POST
**Handler:** `AjaxHandler::handle_get_workspaces()`

**Request:**

```javascript
{
  action: 'sd_get_workspaces',
  nonce: 'your-nonce-here'
}
```

**Response:**

```json
{
	"success": true,
	"data": {
		"workspaces": [
			{
				"name": "Default",
				"slug": "default",
				"created": null
			},
			{
				"name": "My Workspace",
				"slug": "my-workspace",
				"created": 1706380800
			}
		]
	}
}
```

---

### 11. `sd_refresh_menu`

Refresh sidebar menu HTML.

**Action:** `sd_refresh_menu`
**Method:** POST
**Handler:** `AjaxHandler::handle_refresh_menu()`

**Request:**

```javascript
{
  action: 'sd_refresh_menu',
  nonce: 'your-nonce-here'
}
```

**Response:**

```json
{
	"success": true,
	"data": {
		"html": "<ul>...</ul>"
	}
}
```

---

### 12. `sd_save_proxy_selection`

Save proxy widget discovery selection.

**Action:** `sd_save_proxy_selection`
**Method:** POST
**Handler:** `AjaxHandler::handle_save_proxy_selection()`

**Request:**

```javascript
{
  action: 'sd_save_proxy_selection',
  nonce: 'your-nonce-here',
  widgets: ['dashboard_quick_press', 'dashboard_primary']
}
```

**Response:**

```json
{
	"success": true
}
```

---

### 13. `sd_toggle_pin`

Toggle pin state (add/remove).

**Action:** `sd_toggle_pin`
**Method:** POST
**Handler:** `AjaxHandler::handle_toggle_pin()`

**Request:**

```javascript
{
  action: 'sd_toggle_pin',
  nonce: 'your-nonce-here',
  workspace: 'Default',
  pin_data: '{"id":"cpu_usage","label":"CPU","value":"45%"}'
}
```

**Response (Added):**

```json
{
  "success": true,
  "data": {
    "action": "added",
    "pins": [...]
  }
}
```

**Response (Removed):**

```json
{
  "success": true,
  "data": {
    "action": "removed",
    "pins": [...]
  }
}
```

---

### 14. `sd_get_notes`

Get all notes.

**Action:** `sd_get_notes`
**Method:** POST
**Handler:** `AjaxHandler::handle_get_notes()`

**Request:**

```javascript
{
  action: 'sd_get_notes',
  nonce: 'your-nonce-here'
}
```

**Response:** Handled by `Notes` widget class.

---

### 15. `sd_save_note`

Save a note.

**Action:** `sd_save_note`
**Method:** POST
**Handler:** `AjaxHandler::handle_save_note()`

**Request:**

```javascript
{
  action: 'sd_save_note',
  nonce: 'your-nonce-here',
  // Additional parameters handled by Notes widget
}
```

**Response:** Handled by `Notes` widget class.

---

### 16. `sd_delete_note`

Delete a note.

**Action:** `sd_delete_note`
**Method:** POST
**Handler:** `AjaxHandler::handle_delete_note()`

**Request:**

```javascript
{
  action: 'sd_delete_note',
  nonce: 'your-nonce-here',
  // Additional parameters handled by Notes widget
}
```

**Response:** Handled by `Notes` widget class.

---

### 17. `sd_pin_note`

Pin a note.

**Action:** `sd_pin_note`
**Method:** POST
**Handler:** `AjaxHandler::handle_pin_note()`

**Request:**

```javascript
{
  action: 'sd_pin_note',
  nonce: 'your-nonce-here',
  // Additional parameters handled by Notes widget
}
```

**Response:** Handled by `Notes` widget class.

---

## 🔐 Security

### Nonce Verification

All endpoints verify the nonce using:

```php
check_ajax_referer('sd_load_shell', 'nonce', false)
```

### Capability Check

All endpoints require:

```php
current_user_can('manage_options')
```

### Input Sanitization

All user input is sanitized:

- `sanitize_text_field()` for text
- `sanitize_key()` for keys/slugs
- `json_decode()` with validation for JSON

---

## 📦 JavaScript Helper Functions

### `apiFetch(action, data)`

Wrapper for AJAX calls.

**Location:** `assets/js/sd-workspace.js`

**Usage:**

```javascript
apiFetch("save_layout", {
	workspaceId: "default",
	layout: JSON.stringify(items),
})
	.then((data) => console.log("Success:", data))
	.catch((err) => console.error("Error:", err))
```

**Parameters:**

- `action` (string): Action name without `sd_` prefix
- `data` (object): Additional data to send

**Returns:** Promise

---

### `saveLayout(items, workspaceId)`

Save layout with debouncing.

**Location:** `assets/js/sd-workspace.js`

**Usage:**

```javascript
saveLayout(items, "default")
```

**Parameters:**

- `items` (array): Layout items
- `workspaceId` (string): Workspace identifier

---

## 🎨 React Components

### `WidgetShell`

Unified wrapper for widgets.

**Props:**

- `title` (string): Widget title
- `children` (ReactNode): Widget content
- `widthControl` (ReactNode): Width control dropdown
- `isCollapsed` (boolean): Collapse state
- `onToggle` (function): Collapse toggle handler
- `dragHandle` (ReactNode): Drag handle element
- `className` (string): Additional CSS classes

---

### `DashboardWidgetFrame`

Iframe wrapper for proxy widgets.

**Props:**

- `widgetId` (string): Widget ID to load
- `minHeight` (number): Minimum iframe height in pixels

---

### `ToolBox`

Screen Options widget list.

**Props:**

- `registry` (object): Widget registry
- `activeItems` (array): Currently active items
- `onToggle` (function): Toggle handler

---

## 🗄️ PHP Classes

### `AjaxHandler`

Centralized AJAX handler.

**Location:** `includes/Core/AjaxHandler.php`

**Methods:**

- `init()`: Register all AJAX hooks
- `verify_request()`: Security verification
- `handle_*()`: Individual endpoint handlers

---

### `Registry`

Widget registry manager.

**Location:** `includes/Core/Registry.php`

**Methods:**

- `instance()`: Get singleton instance
- `register_widget()`: Register a widget
- `get_widget($id)`: Get widget by ID
- `hydrate_manifest($workspace_id)`: Get full manifest

---

### `Locker`

State persistence manager.

**Location:** `includes/Core/Locker.php`

**Methods:**

- `save_state($user_id, $workspace_id, $key, $value)`: Save state
- `get_state($user_id, $workspace_id, $key)`: Get state

---

## 🎯 Custom Events

### JavaScript Events

**`sd_widget_toggled`**
Triggered when widget visibility changes.

```javascript
$(document).on("sd_widget_toggled", (event, widgetId, isSelected) => {
	console.log("Widget toggled:", widgetId, isSelected)
})
```

**`sd_layout_updated`**
Triggered when layout changes.

```javascript
$(document).on("sd_layout_updated", (event, items) => {
	console.log("Layout updated:", items)
})
```

**`sd_workspace_rendered`**
Triggered when workspace is rendered.

```javascript
$(document).on("sd_workspace_rendered", () => {
	console.log("Workspace rendered")
})
```

---

## 📚 Additional Resources

- [WordPress AJAX API](https://developer.wordpress.org/plugins/javascript/ajax/)
- [WordPress REST API](https://developer.wordpress.org/rest-api/)
- [React Documentation](https://react.dev/)
- [WordPress Components](https://developer.wordpress.org/block-editor/reference-guides/components/)

---

**Last Updated:** January 27, 2026
**Maintained By:** SystemDeck Development Team
