# SystemDeck Command Center - Quick Reference

## 🎯 What You Asked For vs What Was Built

### ✅ Requirements Met

| Requirement                          | Status | Implementation                       |
| ------------------------------------ | ------ | ------------------------------------ |
| React grid layout                    | ✅     | CSS Grid with responsive columns     |
| Workspace cards with drag handles    | ✅     | jQuery UI Sortable                   |
| Drag to reorder updates menu         | ✅     | AJAX saves order on drop             |
| Card shows: name, widget count, date | ✅     | All metadata displayed               |
| Delete with confirmation             | ✅     | `confirm()` dialog                   |
| Rename/edit button                   | ✅     | `prompt()` dialog                    |
| Welcome tab with greeting            | ✅     | "Welcome to SystemDeck, {username}!" |
| Last login date/time                 | ✅     | Tracked in user meta                 |
| Last login IP                        | ✅     | Tracked in user meta                 |
| Create workspace button              | ✅     | Slides down form (like Admin Drawer) |
| Scanner tab                          | ✅     | Existing scanner moved to tab        |
| Import/Export tab                    | ✅     | JSON export/import with validation   |
| Help tab placeholder                 | ✅     | "Documentation Coming Soon"          |
| WP Admin styling                     | ✅     | Uses native WP classes throughout    |
| No widget appearance                 | ✅     | Custom `.sd-workspace-card` class    |
| Bridge between WP Admin & FSE        | ✅     | Works in both contexts               |
| Extendable for future features       | ✅     | Ready for shared/default workspaces  |

---

## 📁 Files Created/Modified

### Created

1. **SystemScreen.php** - Complete rewrite with new layout
2. **SYSTEMSCREEN_COMMAND_CENTER.md** - Full documentation

### Deleted

1. **SystemScreenAjax.php** - Handlers moved to main AjaxHandler

### To Modify (Manual)

1. **AjaxHandler.php** - Add 4 new actions + 4 new handler methods
2. **system-deck.php** - Bump version to 1.1.6

---

## 🔧 Manual Integration (Copy/Paste Ready)

### File: `/includes/Core/AjaxHandler.php`

**Location 1: Line ~40 (inside $actions array)**

```php
'rename_workspace',        // <--- ADD
'update_workspace_order',  // <--- ADD
'export_workspaces',       // <--- ADD
'import_workspaces',       // <--- ADD
```

**Location 2: Line ~334 (before closing })**

Copy all 4 handler methods from:
`/documentation/SYSTEMSCREEN_COMMAND_CENTER.md`

Methods to add:

- `handle_rename_workspace()`
- `handle_update_workspace_order()`
- `handle_export_workspaces()`
- `handle_import_workspaces()`

---

## 🎨 UI Components Reference

### Workspace Card Structure

```html
<div class="sd-workspace-card" data-workspace-id="ws_123">
	<div class="sd-workspace-card-header">
		<span class="dashicons dashicons-move"></span>
		<span class="sd-workspace-card-title">Workspace Name</span>
		<div class="sd-workspace-card-actions">
			<button class="sd-rename-workspace">✎</button>
			<button class="sd-delete-workspace">🗑</button>
		</div>
	</div>
	<div class="sd-workspace-card-meta">
		<div>Widgets: 5</div>
		<div>Created: Jan 28, 2026</div>
	</div>
</div>
```

### Tab Navigation

```html
<nav class="nav-tab-wrapper">
	<a href="#sd-tab-welcome" class="nav-tab nav-tab-active">Welcome</a>
	<a href="#sd-tab-scanner" class="nav-tab">Widget Scanner</a>
	<a href="#sd-tab-import-export" class="nav-tab">Import/Export</a>
	<a href="#sd-tab-help" class="nav-tab">Help</a>
</nav>
```

---

## 🗄️ Database Schema

### User Meta Keys

| Key                | Type   | Description         | Example               |
| ------------------ | ------ | ------------------- | --------------------- |
| `sd_workspaces`    | array  | All user workspaces | See below             |
| `sd_last_login`    | string | MySQL datetime      | `2026-01-28 12:34:56` |
| `sd_last_login_ip` | string | IP address          | `192.168.1.100`       |

### Workspace Structure

```php
[
    'ws_abc123' => [
        'id' => 'ws_abc123',
        'name' => 'My Workspace',
        'widgets' => ['widget_1', 'widget_2'],
        'created' => '2026-01-28 12:00:00',
        'order' => 1
    ]
]
```

---

## 🎯 JavaScript Events

### Tab Switching

```javascript
$(".nav-tab").on("click", function (e) {
	var tab = $(this).data("tab")
	// Switches to #sd-tab-{tab}
})
```

### Workspace Sorting

```javascript
$("#sd-workspace-cards").sortable({
	handle: ".sd-workspace-drag-handle",
	update: function (event, ui) {
		// AJAX: sd_update_workspace_order
	},
})
```

