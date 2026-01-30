# SystemDeck Technical Manual

Welcome to the **SystemDeck** developer documentation. This manual serves as the comprehensive guide to the architecture, components, and extensibility of the SystemDeck plugin.

## 📚 Table of Contents

### 1. 🏗️ [Blueprints](./blueprints/)

Visualizing the system.

-   **[System Architecture](./blueprints/system-architecture.md)**: The "Exploded View" of the entire plugin hierarchy.
-   **[Boot Sequence](./blueprints/boot-flow.md)**: How the matrix loads.
-   **[Rendering Pipeline](./blueprints/renderer-flow.md)**: How the Shell is injected and hydrated.

### 2. 🧩 [Core Components](./core/)

Deep dives into the engine room.

-   **[HtmlAttributes](./core/HtmlAttributes.md)**: The Attribute Manager system.
-   **[Registry](./core/Registry.md)**: The central module registry.
-   **[UserPreferences](./core/UserPreferences.md)**: Managing user state (Docks, Incognito).
-   **[StyleArchitecture](./core/StyleArchitecture.md)**: CSS Strategy (5-file system, Safe EM, Units).
-   **[CSSVariables](./core/CSSVariables.md)**: Complete CSS variable reference and naming conventions.
-   **[CachingStrategy](./core/CachingStrategy.md)**: Dynamic CSS caching system and performance.

### 3. 🪝 [Hooks & API](./hooks/)

Extending the deck.

-   **[Filter Reference](./hooks/filters.md)**: Modifying data on the fly.
-   **[Action Reference](./hooks/actions.md)**: Hooking into lifecycle events.
-   **[Javascript Events](./hooks/javascript-events.md)**: Reacting to resize and UI changes.

### 4. 📜 [Reference](./reference/)

The complete code lexicon.

-   **[Function Reference](./reference/Functions.md)**: A catalog of all PHP functions and class methods.
-   **[File Structure](./reference/FileStructure.md)**: Complete guide to code organization and asset loading.
-   **[WP Colors Analysis](./reference/WP_Colors_Analysis.md)**: WordPress color scheme integration.

---

_This documentation is self-hosted within the `system-deck` plugin._
