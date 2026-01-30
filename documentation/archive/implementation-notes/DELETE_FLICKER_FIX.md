## FIX: Workspace Delete Confirmation Flicker

**File:** `/assets/js/sd-system.js`
**Line:** 76

### Problem:

When clicking the delete button, the confirmation dialog flickers before allowing you to confirm.

### Root Cause:

The click event bubbles up and may trigger parent elements or cause UI re-renders.

### Solution:

Add event parameter and prevent default/propagation.

---

### **MANUAL FIX:**

**Find this code (line 76):**

```javascript
$("body").on("click", ".sd-delete-ws-btn", function () {
```

**Change to:**

```javascript
$("body").on("click", ".sd-delete-ws-btn", function (e) {
	e.preventDefault()
	e.stopPropagation()

```

**Complete block should look like:**

```javascript
// 3. Delete Workspace
$("body").on("click", ".sd-delete-ws-btn", function (e) {
	e.preventDefault()
	e.stopPropagation()

	const name = $(this).data("name")
	if (confirm('Are you sure you want to delete "' + name + '"?')) {
		SystemDeckSys.deleteWorkspace(name)
	}
})
```

---

### **Quick Steps:**

1. **Open:** `sd-system.js` (line 76)
2. **Add `e` parameter** to function
3. **Add these 2 lines** right after the opening `{`:
    ```javascript
    e.preventDefault()
    e.stopPropagation()
    ```
4. **Add blank line** for readability
5. **Save** (Cmd+S)

---

### **What This Fixes:**

- ✅ Prevents event from bubbling to parent elements
- ✅ Stops default link/button behavior
- ✅ Eliminates UI flicker during confirmation
- ✅ Ensures confirm dialog stays visible until user responds
