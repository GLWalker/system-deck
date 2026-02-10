This manifest is prepared for the **Antigravity Editor Team Agents**. It is structured to execute the transition from **SystemDeck Beta** to the **SystemDeck Unified Architecture**, strictly adhering to the `master.md` blueprint and your established **SMART** development philosophy.

### **Project Manifesto: SystemDeck Rebirth**

**Objective:** Re-engineer SystemDeck from a "Dashboard Plugin" into a "Runtime Infrastructure Shell" without feature loss.
**Core Directive:** Separate State (Truth) from UI (Render).
**Constraint:** Adhere to the "Zero Regression" policy while finalizing the autonomous Pin Grid system.

---

### **Phase 1: The System (Structural Foundation)**

_Establish the file structure and rigid boundaries defined in `master.md`. This is the "System" phase of your SMART method—defining the workspace and canvas._

**Agent Tasks:**

1. **Scaffold Directory Structure:**

- Create the exact hierarchy from `master.md` (Section 4).
- **Strict Rule:** Ensure `core/`, `state/`, `registry/`, and `runtime/` are distinct. No cross-pollution.

2. **Initialize PHP Bootstrapping:**

- Implement `systemdeck.php` (provided).
- **Action:** Verify `systemdeck_init` hooks run _only_ after environment detection.
- **Security:** Implement `systemdeck_user_can_boot()` immediately to enforce role-gating from Day 1.

3. **Webpack & Build Configuration:**

- Configure the build to output `systemdeck.js` and `systemdeck.css`.
- **Optimization:** Ensure `wp-data`, `wp-element`, and `wp-components` are externalized (not bundled) to keep the runtime lean.

### **Phase 2: The Map (State & Data Topology)**

_Map the data flow. This corresponds to the "Map" phase—connecting the plugin's internal logic to the WordPress state engine (`wp.data`)._

**Agent Tasks:**

1. **Construct the Store (`core/state/`):**

- **Store Shape:** Implement the exact shape defined in Section 5.1 of `master.md`.
- **Key Requirement:** The `store` must be the _Single Source of Truth_.
- **Migration Task:** Audit SystemDeck Beta for any `jQuery` DOM storage or hidden inputs and remap them to Redux actions.

2. **Implement the Workspace Model:**

- Define the default `workspace` object (Section 5.2).
- **Constraint:** Workspaces must be defined by _configuration_, not hardcoded HTML.

3. **Persistence Layer (The Bridge):**

- Implement `persistence.js` to handle the hierarchy: `wp.data` -> `localStorage` -> `PHP Batched Storage`.
- **Restriction:** Do NOT use `wp-ajax` for state updates. Use the REST API or strictly defined storage endpoints only when necessary for permanence.

### **Phase 3: Adaptation (Runtime & UI Shell)**

_Adapt the styles and functionality to the environment. This is the "Adapt" phase—ensuring the shell looks native in Admin, Frontend, and FSE._

**Agent Tasks:**

1. **Environment Detection (`core/bootstrap/environment.js`):**

- Write logic to detect: `isAdmin`, `isFrontend`, `isFSE`, `isIframe`.
- **Requirement:** The shell must mount differently based on context but run the _same_ logic.

2. **The UI Shell (`core/runtime/ui-shell.js`):**

- Build the main container that conditionally renders:
- The Dock (Toolbar)
- The Panel (Inspector/Widget Area)
- The Overlay (Pin Canvas)

- **Style Guide:** Use strictly modular CSS (`styles/sd-core.css`). No global pollution.

3. **Layout Engine (`core/runtime/layout.js`):**

- Implement the grid logic that reads `layout.items` from the store and renders coordinates.
- **Critical:** Movement/Resizing must dispatch actions, _never_ mutate DOM directly.

### **Phase 4: Replication (Feature Porting)**

_Replicate the served data from Beta to the new architecture. Use the "Utility Bias" philosophy—if it repeats, make it a tool._

**Agent Tasks:**

1. **Widget Migration:**

- **Audit:** List every widget in SystemDeck Beta.
- **Refactor:** Convert each widget into the `SD.registerWidget()` contract (Section 7.1).
- **Rule:** Widgets must become "dumb" renderers. They receive props, render UI, and dispatch actions. They do not hold state.

2. **Inspector Porting:**

- Migrate the Inspector tools to the `inspectors/` directory.
- Ensure they connect to the `store` to modify the `activeSelection` or `pageContext`.

### **Phase 5: Technology (The Pin Engine)**

_The final missing piece. Utilizing the technology to create the unique, native experience for the "Pin-Items" grid._

**Agent Tasks:**

1. **Pin Registry (`core/pins/`):**

- Implement `SD.registerPinType()` (Section 8.1).
- Define the standard pin types: `Note`, `Metric`, `Status`, `Link`.

2. **The Pin Runtime (`core/pins/runtime.js`):**

- **Challenge:** This engine must run _even if the main dashboard is closed_.
- **Logic:** It iterates over `store.pins`, checks `pin.visibility`, and renders them into the `Overlay` layer using `ReactDOM.createPortal`.

3. **The Missing Link - Pin Grid:**

- Implement the `renderGrid` method for pins.
- Create a dedicated "Pin Mode" view (Section 10) where pins snap to a specialized grid layer, distinct from the widget grid.

### **Final Compliance Checklist for Agents**

- **Performance:** Is the bundle minimal? Are scripts loaded only when the shell mounts?
- **Discipline:** Are `var` (or `const/let` per project standard) and naming conventions consistent?
- **Isolation:** Does the CSS use low-specificity class names (`.sd-*`) to avoid theme conflicts?
- **Control:** Is direct DOM manipulation zero? (Exceptions only for the absolute root mount).

**Status:** Ready for Execution.
**Entry Point:** `systemdeck/core/bootstrap/index.js`
