/**
 * SystemDeck Visual Mode (V3.3.0)
 *
 * Objectives:
 * - Dynamic Viewport Breakpoints (SM, MD, LG, XL, XXL)
 * - Intelligent Auto-Scaling (Fit-to-Width)
 * - Initial Start at LG (783px)
 * - Real-time Scaling on Window Resize
 */
;(function ($) {
	"use strict"

	var VisualMode = {
		active: false,
		inspectorActive: false,
		gridActive: false,
		currentStyle: "",
		currentMode: "full", // Opens to browser size by default

		// Breakpoints Map
		breakpoints: {
			sm: 360,
			md: 782,
			lg: 960,
			xl: 1200,
			xxl: 1400,
			full: "100%",
		},

		// Resizing State
		isResizing: false,
		resizerType: null,
		startX: 0,
		startY: 0,
		startW: 0,
		startH: 0,

		init: function () {
			this.injectTrigger()
			this.bindEvents()
			this.checkAutoOpen()

			// Ensure trigger re-injects if dashboard refreshes
			document.addEventListener("sd_shell_loaded", () => {
				this.injectTrigger()
			})
		},

		// 1. Header Integration
		injectTrigger: function () {
			const $header = $("#sd-header-bar")
			const $controls = $header.find(".sd-header-right")

			if ($header.length && $("#sd-visual-trigger").length === 0) {
				const btnHtml = `
                    <button type="button" id="sd-visual-trigger" class="sd-btn-icon" title="Visual Mode">
                        <span class="ab-icon dashicons dashicons-welcome-view-site"></span>
                    </button>
                `
				if ($controls.length) {
					$controls.prepend(btnHtml)
				} else {
					$header.append(btnHtml)
				}
			}
		},

		// 2. The Premium UI Build
		renderWorkspace: function () {
			if ($("#sd-visual-workspace").length) return

			let url = window.location.href.split("#")[0]
			url +=
				(url.indexOf("?") > -1 ? "&" : "?") +
				"sd_preview=1&sd_inspect=1"

			// Style Variations
			let styleOptions = '<option value="">Default Theme</option>'
			if (window.sd_retail_vars && sd_retail_vars.variations) {
				sd_retail_vars.variations.forEach((v) => {
					styleOptions += `<option value="${v.slug}">${v.title}</option>`
				})
			}

			const workspaceHtml = `
            <div id="sd-visual-workspace">
                <div id="sd-visual-canvas-wrap">
                    <div id="sd-visual-resizable">
                        <iframe id="sd-visual-frame" src="${url}"></iframe>

                        <!-- Drag Handles -->
                        <div class="sd-resizer sd-resizer-r" data-type="width"></div>
                        <div class="sd-resizer sd-resizer-b" data-type="height"></div>

                        <!-- Dimension Tooltip -->
                        <div id="sd-resize-tooltip"></div>
                    </div>
                </div>

                <!-- Inspector HUD (React Mount) -->
                <div id="sd-inspector-hud">
                    <div id="sd-inspector-hud-header">
                        <h3>Forensic Inspector</h3>
                        <button type="button" class="sd-close-hud sd-btn-icon" title="Close Panel">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    </div>
                    <div id="sd-inspector-hud-content">
                        <!-- React will mount here -->
                    </div>
                </div>
            </div>

            <div id="sd-visual-toolbar" class="sd-visual-island">
                <ul id="sd-visual-menu" class="ab-top-menu">
                    <li class="sd-tool-item">
                        <a class="ab-item active" data-mode="full" title="Full Screen"><span class="ab-icon dashicons dashicons-screenoptions"></span></a>
                    </li>
                    <li class="sd-tool-item">
                        <a class="ab-item" data-mode="sm" title="Mobile (360px)"><span class="ab-icon dashicons dashicons-smartphone"></span></a>
                    </li>
                    <li class="sd-tool-item">
                        <a class="ab-item" data-mode="md" title="Tablet (782px)"><span class="ab-icon dashicons dashicons-tablet"></span></a>
                    </li>
                    <li class="sd-tool-item">
                        <a class="ab-item" data-mode="lg" title="Small Desktop (960px)"><span class="ab-icon dashicons dashicons-desktop"></span></a>
                    </li>
                    <li class="sd-tool-item">
                        <a class="ab-item" data-mode="xl" title="XL Wide (1200px)"><span class="ab-icon dashicons dashicons-admin-customizer"></span></a>
                    </li>
                    <li class="sd-tool-item">
                        <a class="ab-item" data-mode="xxl" title="XXL Ultra (1400px)"><span class="ab-icon dashicons dashicons-editor-expand"></span></a>
                    </li>

                    <li class="sd-tool-item">
                        <a class="ab-item" id="sd-tool-grid" title="Toggle Grid"><span class="ab-icon dashicons dashicons-grid-view"></span></a>
                    </li>
                    <li class="sd-tool-item">
                        <a class="ab-item" id="sd-tool-inspect" title="Inspect Element"><span class="ab-icon dashicons dashicons-html"></span></a>
                    </li>

                    <li id="sd-visual-style-select">
                        <select id="sd-style-swapper">
                            ${styleOptions}
                        </select>
                    </li>

                    <li class="sd-tool-item">
                        <a class="ab-item" data-mode="external" title="Open External Preview"><span class="ab-icon dashicons dashicons-external"></span></a>
                    </li>
                    <li class="sd-tool-item">
                        <a class="ab-item sd-close-visual" title="Close"><span class="ab-icon dashicons dashicons-no-alt"></span></a>
                    </li>
                </ul>
            </div>`

			$("body").append(workspaceHtml)
			$("body").addClass("sd-visual-mode-active")

			$("#sd-visual-frame").on("load", () => {
				this.syncState()
			})

			// Initial view setup
			this.setViewport(this.currentMode)
		},

		setViewport: function (mode) {
			this.currentMode = mode
			const $canvas = $("#sd-visual-resizable")
			const $wrap = $("#sd-visual-canvas-wrap")

			if (!$canvas.length) return

			let targetWidthValue = this.breakpoints[mode] || 783
			let isPercentage =
				typeof targetWidthValue === "string" &&
				targetWidthValue.includes("%")

			let targetWidth = isPercentage
				? $wrap.width() - 20
				: targetWidthValue
			let availableWidth = $wrap.width() - 20 // 10px padding on each side

			// Calculate scale if mode is not custom and width > available
			let scale = 1.0
			if (targetWidth > availableWidth) {
				scale = availableWidth / targetWidth
			}

			$canvas.css({
				width: targetWidth + "px",
				height: "100%",
				transform: `scale(${scale})`,
			})

			// Flash dimensions with scale indicator if necessary
			let displayW = Math.round(targetWidth)
			let displayH = Math.round($canvas.height())
			let tooltipText = `${displayW} x ${displayH}`
			if (scale < 1) {
				tooltipText += ` (${Math.round(scale * 100)}%)`
			}
			this.showTooltipRaw(tooltipText)
			setTimeout(() => this.hideTooltip(), 1000)
		},

		bindEvents: function () {
			const self = this

			$(document).on("click", "#sd-visual-trigger", () => self.open())
			$(document).on("click", ".sd-close-visual", () => self.close())

			// Responsive Switching
			$(document).on("click", ".sd-tool-item [data-mode]", function (e) {
				const mode = $(this).data("mode")
				if (mode === "external") {
					let url = $("#sd-visual-frame").attr("src")
					// Strip the magic mouse for external view
					url = url
						.replace("&sd_inspect=1", "")
						.replace("?sd_inspect=1", "")
					window.open(url, "_blank")
					return
				}

				$(".sd-tool-item a").removeClass("active")
				$(this).addClass("active")
				self.setViewport(mode)
			})

			// Window Resize scaling maintenance
			$(window).on("resize.sd_visual", () => {
				if (this.active && !this.isResizing) {
					this.setViewport(this.currentMode)
				}
			})

			// Grid Toggle
			$(document).on("click", "#sd-tool-grid", function () {
				self.gridActive = !self.gridActive
				$(this).toggleClass("active", self.gridActive)
				self.sendMessage({
					command: "sd_grid_toggle",
					active: self.gridActive,
				})
			})

			// Inspector Toggle
			$(document).on("click", "#sd-tool-inspect", function () {
				self.inspectorActive = !self.inspectorActive
				$(this).toggleClass("active", self.inspectorActive)

				// Toggle HUD Visibility Class
				$("#sd-visual-workspace").toggleClass(
					"hud-active",
					self.inspectorActive,
				)

				self.sendMessage({
					command: "sd_inspector_toggle",
					active: self.inspectorActive,
				})
			})

			// HUD Close Button
			$(document).on("click", ".sd-close-hud", function () {
				self.inspectorActive = false
				$("#sd-tool-inspect").removeClass("active")
				$("#sd-visual-workspace").removeClass("hud-active")
				self.sendMessage({
					command: "sd_inspector_toggle",
					active: false,
				})
			})

			// Style Swapper
			$(document).on("change", "#sd-style-swapper", function () {
				self.currentStyle = $(this).val()
				self.updateFrameUrl()
			})

			// Manual Resizer Interaction
			$(document).on("mousedown", ".sd-resizer", function (e) {
				e.preventDefault()
				self.isResizing = true
				self.resizerType = $(this).data("type")
				self.startX = e.clientX
				self.startY = e.clientY

				const $canvas = $("#sd-visual-resizable")
				self.startW = $canvas.width()
				self.startH = $canvas.height()

				$canvas.addClass("is-resizing")
				$(this).addClass("is-resizing")

				// Clear active preset buttons
				$(".sd-tool-item a").removeClass("active")
			})

			$(document).on("mousemove", function (e) {
				if (!self.isResizing) return

				const $canvas = $("#sd-visual-resizable")
				let newW = self.startW
				let newH = self.startH

				if (self.resizerType === "width") {
					const diff = (e.clientX - self.startX) * 2
					newW = self.startW + diff
				} else {
					const diff = e.clientY - self.startY
					newH = self.startH + diff
				}

				$canvas.css({
					width: newW + "px",
					height: newH + "px",
					transform: "scale(1.0)", // Reset scale during manual drag
				})

				self.showTooltip(
					$canvas.get(0).offsetWidth,
					$canvas.get(0).offsetHeight,
				)
			})

			$(document).on("mouseup", function () {
				if (!self.isResizing) return
				self.isResizing = false
				$("#sd-visual-resizable").removeClass("is-resizing")
				$(".sd-resizer").removeClass("is-resizing")
				self.hideTooltip()
			})
		},

		showTooltip: function (w, h) {
			$("#sd-resize-tooltip")
				.text(`${Math.round(w)} x ${Math.round(h)}`)
				.addClass("visible")
		},

		showTooltipRaw: function (text) {
			$("#sd-resize-tooltip").text(text).addClass("visible")
		},

		hideTooltip: function () {
			$("#sd-resize-tooltip").removeClass("visible")
		},

		updateFrameUrl: function () {
			let url = window.location.href.split("#")[0]
			url +=
				(url.indexOf("?") > -1 ? "&" : "?") +
				"sd_preview=1&sd_inspect=1"
			if (this.currentStyle) {
				url += "&sd_style=" + this.currentStyle
			}
			$("#sd-visual-frame").attr("src", url)
		},

		open: function () {
			this.active = true
			this.renderWorkspace()
		},

		close: function () {
			this.active = false
			$(window).off("resize.sd_visual")
			$("#sd-visual-workspace").remove()
			$("#sd-visual-toolbar").remove()
			$("body").removeClass("sd-visual-mode-active")
		},

		sendMessage: function (payload) {
			const frame = document.getElementById("sd-visual-frame")
			if (frame && frame.contentWindow) {
				frame.contentWindow.postMessage(payload, "*")
			}
		},

		syncState: function () {
			window.addEventListener("message", (e) => {
				if (e.data.command === "sd_inspector_ready") {
					this.sendMessage({
						command: "sd_inspector_toggle",
						active: this.inspectorActive,
					})
					this.sendMessage({
						command: "sd_grid_toggle",
						active: this.gridActive,
					})
				}

				// Catch element data and relay to HUD
				if (e.data.command === "sd_inspector_data") {
					// The HUD component will listen directly, but we log for debug
					// console.log("SD: HUD Data Received", e.data.data);
				}
			})
		},

		checkAutoOpen: function () {
			const params = new URLSearchParams(window.location.search)
			if (params.get("sd_retail") === "1") {
				this.open()
			}
		},
	}

	$(document).ready(function () {
		VisualMode.init()
	})
})(jQuery)
