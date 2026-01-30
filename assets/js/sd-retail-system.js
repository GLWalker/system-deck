/**
 * SystemDeck RetailSystem Engine
 * Handles: Responsive Wrapper, Iframe Management, and Floating Menu.
 */
;(function ($) {
	"use strict"

	const RetailSystem = {
		active: false,
		iframe: null,

		init: function () {
			// 1. Check for Launch
			if ($("#sd-retail-dock").length) {
				$("#sd-retail-dock").fadeIn()
			}

			// 2. Bind Triggers
			$(document).on("click", "#sd-retail-trigger", this.open.bind(this))
			$(document).on("click", ".sd-close-retail", this.close.bind(this))
			$(document).on(
				"click",
				".sd-inspector-close",
				this.hideInspector.bind(this),
			)

			// 3. Auto-Launch from State
			if (
				window.sd_retail_vars &&
				sd_retail_vars.state &&
				sd_retail_vars.state.open
			) {
				this.open()
			}

			// 4. Listen for messages from the "Magic Mouse"
			window.addEventListener("message", this.handleMessage.bind(this))
		},

		handleMessage: function (e) {
			// Security check: Ensure message is from our iframe
			if (!e.data || e.data.type !== "sd_element_selected") return

			this.renderInspectorPanel(e.data.data)
		},

		renderInspectorPanel: function (data) {
			let panel = $("#sd-inspector-panel")

			// Create if missing
			if (!panel.length) {
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
                                <div id="sd-insp-size" class="sd-meta-value"></div>
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
				panel = $("#sd-inspector-panel")
			}

			// Populate Data
			$("#sd-insp-title").text(
				data.tagName.toUpperCase() + (data.id ? "#" + data.id : ""),
			)
			$("#sd-insp-block").text(data.block)
			$("#sd-insp-w").text(Math.round(data.box.width) + "px")
			$("#sd-insp-h").text(Math.round(data.box.height) + "px")

			$("#sd-insp-font").text(
				data.styles.fontFamily.split(",")[0].replace(/['"]/g, ""),
			)
			$("#sd-insp-size").text(
				data.styles.fontSize + " (" + data.styles.fontWeight + ")",
			)

			$("#sd-insp-color").text(data.styles.color)
			$("#sd-insp-color-swatch").css(
				"background-color",
				data.styles.color,
			)

			$("#sd-insp-bg").text(data.styles.backgroundColor)
			$("#sd-insp-bg-swatch").css(
				"background-color",
				data.styles.backgroundColor,
			)

			panel.addClass("active")
		},

		hideInspector: function () {
			$("#sd-inspector-panel").removeClass("active")
		},

		open: function (e) {
			if (e) e.preventDefault()
			this.active = true
			$("body").addClass("sd-retail-mode-active")
			$("#sd-retail-dock").fadeOut()

			// Prevent double render
			if ($("#sd-retail-wrapper").length) return

			// 1. Determine URL (Add sd_preview param)
			let url = window.location.href
			url = url.split("#")[0] // Remove anchor
			url += (url.indexOf("?") > -1 ? "&" : "?") + "sd_preview=1"

			// 2. Build Stage
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

			// 3. Bind Canvas Controls
			this.bindControls()

			// 4. Save State
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
					var canvas = $("#sd-retail-canvas")
					canvas.css({ width: w === "100%" ? "100%" : w + "px" })
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

	// Only run if NOT inside the preview iframe
	if (window.self === window.top) {
		$(document).ready(function () {
			RetailSystem.init()
		})
	} else {
		$("html").addClass("sd-in-frame")
	}
})(jQuery)
