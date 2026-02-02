/**
 * SystemDeck System Logic
 * Handles System Config, Workspace Switching, and State Persistence.
 */
;(function ($) {
	"use strict"

	const SystemDeckSys = {
		initialized: false,

		init: function () {
			if (this.initialized) return
			this.initialized = true
			this.bindEvents()
			this.restoreState()
		},

		restoreState: function () {
			// Check last active screen
			const lastScreen = localStorage.getItem("sd_active_screen")
			if (lastScreen) {
				if (lastScreen === "#system") {
					// Update UI immediately for perceived speed
					$("#sd-workspace-title").text("SystemDeck")
					$("#workspace-meta-container").hide()
					this.loadSystemScreen()
				} else if (lastScreen.startsWith("#workspace-")) {
					// Extract name from data attribute logic or save name directly
					const lastWSID = localStorage.getItem(
						"sd_active_workspace_id",
					)
					const lastWSName = localStorage.getItem(
						"sd_active_workspace_name",
					)

					if (lastWSID) {
						// Update UI immediately for perceived speed
						$("#sd-workspace-title").text(lastWSName || lastWSID)
						$("#workspace-meta-container").show()
						this.loadWorkspace(lastWSID, lastWSName)
					} else {
						// Fallback to Default
						this.loadSystemScreen()
					}
				} else {
					this.loadSystemScreen()
				}
			} else {
				// First load ever? System Screen.
				this.loadSystemScreen()
			}
		},

		bindEvents: function () {
			// 1. Navigation Handler
			$("body").on("click", "#sd-menu a", function (e) {
				const href = $(this).attr("href")
				if (!href || href === "#") return

				e.preventDefault()

				if (href === "#system") {
					SystemDeckSys.loadSystemScreen()
				} else if (href.startsWith("#workspace-")) {
					const workspaceID = $(this).data("workspace")
					const workspaceTitle = $(this).data("name") || workspaceID
					SystemDeckSys.loadWorkspace(workspaceID, workspaceTitle)
				} else if (href === "#toolbox") {
					SystemDeckSys.updateMenuState("#toolbox")
					// No preventDefault here if we want native hash listener and other listeners to fire
					// or we trigger a custom event
					$(document).trigger("sd_toolbox_toggle")
					return
				}
			})

			// 2. Create Workspace
			$("body").on("click", "#sd-btn-new-workspace", function () {
				$(this).hide()
				$("#sd-create-workspace-form").css("display", "inline-flex")
				$("#sd-input-workspace-name").focus()
			})

			$("body").on("click", "#sd-btn-cancel-workspace", function () {
				$("#sd-create-workspace-form").hide()
				$("#sd-btn-new-workspace").show()
				$("#sd-input-workspace-name").val("")
			})

			$("body").on("click", "#sd-btn-save-workspace", function () {
				const name = $("#sd-input-workspace-name").val().trim()
				if (!name) return
				SystemDeckSys.createWorkspace(name)
			})

			// 3. Delete Workspace
			$("body").on("click", ".sd-delete-ws-btn", function (e) {
				e.preventDefault()
				e.stopPropagation()

				const name = $(this).data("name")
				if (
					confirm('Are you sure you want to delete "' + name + '"?')
				) {
					SystemDeckSys.deleteWorkspace(name)
				}
			})

			// 4. Load from Card
			$("body").on("click", ".sd-load-ws-btn", function () {
				const workspaceID = $(this).data("workspace")
				const workspaceTitle = $(this).data("name") || workspaceID
				SystemDeckSys.loadWorkspace(workspaceID, workspaceTitle)
			})
		},

		loadSystemScreen: function () {
			const container = $("#sd-workspacewrap")

			// IMMEDIATE UPDATE: Hide Toolbox & Set Title
			$("#sd-workspace-title").text("SystemDeck")
			$("#workspace-meta-container").hide()

			container.html('<div class="sd-loading">Loading System...</div>')

			$.post(
				window.sd_vars?.ajax_url || ajaxurl,
				{
					action: "sd_render_system_screen",
					nonce: window.sd_vars?.nonce || "",
				},
				function (res) {
					if (res.success) {
						container.html(res.data.html)
						SystemDeckSys.updateMenuState("#system")
						localStorage.setItem("sd_active_screen", "#system")
					}
				},
			)
		},

		loadWorkspace: function (id, title = null) {
			const displayTitle = title || id
			const container = $("#sd-workspacewrap")
			container.html(
				'<div class="sd-loading">Loading ' + displayTitle + "...</div>",
			)

			// IMMEDIATE UPDATE: Show Toolbox & Set Title
			$("#sd-workspace-title").text(displayTitle)
			$("#workspace-meta-container").show()

			$.post(
				window.sd_vars?.ajax_url || ajaxurl,
				{
					action: "sd_render_workspace",
					name: id,
					nonce: window.sd_vars?.nonce || "",
				},
				function (res) {
					if (res.success) {
						container.html(res.data.html)

						// Add Loading Overlay for Workspaces (2s delay for widget hydration)
						const overlay = $(
							'<div class="sd-loading-overlay"><span class="spinner is-active" style="float:none;margin:0 10px 0 0;"></span> Initializing Environment...</div>',
						)
							.css({
								position: "absolute",
								top: 0,
								left: 0,
								right: 0,
								bottom: 0,
								background: "#f0f0f1",
								zIndex: 9999,
								display: "flex",
								alignItems: "center",
								justifyContent: "center",
								fontSize: "14px",
								fontWeight: 500,
								color: "#646970",
							})
							.appendTo(container)

						setTimeout(() => {
							overlay.fadeOut(300, function () {
								$(this).remove()
							})
						}, 2000)

						// Calculate slug for menu highlighting
						const slug = id
							.replace(/\s+/g, "-")
							.replace(/[^a-zA-Z0-9-_]/g, "")
							.toLowerCase()
						const href = "#workspace-" + slug

						SystemDeckSys.updateMenuState(href)

						// Persist State
						localStorage.setItem("sd_active_screen", href)
						localStorage.setItem("sd_active_workspace_id", id)
						localStorage.setItem(
							"sd_active_workspace_name",
							displayTitle,
						)

						// Trigger legacy event for other modules
						$(document).trigger("sd_workspace_rendered", [id])
					} else {
						container.html(
							'<div class="sd-error">Failed: ' +
								(res.data.message || "Unknown") +
								"</div>",
						)
					}
				},
			)
		},

		createWorkspace: function (name) {
			const btn = $("#sd-btn-save-workspace")
			btn.prop("disabled", true)

			$.post(
				window.sd_vars?.ajax_url || ajaxurl,
				{
					action: "sd_create_workspace",
					name: name,
					nonce: window.sd_vars?.nonce || "",
				},
				function (res) {
					btn.prop("disabled", false)
					if (res.success) {
						// Reset Form
						$("#sd-btn-cancel-workspace").click()

						// Reload Screen to show new card
						SystemDeckSys.loadSystemScreen()

						// Update Sidebar
						SystemDeckSys.refreshMenu()
					} else {
						alert(res.data.message || "Error")
					}
				},
			)
		},

		deleteWorkspace: function (name) {
			$.post(
				window.sd_vars?.ajax_url || ajaxurl,
				{
					action: "sd_delete_workspace",
					name: name,
					nonce: window.sd_vars?.nonce || "",
				},
				function (res) {
					if (res.success) {
						SystemDeckSys.loadSystemScreen()
						SystemDeckSys.refreshMenu()
					} else {
						alert(res.data.message || "Error")
					}
				},
			)
		},

		refreshMenu: function () {
			$.post(
				window.sd_vars?.ajax_url || ajaxurl,
				{
					action: "sd_refresh_menu",
					nonce: window.sd_vars?.nonce || "",
				},
				function (res) {
					if (res.success) {
						// Replace the inner content of the aside
						// Note: MenuEngine outputs <div id="sd-menuwrap">...</div>
						// So we replace #sd-menuwrap
						$("#sd-menuwrap").replaceWith(res.data.html)

						// Re-highlight current item based on local state
						const current = localStorage.getItem("sd_active_screen")
						if (current) SystemDeckSys.updateMenuState(current)
					}
				},
			)
		},

		updateMenuState: function (href) {
			$("#sd-menu li").removeClass(
				"current wp-has-current-submenu wp-menu-open",
			)
			$("#sd-menu a").removeClass("current")

			// Find the link
			const link = $('#sd-menu a[href="' + href + '"]')
			if (link.length) {
				const li = link.closest("li")
				li.addClass("current")

				// If it's a submenu item
				if (li.parents(".wp-submenu").length) {
					li.parents(".wp-has-submenu").addClass(
						"wp-has-current-submenu wp-menu-open",
					)
				}
			}
		},
	}

	$(document).ready(function () {
		SystemDeckSys.init()

		// Handle Retail Async Load
		document.addEventListener("sd_shell_loaded", function () {
			SystemDeckSys.init()
		})
	})

	// Expose globally for React components
	window.SystemDeckSys = SystemDeckSys
})(jQuery)
