# System Architecture Blueprint

This diagram represents the **Exploded View** of the SystemDeck architecture, showing how the Kernel initializes and manages its key subsystems.

```mermaid
graph TD
    %% Core Kernel
    subgraph KERNEL [SystemDeck Kernel]
        Boot[Boot (Bootstrap)]
        Registry[Registry (Module Store)]
    end

    %% Core Services
    subgraph SERVICES [Core Services]
        Assets[Assets (CSS/JS)]
        UserPrefs[UserPreferences]
        HtmlAttrs[HtmlAttributes (Attr Manager)]
        API[Rest Controller]
        Retail[Retail Controller (Frontend)]
    end

    %% Module Layer
    subgraph MODULES [Active Modules]
        Renderer[Renderer (Shell Injection)]
        WS_Renderer[WorkspaceRenderer (Content)]
        IFrame[IFrameEngine (Visual Helper)]
        Health[HealthBridge (Diagnostics)]
        Dash[DashboardProxy]
    end

    %% UI Output
    subgraph UI [Frontend / Admin]
        Shell[Template: system-deck.php]
        JS[Client: sd-drawer.js]
    end

    %% Relationships
    Boot -->|Initializes| Registry
    Boot -->|Loads| SERVICES
    Boot -->|Registers| MODULES

    Renderer -->|Hydrates| Shell
    HtmlAttrs -->|Injects Attributes| Shell
    UserPrefs -->|Configures| Shell

    Registry -->|Manages| MODULES
    WS_Renderer -->|Renders Inside| Shell

    %% Styling
    style KERNEL fill:#f9f,stroke:#333,stroke-width:2px
    style SERVICES fill:#bbf,stroke:#333,stroke-width:1px
    style MODULES fill:#bfb,stroke:#333,stroke-width:1px
    style UI fill:#ff9,stroke:#333,stroke-width:2px
```

## Description

1.  **KERNEL**: The `Boot` class is the entry point. It wakes up the `Registry` (the brain).
2.  **SERVICES**: Essential utilities like `HtmlAttributes` and `UserPreferences` are loaded immediately to provide infrastructure.
3.  **MODULES**: Functional units (like the `Renderer`) are registered components that perform specific jobs.
4.  **UI**: The final output. The `Renderer` uses `HtmlAttributes` and `UserPreferences` to build the `Shell` (system-deck.php).
