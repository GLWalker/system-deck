/**
 * SystemDeck RetailSystem Engine
 * Phase 3: Data Correlator & Style Swapper
 */
;(function ($) {
	"use strict"

	const RetailSystem = {
		active: false,
		iframe: null,
		harvest: null, // Stores theme.json data

		init: function () {
			if ($("#sd-retail-dock").length) $("#sd-retail-dock").fadeIn()

			$(document).on("click", "#sd-retail-trigger", this.open.bind(this))
			$(document).on("click", ".sd-close-retail", this.close.bind(this))
			$(document).on(
				"click",
				".sd-inspector-close",
				this.hideInspector.bind(this),
			)

			// Phase 3: Variation Switcher
			$(document).on(
				"change",
				"#sd-style-swapper",
				this.switchStyle.bind(this),
			)

			if (
				window.sd_retail_vars &&
				sd_retail_vars.state &&
				sd_retail_vars.state.open
			) {
				this.open()
			}

			// Listen for messages from the "Magic Mouse"
			window.addEventListener("message", this.handleMessage.bind(this))
		},

		// --- DATA CORRELATOR LOGIC ---

		loadHarvest: function () {
			if (this.harvest) return // Already loaded

			$.post(
				sd_retail_vars.ajax_url,
				{
					action: "sd_get_harvest",
					nonce: sd_retail_vars.nonce,
				},
				(res) => {
					if (res.success) {
						this.harvest = res.data
						this.renderStyleSwapper() // Populate dropdown
					}
				},
			)
		},

		correlateColor: function (colorVal) {
			if (!this.harvest || !this.harvest.palette || !colorVal) return null

			// Normalize (simple check, robust would use a Color library)
			// Checks for direct Hex or RGB match
			const match = this.harvest.palette.find((c) => c.color === colorVal)
			if (match) return match

			// Simple RGB string matcher could go here
			return null
		},

		// --- STYLE SWAPPER LOGIC ---

		renderStyleSwapper: function () {
			if (!this.harvest || !this.harvest.variations) return

			const vars = this.harvest.variations
			if (vars.length === 0) return

			// Inject Dropdown into Toolbar if not exists
			if ($("#sd-style-swapper").length === 0) {
				let options = `<option value="">Default Style</option>`

				// Get current active style from URL (if we just reloaded)
				const current = new URLSearchParams(window.location.search).get(
					"sd_style",
				)

				vars.forEach((v) => {
					const sel = current === v.slug ? "selected" : ""
					options += `<option value="${v.slug}" ${sel}>${v.title}</option>`
				})

				const selectorHtml = `
                    <div class="sd-style-control">
                        <select id="sd-style-swapper">${options}</select>
                    </div>
                `

				// Append before the close button
				$(
					".sd-retail-toolbar .sd-responsive-controls:last-child",
				).before(selectorHtml)
			}
		},

		switchStyle: function (e) {
			const slug = $(e.target).val()
			const iframe = $("#sd-retail-frame")
			let url = new URL(iframe.attr("src"))

			if (slug) {
				url.searchParams.set("sd_style", slug)
			} else {
				url.searchParams.delete("sd_style")
			}

			iframe.attr("src", url.toString())
		},

		// --- INSPECTOR UI (UPDATED) ---

		handleMessage: function (e) {
			if (!e.data || e.data.type !== "sd_element_selected") return
			this.renderInspectorPanel(e.data.data)
		},

		renderInspectorPanel: function (data) {
			let panel = $("#sd-inspector-panel")

			// Build Panel Skeleton if missing
			if (!panel.length) {
				this.buildPanelSkeleton()
				panel = $("#sd-inspector-panel")
			}

			// Populate Basic Data
			$("#sd-insp-title").text(
				data.tagName.toUpperCase() + (data.id ? "#" + data.id : ""),
			)
			$("#sd-insp-block").text(data.block)
			$("#sd-insp-w").text(Math.round(data.box.width) + "px")
			$("#sd-insp-h").text(Math.round(data.box.height) + "px")
			$("#sd-insp-font").text(
				data.styles.fontFamily.split(",")[0].replace(/['"]/g, ""),
			)

			// --- CORRELATION MAGIC ---
			this.updateColorField(
				"#sd-insp-color",
				"#sd-insp-color-swatch",
				data.styles.color,
			)
			this.updateColorField(
				"#sd-insp-bg",
				"#sd-insp-bg-swatch",
				data.styles.backgroundColor,
			)

			panel.addClass("active")
		},

		updateColorField: function (textId, swatchId, colorVal) {
			$(swatchId).css("background-color", colorVal)

			const match = this.correlateColor(colorVal)
			if (match) {
				// Found a token!
				$(textId).html(
					`<span class="sd-token-pill">${match.name}</span> <small>${colorVal}</small>`,
				)
			} else {
				$(textId).text(colorVal)
			}
		},

		buildPanelSkeleton: function () {
			const panelHtml = `
                    <div id="sd-inspector-panel">
                        <div class="sd-insp-header">
                            <span class="dashicons dashicons-search"></span>
                            <strong id="sd-insp-title">Inspector</strong>
                            <button class="sd-btn-icon sd-inspector-close"><span class="dashicons dashicons-no"></span></button>
                        </div>
                        <div class="sd-insp-content">
                            <div class="sd-insp-row">
                                <label>Block</label>
                                <code id="sd-insp-block" class="sd-tag"></code>
                            </div>
                            <div class="sd-insp-grid-2">
                                <div><label>Width</label> <span id="sd-insp-w"></span></div>
                                <div><label>Height</label> <span id="sd-insp-h"></span></div>
                            </div>
                            <div class="sd-insp-section">
                                <label>Typography</label>
                                <div id="sd-insp-font" class="sd-value-truncate"></div>
                            </div>
                            <div class="sd-insp-section">
                                <label>Colors</label>
                                <div class="sd-color-row">
                                    <span id="sd-insp-color-swatch" class="sd-swatch"></span>
                                    <span id="sd-insp-color"></span>
                                </div>
                                <div class="sd-color-row">
                                    <span id="sd-insp-bg-swatch" class="sd-swatch"></span>
                                    <span id="sd-insp-bg"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                `
			$("#sd-retail-wrapper").append(panelHtml)
		},

		hideInspector: function () {
			$("#sd-inspector-panel").removeClass("active")
		},

		open: function (e) {
			if (e) e.preventDefault()
			this.active = true
			$("body").addClass("sd-retail-mode-active")
			$("#sd-retail-dock").fadeOut()

			if ($("#sd-retail-wrapper").length) return

			let url = window.location.href
			url = url.split("#")[0] // Remove anchor
			url += (url.indexOf("?") > -1 ? "&" : "?") + "sd_preview=1"

			const stageHtml = `
                <div id="sd-retail-wrapper">
                    <div class="sd-retail-toolbar">
                        <div class="sd-responsive-controls detatched" style="border:none;">
                            <span class="dashicons dashicons-welcome-view-site"></span>
                            <strong>RetailSystem</strong>
                        </div>
                        <div class="sd-responsive-controls detatched" style="border:none;">
                            <button data-w="375" class="sd-btn-icon" title="Mobile"><span class="dashicons dashicons-smartphone"></span></button>
                            <button data-w="768" class="sd-btn-icon" title="Tablet"><span class="dashicons dashicons-tablet"></span></button>
                            <button data-w="100%" class="sd-btn-icon active" title="Desktop"><span class="dashicons dashicons-desktop"></span></button>
                        </div>
                        <div class="sd-responsive-controls detatched" style="border:none;">
                            <button class="sd-btn-icon sd-close-retail" title="Close"><span class="dashicons dashicons-no-alt"></span></button>
                        </div>
                    </div>
                    <div id="sd-retail-stage">
                        <div id="sd-retail-canvas">
                            <iframe id="sd-retail-frame" src="${url}"></iframe>
                            <div class="sd-resizer-handle"></div>
                        </div>
                    </div>
                </div>
            `
			$("body").append(stageHtml)

			this.bindControls()

			// Phase 3: Load Data
			this.loadHarvest()

			this.persistState({ open: true })
		},

		close: function (e) {
			if (e) e.preventDefault()
			this.active = false
			$("body").removeClass("sd-retail-mode-active")
			$("#sd-retail-wrapper").remove()
			$("#sd-retail-dock").fadeIn()
			this.persistState({ open: false })
		},

		bindControls: function () {
			$("#sd-retail-wrapper .sd-btn-icon[data-w]").on(
				"click",
				function () {
					$("#sd-retail-wrapper .sd-btn-icon").removeClass("active")
					$(this).addClass("active")
					var w = $(this).data("w")
					$("#sd-retail-canvas").css({
						width: w === "100%" ? "100%" : w + "px",
					})
				},
			)
		},

		persistState: function (state) {
			if (!window.sd_retail_vars) return
			$.post(sd_retail_vars.ajax_url, {
				action: "sd_save_widget_data",
				nonce: sd_retail_vars.nonce,
				widget_id: "retail_state",
				key: "pref_retail_state",
				value: state,
			})
		},
	}

	if (window.self === window.top) {
		$(document).ready(function () {
			RetailSystem.init()
		})
	} else {
		$("html").addClass("sd-in-frame")
	}
})(jQuery)
