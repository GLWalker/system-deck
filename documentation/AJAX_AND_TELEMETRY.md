# SystemDeck Ajax & Telemetry Guide

This document outlines the recent overhaul of the Ajax handling system, the architecture of the Telemetry engine, and provides a reference for developers on how to access system data.

---

## 1. Ajax Functionality Overhaul

Overview of the structural changes made to improve stability, security, and developer experience.

### Centralization Strategy (`AjaxHandler.php`)

Previously, individual widgets (like Notes or Renderer) registered their own `wp_ajax_` hooks scattershot throughout the codebase. This led to race conditions, duplicate handlers, and "Loading..." hangs if a module wasn't fully initialized.

We have centralized **all** Ajax communication into a single controller: `SystemDeck\Core\AjaxHandler`.

- **Single Entry Point**: All `wp_ajax_sd_*` actions are now registered in `AjaxHandler::init()`.
- **Unified Security**: A single `verify_request()` method enforces Nonce verification (`sd_load_shell`) and Capability checks (`manage_options`) for every request.
- **Clean Room Support**: This architecture allows us to serve data even when the dashboard UI is largely suppressed (e.g., inside the Dashboard Tunnel).

### Key Changes

- **Removed** ad-hoc `add_action('wp_ajax_...')` calls from `Renderer.php` and `Notes.php`.
- **Added** explicit handlers for `get_telemetry`, `save_widget_data`, and `get_widget_data` to support 3rd-party integrations.
- **Enhanced** `create_workspace` to accept a `layout` payload, allowing for "Hydrated Workspace Creation" in a single step.
- **Identity Resolution**: Fixed issues where Workspace IDs were returned instead of Human Readable Names in headers.

---

## 2. The Telemetry File (`Telemetry.php`)

The **Telemetry Engine** (`includes/Core/Telemetry.php`) is a robust diagnostics collector designed to run safely on any host environment.

### Capabilities

It provides two modes of data retrieval:

1.  **`get_all_metrics()`**: Returns pre-formatted HTML data (with icons and colors) suitable for immediate rendering in the "System Diagnostics" widget.
2.  **`get_raw_metrics()`**: Returns a pure JSON-ready array of raw integers and strings. This is ideal for JavaScript visualization tools (like the Time Monitor or live graphs).

### Data Points Collected

- **Timing**: Server Time, WP Time, MySQL Time, Uptime, Page Load Latency.
- **Resources**: Memory Usage (Real vs Limit), CPU Model/Cores/Temp, Disk Space.
- **Database**: Query Count, DB Size, Autoload Size, Table Count.
- **Assets**: Enqueued Script/Style counts.
- **Network**: Server IP, User IP, Geo Location.

---

## 3. How to Access Ajax Endpoints

All SystemDeck Ajax calls must be routed to the WordPress Ajax handler with specific security tokens.

### Prerequisites (Frontend JS)

SystemDeck localizes a global `sd_vars` object for use in your scripts:

```javascript
const ajaxUrl = window.sd_vars.ajax_url // System Ajax URL
const nonce = window.sd_vars.nonce // Security Token ('sd_load_shell')
```

### Standard Request Pattern

Use `jQuery.post` or the `fetch` API. You must always include the `action` and `nonce`.

**Example (jQuery):**

```javascript
$.post(
	sd_vars.ajax_url,
	{
		action: "sd_ACTION_NAME", // e.g., 'sd_get_telemetry'
		nonce: sd_vars.nonce,
		// ... additional data ...
	},
	function (response) {
		if (response.success) {
			console.log(response.data)
		} else {
			console.error(response.data.message)
		}
	},
)
```

---

## 4. How to Pull Telemetry Data

To fetch live system metrics for your own widgets (like a Time Monitor), use the `sd_get_telemetry` endpoint.

### Endpoint: `sd_get_telemetry`

**Response Structure (JSON):**
Returns a raw data object containing real-time metrics.

```json
{
    "success": true,
    "data": {
        "timestamp": 1706551200,
        "wp_time": 1706551200,
        "load_time_srv": 0.045,
        "memory_bytes": 12582912,
        "cpu_temp": "45°C",
        "disk_free": 549755813888,
        "db_queries": 45,
        ...
    }
}
```

### Usage Example: Creating a Live Clock

```javascript
function updateSystemClock() {
	$.post(
		sd_vars.ajax_url,
		{
			action: "sd_get_telemetry",
			nonce: sd_vars.nonce,
		},
		function (res) {
			if (res.success) {
				const data = res.data

				//Update UI
				$("#my-clock").text(data.time_srv) // Server time
				$("#my-load").text(data.load_time_srv + "s") // Load time
				$("#my-memory").text(
					(data.memory_bytes / 1024 / 1024).toFixed(2) + " MB",
				)
			}
		},
	)
}

// Poll every 5 seconds
setInterval(updateSystemClock, 5000)
```

### Filtering Keys

To save bandwidth, you can request specific keys only:

```javascript
$.post(sd_vars.ajax_url, {
    action: 'sd_get_telemetry',
    nonce:  sd_vars.nonce,
    keys: ['timestamp', 'memory_bytes'] // Only fetch these
}, ...);
```
