# Javascript Events

SystemDeck emits native DOM `CustomEvent`s to allow other components to react to its state changes, particularly for resizing and layout shifting.

## Events

### `sd:resize`

Fired continuously while the deck is being resized by the user. Use this for "live" reactions (e.g. reflowing a canvas, updating a readout).

-   **Target**: `#systemdeck` (The main drawer element)
-   **Bubbles**: `true`
-   **Detail**:
    -   `dock`: (string) Current dock state (e.g., `'standard-dock'`, `'right-dock'`).
    -   `type`: (string) The handle being dragged (`'n'`, `'w'`, `'e'`).

**Example:**

```javascript
document.addEventListener("sd:resize", function (e) {
	console.log("Resizing...", e.detail.dock)
	// E.g. Update your chart width
	myChart.resize()
})
```

---

### `sd:resize-end`

Fired once when the user releases the mouse/touch after a resize operation. Use this for expensive updates that shouldn't happen 60 times a second.

-   **Target**: `#systemdeck`
-   **Bubbles**: `true`
-   **Detail**:
    -   `dock`: (string) Current dock state.
    -   `type`: (string) The handle that was dragged.

**Example:**

```javascript
document.addEventListener("sd:resize-end", function (e) {
	console.log("Resize Complete.")
	// Save state or perform heavy calculation
})
```

## Global API

The core logic is exposed via `window.SystemDeck`.

```javascript
// Check current state
console.log(SystemDeck.currentDock)

// Toggle the deck
SystemDeck.toggle()

// Switch dock programmatically
SystemDeck.switchDock("right-dock")
```
