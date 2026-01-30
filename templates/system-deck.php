<div <?php \SystemDeck\Core\sd_attr('system-deck'); ?>>

    <header id="sd-header-bar">
        <button type="button" class="sd-drawer-icon sd-btn-icon" title="Minimize Dock">
            <span class="dashicons dashicons-index-card"></span>
        </button>
        <div class="sd-header-left">
            <h2 id="sd-workspace-title">SystemDeck</h2>
        </div>

        <div class="sd-header-right">
            <!-- Controls Injected Here -->
            <button type="button" id="sd-theme-toggle" class="sd-btn-icon" title="Toggle Dark Mode">
                <span class="dashicons dashicons-star-half"></span>
            </button>
            <div class="sd-dock-controls">
                <button type="button" data-dock="left-dock" class="sd-btn-icon" title="Dock Left">
                    <span class="dashicons dashicons-arrow-left-alt"></span>
                </button>
                <button type="button" data-dock="base-dock" class="sd-btn-icon" title="Dock Base">
                    <span class="dashicons dashicons-minus"></span>
                </button>
                <button type="button" data-dock="right-dock" class="sd-btn-icon" title="Dock Right">
                    <span class="dashicons dashicons-arrow-right-alt"></span>
                </button>
                <button type="button" data-dock="standard-dock" class="sd-btn-icon" title="Standard Dock">
                    <span class="dashicons dashicons-randomize"></span>
                </button>
                <button type="button" data-dock="full-dock" class="sd-btn-icon" title="Full Screen">
                    <span class="dashicons dashicons-fullscreen-alt"></span>
                </button>
            </div>
            <button type="button" id="sd-close-button" class="sd-btn-icon" title="Close">
                <span class="dashicons dashicons-no"></span>
            </button>
        </div>
    </header>

    <div id="sd-wrap">

        <aside id="sd-menumain" role="navigation">
            <?php
            // Initialize and Render the Menu Engine
            try {
                $menu_engine = new \SystemDeck\Core\MenuEngine();
                $menu_engine->render();
            } catch (\Exception $e) {
                echo '<div class="sd-error">Menu Load Failed</div>';
            }
            ?>
        </aside>

        <section id="sd-workspace-content">

            <!-- Contextual Settings Container (Top Right) -->
            <div id="workspace-meta-container">
                <!-- workspace-meta style is toggled display block or none -->
                <div id="workspace-meta" class="metabox-prefs" style="display: none;">

                    <div id="workspace-options-wrap" class="" tabindex="-1" aria-label="Workspace Options Tab" style="display: block;">
                        <form id="adv-settings" method="post">
                            <fieldset class="metabox-prefs">
                                <legend>Workspace Elements</legend>
                                <p>
                                    Some workspace elements can be shown or hidden by using the checkboxes. Expand or collapse the elements by clicking on their headings, and arrange them by dragging their headings or by clicking on the up and down arrows.
                                </p>
                                <div class="metabox-prefs-container" id="sd-toolbox-content">
                                    <!-- React will mount the toolbox checkboxes here -->
                                </div>
                            </fieldset>
                            <input type="hidden" id="workspaceoptionnonce" name="workspaceoptionnonce" value="16c06132f2">
                        </form>
                    </div>
                </div>

                <div id="workspace-meta-links">
                    <div id="workspace-options-link-wrap" class="workspace-meta-toggle">
                        <!-- workspace-meta-active is toggled -->
                        <button type="button" id="show-widgets-link" class="button show-widgets" aria-controls="workspace-meta" aria-expanded="false">
                            Widgets
                        </button>
                    </div>
                </div>
            </div>

            <div id="sd-workspacewrap">
                <?php
                // Render the active workspace
                if (class_exists('SystemDeck\Modules\WorkspaceRenderer')) {
                    \SystemDeck\Modules\WorkspaceRenderer::render('system_main');
                } else {
                    echo 'Error: Renderer module not loaded.';
                }
                ?>
            </div>
        </section>
    </div>

</div>