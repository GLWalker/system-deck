/**
 * SystemDeck RetailSystem Engine
 * Phase 4.5 Complete: Magic Mouse + Color Intelligence + Style Swapper + Box Model + Shadows
 */
;(function ($) {
	"use strict"

	const RetailSystem = {
		active: false,
		iframe: null,
		harvest: null,

		init: function () {
			if ($("#sd-retail-dock").length) $("#sd-retail-dock").fadeIn()

			$(document).on("click", "#sd-retail-trigger", this.open.bind(this))
			$(document).on("click", ".sd-close-retail", this.close.bind(this))
			$(document).on(
				"click",
				".sd-inspector-close",
				this.hideInspector.bind(this),
			)
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
			window.addEventListener("message", this.handleMessage.bind(this))
		},

		// --- DATA INTELLIGENCE ---
		loadHarvest: function () {
			if (this.harvest) return
			$.post(
				sd_retail_vars.ajax_url,
				{
					action: "sd_get_harvest",
					nonce: sd_retail_vars.nonce,
				},
				(res) => {
					if (res.success) {
						this.harvest = res.data
						this.renderStyleSwapper()
					}
				},
			)
		},

		correlateColor: function (colorVal) {
			if (!this.harvest || !this.harvest.palette || !colorVal) return null
			return this.harvest.palette.find(
				(c) => c.color === colorVal || c.rgb === colorVal,
			)
		},

		// Phase 4: Match Font Size
		correlateFontSize: function (sizeVal) {
			if (
				!this.harvest ||
				!this.harvest.typography ||
				!this.harvest.typography.fontSizes
			)
				return null
			return this.harvest.typography.fontSizes.find(
				(f) => f.size === sizeVal,
			)
		},

		// Phase 4: Match Spacing
		correlateSpacing: function (spaceVal) {
			if (
				!this.harvest ||
				!this.harvest.spacing ||
				!this.harvest.spacing.steps
			)
				return null
			const steps = this.harvest.spacing.steps
			if (Array.isArray(steps)) {
				return steps.find((s) => s === spaceVal)
			}
			return null
		},

		// Phase 4.5: Match Shadow Token
		correlateShadow: function (shadowVal) {
			if (
				!this.harvest ||
				!this.harvest.shadows ||
				!shadowVal ||
				shadowVal === "none"
			)
				return null
			// Best effort match for normalized strings
			return this.harvest.shadows.find((s) => s.shadow === shadowVal)
		},

		renderStyleSwapper: function () {
			if (!this.harvest || !this.harvest.variations) return
			const vars = this.harvest.variations
			if (vars.length === 0) return

			if ($("#sd-style-swapper").length === 0) {
				let options = `<option value="">Default Style</option>`
				const current = new URLSearchParams(window.location.search).get(
					"sd_style",
				)
				vars.forEach((v) => {
					const sel = current === v.slug ? "selected" : ""
					options += `<option value="${v.slug}" ${sel}>${v.title}</option>`
				})
				const html = `<div class="sd-style-control"><select id="sd-style-swapper">${options}</select></div>`
				$(
					".sd-retail-toolbar .sd-responsive-controls:last-child",
				).before(html)
			}
		},

		switchStyle: function (e) {
			const slug = $(e.target).val()
			const iframe = $("#sd-retail-frame")
			let url = new URL(iframe.attr("src"))
			if (slug) url.searchParams.set("sd_style", slug)
			else url.searchParams.delete("sd_style")
			iframe.attr("src", url.toString())
		},

		// --- INSPECTOR UI ---
		handleMessage: function (e) {
			if (!e.data || e.data.type !== "sd_element_selected") return
			this.renderInspectorPanel(e.data.data)
		},

		renderInspectorPanel: function (data) {
			let panel = $("#sd-inspector-panel")
			if (!panel.length) {
				this.buildPanelSkeleton()
				panel = $("#sd-inspector-panel")
			}

			// 1. Header Data
			$("#sd-insp-title").text(
				data.tagName.toUpperCase() + (data.id ? "#" + data.id : ""),
			)
			$("#sd-insp-block").text(data.block)

			// 2. Box Model Visualization (Enhanced)
			this.updateBoxModel(data.styles)

			// 3. Typography (Enhanced)
			const fontName = data.styles.fontFamily
				.split(",")[0]
				.replace(/['"]/g, "")
			const fontSize = data.styles.fontSize

			let fontDisplay = `<span class="sd-meta-value">${fontName}</span>`
			const fontMatch = this.correlateFontSize(fontSize)
			if (fontMatch) {
				fontDisplay += ` <span class="sd-token-pill">${fontMatch.name}</span>`
			} else {
				fontDisplay += ` <small>${fontSize}</small>`
			}
			$("#sd-insp-font").html(fontDisplay)

			// 4. Shadows (Phase 4.5)
			const shadowVal = data.styles.boxShadow
			const shadowEl = $("#sd-insp-shadow")
			const shadowSwatch = $("#sd-insp-shadow-swatch")

			if (shadowVal && shadowVal !== "none") {
				$(".sd-insp-shadow-section").show()
				shadowSwatch.css("box-shadow", shadowVal)

				const match = this.correlateShadow(shadowVal)
				if (match) {
					shadowEl.html(
						`<span class="sd-token-pill">${match.name}</span>`,
					)
				} else {
					const clean =
						shadowVal.length > 30
							? shadowVal.substring(0, 30) + "..."
							: shadowVal
					shadowEl.text(clean)
				}
			} else {
				$(".sd-insp-shadow-section").hide()
			}

			// 5. Colors (Existing)
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

			// 6. Shared Dimensions (Inside Box Model)
			$("#sd-box-w").text(Math.round(data.box.width))
			$("#sd-box-h").text(Math.round(data.box.height))

			panel.addClass("active")
		},

		updateBoxModel: function (styles) {
			const fmt = (val) => {
				if (!val) return "-"
				if (val === "0px") return "-"
				return val.replace("px", "")
			}

			$("#sd-box-mt").text(fmt(styles.spacing.mt))
			$("#sd-box-mr").text(fmt(styles.spacing.mr))
			$("#sd-box-mb").text(fmt(styles.spacing.mb))
			$("#sd-box-ml").text(fmt(styles.spacing.ml))

			$("#sd-box-pt").text(fmt(styles.spacing.pt))
			$("#sd-box-pr").text(fmt(styles.spacing.pr))
			$("#sd-box-pb").text(fmt(styles.spacing.pb))
			$("#sd-box-pl").text(fmt(styles.spacing.pl))

			$("#sd-box-bt").text(fmt(styles.spacing.bt))
			$("#sd-box-br").text(fmt(styles.spacing.br))
			$("#sd-box-bb").text(fmt(styles.spacing.bb))
			$("#sd-box-bl").text(fmt(styles.spacing.bl))
		},

		updateColorField: function (textId, swatchId, colorVal) {
			$(swatchId).css("background-color", colorVal)
			const match = this.correlateColor(colorVal)
			if (match) {
				$(textId).html(
					`<span class="sd-token-pill">${match.name}</span> <span class="sd-meta-value">${match.color}</span>`,
				)
			} else {
				$(textId).text(colorVal)
			}
		},

		buildPanelSkeleton: function () {
			const panelHtml = `
                    <div id="sd-inspector-panel">
                        <div class="sd-insp-header">
                            <span class="dashicons dashicons-search"></span> <strong id="sd-insp-title">Inspector</strong>
                            <button class="sd-btn-icon sd-inspector-close"><span class="dashicons dashicons-no"></span></button>
                        </div>
                        <div class="sd-insp-content">
                            <div class="sd-insp-row"><label>Block</label><code id="sd-insp-block" class="sd-tag"></code></div>

                            <div class="sd-box-model-container">
                                <div class="sd-box-layer sd-box-margin" title="Margin">
                                    <span class="sd-box-label">margin</span>
                                    <div class="sd-box-top" id="sd-box-mt">-</div>
                                    <div class="sd-box-row">
                                        <div class="sd-box-left" id="sd-box-ml">-</div>
                                        <div class="sd-box-layer sd-box-border" title="Border">
                                            <div class="sd-box-top" id="sd-box-bt">-</div>
                                            <div class="sd-box-row">
                                                <div class="sd-box-left" id="sd-box-bl">-</div>
                                                <div class="sd-box-layer sd-box-padding" title="Padding">
                                                    <span class="sd-box-label">padding</span>
                                                    <div class="sd-box-top" id="sd-box-pt">-</div>
                                                    <div class="sd-box-row">
                                                        <div class="sd-box-left" id="sd-box-pl">-</div>
                                                        <div class="sd-box-content" title="Content">
                                                            <span id="sd-box-w"></span> x <span id="sd-box-h"></span>
                                                        </div>
                                                        <div class="sd-box-right" id="sd-box-pr">-</div>
                                                    </div>
                                                    <div class="sd-box-bottom" id="sd-box-pb">-</div>
                                                </div>
                                                <div class="sd-box-right" id="sd-box-br">-</div>
                                            </div>
                                            <div class="sd-box-bottom" id="sd-box-bb">-</div>
                                        </div>
                                        <div class="sd-box-right" id="sd-box-mr">-</div>
                                    </div>
                                    <div class="sd-box-bottom" id="sd-box-mb">-</div>
                                </div>
                            </div>

                            <div class="sd-insp-section"><label>Typography</label><div id="sd-insp-font" class="sd-value-truncate"></div></div>

                            <div class="sd-insp-section sd-insp-shadow-section" style="display:none;">
                                <label>Shadow</label>
                                <div class="sd-color-row">
                                    <div id="sd-insp-shadow-swatch" class="sd-shadow-preview"></div>
                                    <span id="sd-insp-shadow" class="sd-meta-value"></span>
                                </div>
                            </div>

                            <div class="sd-insp-section">
                                <label>Colors</label>
                                <div class="sd-color-row"><span id="sd-insp-color-swatch" class="sd-swatch"></span><span id="sd-insp-color"></span></div>
                                <div class="sd-color-row"><span id="sd-insp-bg-swatch" class="sd-swatch"></span><span id="sd-insp-bg"></span></div>
                            </div>
                        </div>
                    </div>`
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
                            <span class="dashicons dashicons-welcome-view-site"></span> <strong>RetailSystem</strong>
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
                        <div id="sd-retail-canvas"><iframe id="sd-retail-frame" src="${url}"></iframe><div class="sd-resizer-handle"></div></div>
                    </div>
                </div>`
			$("body").append(stageHtml)

			this.bindControls()
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
					$(this).addClass("active").siblings().removeClass("active")
					$("#sd-retail-canvas").css({
						width:
							$(this).data("w") === "100%"
								? "100%"
								: $(this).data("w") + "px",
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

	if (window.self === window.top)
		$(document).ready(function () {
			RetailSystem.init()
		})
	else $("html").addClass("sd-in-frame")
})(jQuery)