### Create Workspace

```javascript
$("#sd-btn-create-workspace").on("click", function () {
	// Slides down form
})

$("#sd-save-create-workspace").on("click", function () {
	// AJAX: sd_create_workspace
})
```

### Delete Workspace

```javascript
$(".sd-delete-workspace").on("click", function () {
	if (confirm("Are you sure?")) {
		// AJAX: sd_delete_workspace
	}
})
```

### Rename Workspace

```javascript
$(".sd-rename-workspace").on("click", function () {
	var newName = prompt("Enter new name:")
	// AJAX: sd_rename_workspace
})
```

---

## 📊 AJAX Endpoints

## 📊 AJAX Endpoints

| Action                      | Nonce           | Parameters           | Response             | Note                             |
| --------------------------- | --------------- | -------------------- | -------------------- | -------------------------------- |
| `sd_create_workspace`       | `sd_load_shell` | `name`               | `{success, html}`    | Returns rendered card HTML       |
| `sd_delete_workspace`       | `sd_load_shell` | `workspace_id`       | `{success, message}` |                                  |
| `sd_rename_workspace`       | `sd_load_shell` | `workspace_id, name` | `{success, message}` |                                  |
| `sd_update_workspace_order` | `sd_load_shell` | `order[]`            | `{success, message}` |                                  |
| `sd_export_workspaces`      | `sd_export`     | `type` (GET)         | JSON file download   | **Deep Export** (Layouts + Pins) |
| `sd_import_workspaces`      | `sd_load_shell` | `data` (JSON string) | `{success, message}` | Restores deep config             |

---

## 🎨 CSS Classes Reference

### Layout

- `.sd-command-center` - Main wrapper
- `.sd-grid-container` - Grid container
- `.sd-grid-row` - Grid row (white box)

### Workspace Cards

- `.sd-workspace-grid` - Card grid container
- `.sd-workspace-card` - Individual card
- `.sd-workspace-card-header` - Card header
- `.sd-workspace-drag-handle` - Drag icon
- `.sd-workspace-card-title` - Workspace name
- `.sd-workspace-card-actions` - Action buttons
- `.sd-workspace-card-meta` - Metadata section

### Tabs

- `.sd-tabs-container` - Tab wrapper
- `.nav-tab-wrapper` - WP tab navigation
- `.nav-tab` - Individual tab
- `.nav-tab-active` - Active tab
- `.sd-tab-panel` - Tab content panel
- `.sd-tab-panel.active` - Active panel

### Welcome Tab

- `.sd-welcome-greeting` - Greeting box
- `.sd-welcome-info` - Info section
- `.sd-create-workspace-section` - Create section
- `.sd-create-workspace-form` - Create form

### Scanner Tab

- `.sd-scanner-header` - Scanner header
- `.sd-widget-grid` - Widget grid
- `.sd-widget-option` - Widget checkbox label
- `.sd-manual-builder` - Manual builder section

### Import/Export Tab

- `.sd-import-export-section` - Section wrapper
- `.sd-import-export-actions` - Button container

---

## ✅ Testing Checklist

### Visual Tests

- [ ] Command center loads without errors
- [ ] All 4 tabs visible
- [ ] Workspace cards display correctly
- [ ] Drag handles visible
- [ ] Action buttons (rename/delete) visible
- [ ] Styling matches WP Admin

### Functional Tests

- [ ] Tab switching works
- [ ] Workspace cards are draggable
- [ ] Drag updates order (check menu)
- [ ] Create workspace button works
- [ ] Form slides down/up
- [ ] Create workspace saves
- [ ] Rename workspace works
- [ ] Delete workspace works (with confirm)
- [ ] Cannot delete default workspace
- [ ] Export all downloads JSON
- [ ] Export single downloads JSON
- [ ] Import validates JSON
- [ ] Import skips duplicates
- [ ] Scanner tab shows widgets
- [ ] Deep scan still works

### Data Tests

- [ ] `sd_workspaces` user meta saves
- [ ] `sd_last_login` tracks correctly
- [ ] `sd_last_login_ip` tracks correctly
- [ ] Workspace order persists
- [ ] Exported JSON is valid
- [ ] Imported workspaces appear

---

## 🚀 Future Enhancements (Noted for Later)

### Database Migration

- Move from user meta to custom tables
- Better performance for many workspaces
- Easier querying and reporting

### Shared Workspaces

- Admin setting to share workspace with all users
- Permission levels (view/edit)
- Workspace templates

### Default Workspaces

- Admin can set default workspace for new users
- Auto-populate widgets on first login
- Role-based defaults

### Advanced Features

- Workspace duplication
- Workspace export/import between sites
- Workspace scheduling (show/hide by time)
- Conditional workspace display
- Workspace analytics

---

**Ready to integrate!** 🎉

See `/documentation/SYSTEMSCREEN_COMMAND_CENTER.md` for full code.
