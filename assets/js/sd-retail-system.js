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

			// 3. Listen for Inspector Messages
			window.addEventListener("message", this.handleMessage.bind(this))

			// 3. Auto-Launch from State
			if (
				window.sd_retail_vars &&
				sd_retail_vars.state &&
				sd_retail_vars.state.open
			) {
				this.open()
			}
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

		handleMessage: function (event) {
			if (!event.data || event.data.type !== "sd_element_selected") return

			console.log("RetailSystem: Element Selected", event.data.data)

			// TODO: Activate Data Correlator and update Inspector Panel UI
			this.updateInspectorPanel(event.data.data)
		},

		updateInspectorPanel: function (data) {
			// Basic UI update for now
			console.log("Update UI with selection:", data.block)
		},

		persistState: function (state) {
			if (!window.sd_retail_vars) return
			$.post(sd_retail_vars.ajax_url, {
				action: "sd_save_widget_data", // Generic Data API
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
	}
})(jQuery)
