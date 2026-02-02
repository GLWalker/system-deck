# Transfer Directive: SystemDeck Inspector Forensic Patch

**Status:** Implementation Complete / Optimization Pending
**Issue:** "Silent Crash" or Communication Blockage in Retail Mode.

## Overview for Investigating Agent

The objective was to fix the SystemDeck Inspector's forensic accuracy, specifically ensuring that font sizes and emphasis (bold/italic) are reported for the **exact tag clicked** (e.g., `<p>` or `<em>`) rather than the parent Gutenberg block wrapper (e.g., `Group` or `Column`).

### Recent Work Completed:

1.  **Frontend Stability**: Wrapped `sd-retail-system.js` in a jQuery closure to resolve standard WordPress No-Conflict mode issues.
2.  **Forensic Re-engineering**: Refactored `sd-inspector-engine.js` with "Cursor-Aware" logic.
    - It now uses a `contentTags` whitelist to prioritize text nodes over block wrappers.
    - It resolves a `forensicSubject` to ensure `getComputedStyle` is called on the inner text node, not the container.
3.  **Cache Busting**: Added dynamic versioning to scripts in `RetailController.php` to ensure browser refreshes load the latest logic.

### Current Symptom: "No Magic Mouse"

Despite the code being pushed, the user reports that the inspector is not providing the "Magic Mouse" highlighter or reporting the correct paragraph styles. This indicates:

- **Initialization Failure**: The `sd-inspector-engine.js` may not be starting correctly inside the iframe.
- **Silent Syntax Error**: Even after the last fix, there may be a runtime error blocking event listeners.
- **Message Blockage**: The `postMessage` communication between the parent (`sd-retail-system.js`) and the child (`sd-inspector-engine.js`) might be failing.

### Files to Review:

1.  `/assets/js/sd-inspector-engine.js`: The "Engine" running inside the iframe. Check `init()`, `bindEvents()`, and the `select()` logic.
2.  `/assets/js/sd-retail-system.js`: The "HUD" container. Check how it toggles the inspector via `postMessage`.
3.  `/includes/Core/RetailController.php`: Check how these scripts are enqueued and localized.

### **Restraint Directive:**

**INTAKE ONLY.** Do not modify code. Analyze the communication flow and the forensic targeting logic to identify why the script is failing to engage the "Magic Mouse" highlighter or report accurate paragraph metadata.
