/**
 * SystemDeck RetailSystem Engine
 * Phase 4.6 Complete: HUD Refinements & FSE Intelligence
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
				"click",
				".sd-breadcrumb-item[data-index]",
				this.reselectElement.bind(this),
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

		// --- CORRELATORS ---

		// --- UPDATED CORRELATORS (To match Universal Harvester) ---

		getSetting: function (path) {
			// Helper to safely dive into harvest.settings
			if (!this.harvest || !this.harvest.settings) return null
			return path
				.split(".")
				.reduce((o, i) => (o ? o[i] : null), this.harvest.settings)
		},

		/**
		 * Normalizes a color (Hex, RGB, RGBA) into a numeric array [r, g, b, a] for precise comparison.
		 */
		getRgbaArray: function (color) {
			if (!color) return null

			// 1. Handle Modern color(srgb ...) format
			// Example: color(srgb 0.0666667 0.0666667 0.0666667 / 0.85)
			if (color.startsWith("color(srgb")) {
				const match = color.match(
					/color\(srgb\s+([\d.]+)\s+([\d.]+)\s+([\d.]+)(?:\s*\/\s*([\d.]+))?\)/,
				)
				if (match) {
					return [
						Math.round(parseFloat(match[1]) * 255),
						Math.round(parseFloat(match[2]) * 255),
						Math.round(parseFloat(match[3]) * 255),
						parseFloat(match[4] || "1"),
					]
				}
			}

			// 2. Handle RGB/RGBA
			if (color.startsWith("rgb")) {
				const match = color.match(
					/rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*([\d.]+))?\)/,
				)
				if (match) {
					return [
						Math.round(parseFloat(match[1])),
						Math.round(parseFloat(match[2])),
						Math.round(parseFloat(match[3])),
						parseFloat(match[4] || "1"),
					]
				}
			}

			// 2. Handle Hex
			if (color.startsWith("#")) {
				let hex = color.slice(1)
				if (hex.length === 3)
					hex = hex
						.split("")
						.map((s) => s + s)
						.join("")
				return [
					Math.round(parseInt(hex.slice(0, 2), 16)),
					Math.round(parseInt(hex.slice(2, 4), 16)),
					Math.round(parseInt(hex.slice(4, 6), 16)),
					1,
				]
			}

			return null
		},

		correlateColor: function (val) {
			if (!val) return null

			const targetRgba = this.getRgbaArray(val)
			if (!targetRgba) return null

			const palettes = [
				this.getSetting("color.palette.theme"),
				this.getSetting("color.palette.default"),
				this.getSetting("color.palette.custom"),
			]

			for (let p of palettes) {
				if (p && Array.isArray(p)) {
					const match = p.find((c) => {
						// Check both original 'color' and enriched 'rgb'
						const cRgba1 = this.getRgbaArray(c.rgb)
						const cRgba2 = this.getRgbaArray(c.color)

						if (!cRgba1 && !cRgba2) return false

						const check = (arr) => {
							if (!arr) return false
							// ALPHA-IGNORANT MATCH:
							// If R, G, B match exactly, we treat it as the parent token
							// even if it has transparency (like hover states).
							return (
								arr[0] === targetRgba[0] &&
								arr[1] === targetRgba[1] &&
								arr[2] === targetRgba[2]
							)
						}

						return check(cRgba1) || check(cRgba2)
					})
					if (match) return match
				}
			}
			return null
		},

		correlateGradient: function (val) {
			const grads = [
				this.getSetting("color.gradients.theme"),
				this.getSetting("color.gradients.default"),
			]
			const target = this.recast(val)

			for (let g of grads) {
				if (g && Array.isArray(g)) {
					const match = g.find((item) =>
						this.recast(target).includes(
							this.recast(item.gradient),
						),
					)
					if (match) return match
				}
			}
			return null
		},

		correlateDuotone: function (val) {
			const duos = [
				this.getSetting("color.duotone.theme"),
				this.getSetting("color.duotone.default"),
			]
			const target = this.recast(val)

			for (let d of duos) {
				if (d && Array.isArray(d)) {
					const match = d.find((item) =>
						this.recast(target).includes(this.recast(item.slug)),
					)
					if (match) return match
				}
			}
			return null
		},

		translateToPx: function (val) {
			if (!val) return 0
			if (typeof val === "number") return val
			if (val.includes("px")) return parseFloat(val)

			// Handle rem/em (estimate 16px base)
			if (val.includes("rem") || val.includes("em")) {
				return parseFloat(val) * 16
			}

			// For complex strings (clamp, calc), we can't easily translate without a dummy element.
			// But for correlation, we usually have a computed px value from the browser.
			return parseFloat(val) || 0
		},

		correlateFontSize: function (val) {
			const sizes = [
				this.getSetting("typography.fontSizes.theme"),
				this.getSetting("typography.fontSizes.default"),
			]
			const targetPx = this.translateToPx(val)

			for (let s of sizes) {
				if (s && Array.isArray(s)) {
					const match = s.find((f) => {
						const fPx = this.translateToPx(f.size)
						if (fPx === 0) return false
						// Use 10% tolerance for fluid typography
						const diff = Math.abs(fPx - targetPx)
						return diff / fPx < 0.1
					})
					if (match) return match
				}
			}
			return null
		},

		/**
		 * Recast: Standardizes strings for comparison.
		 * Removes quotes, normalizes spacing, and lowercases.
		 */
		recast: function (val, mode = "standard") {
			if (!val || typeof val !== "string") return val
			let clean = val.trim()

			if (mode === "font") {
				// Special Font Recast: Strip quotes, take first family, lowercase
				clean = clean
					.replace(/['"]/g, "")
					.split(",")[0]
					.trim()
					.toLowerCase()
			} else if (mode === "classes") {
				// Class Recast: Normalize spaces, remove UI noise
				clean = clean
					.replace(/sd-ghost-hover/g, "")
					.replace(/\s+/g, " ")
					.trim()
			} else {
				// Standard Recast: Lowercase and trim
				clean = clean.toLowerCase()
			}

			return clean
		},

		correlateFontFamily: function (val) {
			const families = [
				this.getSetting("typography.fontFamilies.theme"),
				this.getSetting("typography.fontFamilies.default"),
			]
			const targetFam = this.recast(val, "font")

			for (let fList of families) {
				if (fList && Array.isArray(fList)) {
					const match = fList.find((f) => {
						const fFam = this.recast(f.fontFamily, "font")
						return (
							fFam.includes(targetFam) || targetFam.includes(fFam)
						)
					})
					if (match) return match
				}
			}
			return null
		},

		correlateSpacing: function (val) {
			const scale = this.getSetting("spacing.spacingScale.steps")
			const sizes = this.getSetting("spacing.spacingSizes")
			const targetPx = this.translateToPx(val)

			const list = scale || sizes
			if (Array.isArray(list)) {
				const match = list.find((s) => {
					const sPx = this.translateToPx(s.size)
					if (sPx === 0) return false
					const diff = Math.abs(sPx - targetPx)
					return diff / sPx < 0.1
				})
				if (match) return match
			}

			if (scale && typeof scale === "object" && !Array.isArray(scale)) {
				for (let key in scale) {
					const sPx = this.translateToPx(scale[key])
					if (sPx === 0) continue
					const diff = Math.abs(sPx - targetPx)
					if (diff / sPx < 0.1) return { slug: key, size: scale[key] }
				}
			}
			return null
		},

		// --- PRESET DETECTION (Source of Truth) ---
		detectPreset: function (propType, val, classes, inlineStyle) {
			let result = { type: "inherit", value: "inherit", rendered: val }

			// 1. Check Classes (Theme Presets)
			if (classes) {
				const cleanClasses = this.recast(classes, "classes")
				const classList = cleanClasses.split(" ")
				let match = null

				if (propType === "color") {
					match = classList.find(
						(c) =>
							c.startsWith("has-") &&
							c.endsWith("-color") &&
							!c.includes("background"),
					)
					if (match) {
						const slug = match
							.replace("has-", "")
							.replace("-color", "")
						return { type: "slug", value: slug, rendered: val }
					}
				} else if (propType === "backgroundColor") {
					match = classList.find(
						(c) =>
							c.startsWith("has-") &&
							c.endsWith("-background-color"),
					)
					if (match) {
						const slug = match
							.replace("has-", "")
							.replace("-background-color", "")
						return { type: "slug", value: slug, rendered: val }
					}
				} else if (propType === "fontSize") {
					match = classList.find(
						(c) => c.startsWith("has-") && c.endsWith("-font-size"),
					)
					if (match) {
						const slug = match
							.replace("has-", "")
							.replace("-font-size", "")
						return { type: "slug", value: slug, rendered: val }
					}
				} else if (propType === "duotone") {
					match = classList.find((c) => c.startsWith("wp-duotone-"))
					if (match) {
						const slug = match.replace("wp-duotone-", "")
						return {
							type: "slug",
							value: slug,
							rendered: "Duotone Filter",
						}
					}
				}
			}

			// 2. Check Inline Styles (Manual Overrides via Variables)
			// Example: style="color: var(--wp--preset--color--contrast)"
			if (inlineStyle) {
				// Simple regex check for known WP preset variable patterns
				const varRegex = new RegExp(
					`var\\(--wp--preset--${propType === "fontSize" ? "font-size" : propType}--([a-zA-Z0-9-]+)\\)`,
				)
				const match = inlineStyle.match(varRegex)
				if (match && match[1]) {
					return { type: "slug", value: match[1], rendered: val }
				}

				// Check for raw values in inline style (True Manual Override)
				// e.g. style="color: #123456" or "font-size: 20px"
				if (
					inlineStyle.includes(
						`${propType === "fontSize" ? "font-size" : propType}:`,
					)
				) {
					return { type: "raw", value: val, rendered: val }
				}
			}

			// 3. Fallback: Correlators (Computed Value Match)
			// Sometimes presets don't use classes but output CSS variables directly that resolve to these values.
			if (propType === "color" || propType === "backgroundColor") {
				const match = this.correlateColor(val)
				if (match)
					return { type: "slug", value: match.slug, rendered: val }
			}
			if (propType === "fontSize") {
				const match = this.correlateFontSize(val)
				if (match)
					return { type: "slug", value: match.slug, rendered: val }
			}
			if (propType === "fontFamily") {
				const match = this.correlateFontFamily(val)
				if (match)
					return { type: "slug", value: match.slug, rendered: val }
			}
			if (propType === "spacing") {
				const match = this.correlateSpacing(val)
				if (match)
					return { type: "slug", value: match.slug, rendered: val }
			}

			// 4. Default to Inherit unless explicitly raw (handled above)
			// PHASE 5.2: Resolve the inherited value semantic match
			const trace = this.resolveInheritedToken(propType, val)
			if (trace) {
				return {
					type: "inherit-trace",
					value: "inherit",
					traceSlug: trace.slug,
					traceValue: trace.value,
					rendered: val,
				}
			}

			return { type: "inherit", value: "inherit", rendered: val }
		},

		resolveInheritedToken: function (propType, val) {
			if (!val) return null
			let match = null

			switch (propType) {
				case "color":
				case "backgroundColor":
					match = this.correlateColor(val)
					return match
						? { slug: match.slug, value: match.color }
						: null
				case "fontSize":
					match = this.correlateFontSize(val)
					return match
						? { slug: match.slug, value: match.size }
						: null
				case "fontFamily":
					match = this.correlateFontFamily(val)
					return match
						? { slug: match.slug, value: match.fontFamily }
						: null
				case "spacing":
					match = this.correlateSpacing(val)
					return match
						? { slug: match.slug, value: match.size }
						: null
			}
			return null
		},

		correlateShadow: function (val) {
			const shadows = [
				this.getSetting("shadow.presets.theme"),
				this.getSetting("shadow.presets.default"),
			]
			for (let sList of shadows) {
				if (sList && Array.isArray(sList)) {
					const match = sList.find((s) => s.shadow === val)
					if (match) return match
				}
			}
			return null
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

		reselectElement: function (e) {
			const index = $(e.currentTarget).data("index")
			const iframe = document.getElementById("sd-retail-frame")
			if (iframe && iframe.contentWindow) {
				iframe.contentWindow.postMessage(
					{ type: "sd_request_reselection", index: index },
					"*",
				)
			}
		},

		renderInspectorPanel: function (data) {
			let panel = $("#sd-inspector-panel")
			if (!panel.length) {
				this.buildPanelSkeleton()
				panel = $("#sd-inspector-panel")
			}

			const classes = data.className
			const inlineStyle = data.inlineStyle
			const breadcrumbs = data.breadcrumbs || []

			// 0) BREADCRUMBS
			this.renderBreadcrumbs(breadcrumbs, data.selectedIndex)

			// 1) BLOCK
			$("#sd-insp-block-type").text(data.block)

			// ID
			if (data.id) {
				$("#sd-insp-block-id")
					.text("#" + data.id)
					.show()
			} else {
				$("#sd-insp-block-id").hide()
			}

			// GLOBAL BASELINE TOGGLE
			const isCanvas = data.block === "Canvas"
			if (isCanvas) {
				$(".sd-insp-baseline-section").show()
				this.renderGlobalBaseline()
			} else {
				$(".sd-insp-baseline-section").hide()
			}

			// Classes
			if (classes) {
				const cls = classes
					.replace(/sd-ghost-hover/g, "")
					.replace(/\s+/g, " ")
					.trim()
				if (cls) {
					$("#sd-insp-block-classes").text(
						"." + cls.split(" ").join(" ."),
					)
					$(".sd-insp-classes-row").show()
				} else {
					$(".sd-insp-classes-row").hide()
				}
			} else {
				$(".sd-insp-classes-row").hide()
			}

			// 2) COLORS
			// Text
			const textData = this.detectPreset(
				"color",
				data.styles.color,
				classes,
				inlineStyle,
			)
			this.renderColorRow("#sd-insp-text-color", textData, "Text")

			// Background
			if (
				data.styles.backgroundColor !== "rgba(0, 0, 0, 0)" &&
				data.styles.backgroundColor !== "transparent"
			) {
				$("#sd-insp-bg-row").show()
				const bgData = this.detectPreset(
					"backgroundColor",
					data.styles.backgroundColor,
					classes,
					inlineStyle,
				)
				this.renderColorRow("#sd-insp-bg-color", bgData, "Background")
			} else {
				$("#sd-insp-bg-row").hide()
			}

			// Gradient
			if (
				data.styles.backgroundImage &&
				data.styles.backgroundImage !== "none" &&
				data.styles.backgroundImage.includes("gradient")
			) {
				$("#sd-insp-grad-row").show()
				const gradMatch = this.correlateGradient(
					data.styles.backgroundImage,
				)
				const val = gradMatch ? gradMatch.slug : "Custom Gradient"

				const el = $("#sd-insp-grad-color")
				const swatch = el.closest(".sd-color-row").find(".sd-swatch")
				swatch.css("background-image", data.styles.backgroundImage)

				if (gradMatch) {
					el.html(`<span class="sd-token-pill">${val}</span>`)
				} else {
					el.html(`<span class="sd-meta-value">${val}</span>`)
				}
			} else {
				$("#sd-insp-grad-row").hide()
			}

			// Duotone
			const duoData = this.detectPreset(
				"duotone",
				"",
				classes,
				inlineStyle,
			)
			if (duoData && duoData.type === "slug") {
				$("#sd-insp-duo-row").show()
				$("#sd-insp-duo-color").html(
					`<span class="sd-token-pill">${duoData.value}</span>`,
				)
			} else {
				$("#sd-insp-duo-row").hide()
			}

			// 3) TYPOGRAPHY
			// Family
			const fontData = this.detectPreset(
				"fontFamily",
				data.styles.fontFamily,
				classes,
				inlineStyle,
			)
			// Rendered Family cleanup
			const cleanFamily = data.styles.fontFamily
				.split(",")[0]
				.replace(/['"]/g, "")
			this.renderDoubleField(
				"#sd-insp-font-family",
				"Family",
				fontData, // Passing full object now
				"Rendered Family",
				cleanFamily,
				fontData.type === "slug",
			)

			// Size
			const sizeData = this.detectPreset(
				"fontSize",
				data.styles.fontSize,
				classes,
				inlineStyle,
			)
			this.renderDoubleField(
				"#sd-insp-font-size",
				"Size",
				sizeData, // Passing full object now
				"Rendered Size",
				data.styles.fontSize,
				sizeData.type === "slug",
			)

			// 4) SHADOWS
			const shadowVal = data.styles.boxShadow
			if (shadowVal && shadowVal !== "none") {
				$(".sd-insp-shadow-group").show()
				const shadowMatch = this.correlateShadow(shadowVal)
				this.renderTokenField(
					"#sd-insp-shadow-val",
					shadowMatch ? shadowMatch.name : "Custom Shadow",
					!!shadowMatch,
				)
			} else {
				$(".sd-insp-shadow-group").hide()
			}

			// 5) SPACING
			this.renderSpacingList(data.styles)
			this.updateBoxModel(data.styles) // Keep box model chart

			panel.addClass("active")
		},

		// --- SEMANTIC TRACE RENDERERS ---

		/**
		 * Renders a value with a "Trace" to its origin.
		 * - If it matches a Theme Token: Shows Pill + Slug.
		 * - If it is Raw CSS: Shows Value + "Manual" tag.
		 */
		renderGlobalBaseline: function () {
			const palette = this.getSetting("color.palette.theme") || []
			const fonts = this.getSetting("typography.fontSizes.theme") || []
			const families =
				this.getSetting("typography.fontFamilies.theme") || []

			let paletteHtml = ""
			palette.slice(0, 10).forEach((c) => {
				paletteHtml += `<div class="sd-baseline-swatch" style="background:${
					c.color
				}" title="${c.slug}: ${c.color}"></div>`
			})

			const html = `
                <div class="sd-baseline-grid">
                    <div class="sd-baseline-stat">
                        <span class="sd-stat-num">${palette.length}</span>
                        <span class="sd-stat-label">Colors</span>
                    </div>
                    <div class="sd-baseline-stat">
                        <span class="sd-stat-num">${fonts.length}</span>
                        <span class="sd-stat-label">Sizes</span>
                    </div>
                    <div class="sd-baseline-stat">
                        <span class="sd-stat-num">${families.length}</span>
                        <span class="sd-stat-label">Fonts</span>
                    </div>
                </div>
                <div class="sd-baseline-palette">
                    ${paletteHtml}
                </div>
            `
			$("#sd-insp-baseline-content").html(html)
		},

		renderField: function (elId, label, data, renderedValue) {
			const el = $(elId)
			let html = ""

			const type = data ? data.type : null
			const val = this.recast(data ? data.value : renderedValue)
			const displayVal = this.recast(renderedValue)

			if (type === "slug") {
				html = `
                    <div class="sd-trace-row">
                        <span class="sd-token-pill" title="System Preset: ${val}">${val}</span>
                        <span class="sd-meta-sub">${displayVal}</span>
                    </div>`
			} else if (type === "inherit-trace") {
				const tSlug = this.recast(data.traceSlug)
				const tVal = this.recast(data.traceValue)
				html = `
                    <div class="sd-trace-row">
                        <span class="sd-meta-sub">inherit <span class="sd-trace-hint">(${tSlug} ${tVal})</span></span>
                    </div>`
			} else if (val === "inherit") {
				// EVEN FOR INHERIT: If we have a rendered color, show it!
				const colorHint =
					displayVal && displayVal !== "inherit"
						? `<span class="sd-trace-hint">(${displayVal})</span>`
						: ""
				html = `
                    <div class="sd-trace-row">
                        <span class="sd-meta-sub">inherit ${colorHint}</span>
                    </div>`
			} else {
				html = `
                    <div class="sd-trace-row">
                        <span class="sd-meta-value">${displayVal}</span>
                        <span class="sd-tag-manual" title="This value does not match any theme preset">Manual</span>
                    </div>`
			}

			el.html(html)
		},

		renderColorRow: function (elId, data, label) {
			const el = $(elId)
			const parent = el.closest(".sd-color-row")
			const val = data.rendered || data.value
			const swatch = parent.find(".sd-swatch")

			// Handle Transparency/None
			if (val === "rgba(0, 0, 0, 0)" || val === "transparent") {
				if (label === "Background") {
					parent.hide()
					return
				}
			}
			parent.show()

			// Set visual swatch
			swatch.css("background-color", val)

			this.renderField(elId, label, data, val)
		},

		renderDoubleField: function (
			elId,
			label1,
			data1,
			label2,
			val2,
			isToken,
		) {
			const el = $(elId)
			const val1 = typeof data1 === "object" ? data1.value : data1
			const type1 = typeof data1 === "object" ? data1.type : null

			let v1Html = ""
			if (type1 === "slug") {
				v1Html = `<div class="sd-trace-row"><span class="sd-token-pill">${val1}</span></div>`
			} else if (type1 === "inherit-trace") {
				v1Html = `<div class="sd-trace-row"><span class="sd-meta-sub">inherit <span class="sd-trace-hint">(${data1.traceSlug} ${data1.traceValue})</span></span></div>`
			} else if (val1 === "inherit") {
				v1Html = `<div class="sd-trace-row"><span class="sd-meta-sub">inherit</span></div>`
			} else {
				v1Html = `<div class="sd-trace-row"><span class="sd-meta-value">${val1}</span><span class="sd-tag-manual">Manual</span></div>`
			}

			// Construct the two-line lookup
			const html = `
                <div class="sd-double-row">
                    <div class="sd-dr-line"><span class="sd-label">${label1}:</span> ${v1Html}</div>
                    <div class="sd-dr-line"><span class="sd-label">${label2}:</span> <div class="sd-trace-row"><span class="sd-meta-value">${val2}</span></div></div>
                </div>
             `
			el.html(html)
		},

		renderSpacingList: function (styles) {
			const s = styles.spacing
			// Always show containers, but maybe empty
			const pCont = $("#sd-insp-padding-list")
			const mCont = $("#sd-insp-margin-list")
			pCont.empty()
			mCont.empty()

			const hasPadding = [s.pt, s.pr, s.pb, s.pl].some(
				(v) => v && v !== "0px",
			)
			const hasMargin = [s.mt, s.mr, s.mb, s.ml].some(
				(v) => v && v !== "0px",
			)

			if (hasPadding) {
				this.renderSpacingGroup(
					"Padding",
					s.pt,
					s.pr,
					s.pb,
					s.pl,
					pCont,
				)
			}
			if (hasMargin) {
				this.renderSpacingGroup("Margin", s.mt, s.mr, s.mb, s.ml, mCont)
			}

			// If neither, we might show "Spacing: None" or just leave empty? User asked for lists.
		},

		renderSpacingGroup: function (label, t, r, b, l, container) {
			container.show()
			if (t === r && r === b && b === l) {
				const match = this.correlateSpacing(t)
				const slug = match ? match.slug : null
				container.append(this.buildSpacingDoubleRow(label, slug, t))
			} else {
				container.append(
					`<div class="sd-spacing-header">${label}</div>`,
				)
				const sides = [
					{ k: "Top", v: t },
					{ k: "Right", v: r },
					{ k: "Bottom", v: b },
					{ k: "Left", v: l },
				]
				sides.forEach((side) => {
					if (side.v && side.v !== "0px") {
						const match = this.correlateSpacing(side.v)
						container.append(
							this.buildSpacingDoubleRow(
								side.k,
								match ? match.slug : null,
								side.v,
							),
						)
					}
				})
			}
		},

		buildSpacingDoubleRow: function (label, slug, val) {
			let v1Html = ""
			if (slug) {
				v1Html = `<span class="sd-token-pill">${slug}</span>`
			} else {
				v1Html = `<span class="sd-meta-sub">-</span>` // No slug
			}

			return `
                <div class="sd-double-row sd-spacing-row">
                    <div class="sd-dr-line">
                        <span class="sd-label">${label}:</span>
                        ${v1Html}
                        <span class="sd-label" style="margin-left:8px;">Rendered:</span> <span class="sd-meta-value">${val}</span>
                    </div>
                </div>
             `
		},

		updateBoxModel: function (styles) {
			// Box Model Chart Removed in Phase 4.8 per "Exact Match" list requested by user?
			// User list didn't explicitly ask for the chart, but didn't say remove it.
			// The request says: "5) Spacing ... Padding: slug: size Rendered size: actual size ... (if has different spacing...)"
			// It does NOT mention the box chart.
			// However, Previous prompt said "Place Box Model Chart at bottom".
			// I will HIDE it for now to strictly match the text output requested, or leave it at bottom.
			// "The required info output by the HUD does not match this list..."
			// I'll leave the chart function but NOT call it, or call it at very bottom if container exists.

			const fmt = (val) =>
				!val || val === "0px" ? "-" : val.replace("px", "")
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
			$("#sd-insp-w").text(styles.contentW || "-")
			$("#sd-insp-h").text(styles.contentH || "-")
		},

		buildPanelSkeleton: function () {
			const panelHtml = `
            <div id="sd-inspector-panel">
                <div class="sd-insp-header">
                     <div class="sd-insp-header-main">
                        <strong id="sd-insp-title">Inspector</strong>
                        <button class="sd-btn-icon sd-inspector-close"><span class="dashicons dashicons-no"></span></button>
                     </div>
                     <div id="sd-insp-breadcrumbs"></div>
                </div>
                <div class="sd-insp-content">

                    <div class="sd-insp-section">
                        <div class="sd-insp-block-header">
                            <span id="sd-insp-block-type"></span>
                            <span id="sd-insp-block-id" class="sd-meta-sub"></span>
                        </div>
                        <div class="sd-insp-classes-row" style="display:none;">
                            <code id="sd-insp-block-classes"></code>
                        </div>
                    </div>


                    <div class="sd-insp-section sd-insp-baseline-section" style="display:none;">
                         <label>Global Baseline</label>
                         <div id="sd-insp-baseline-content"></div>
                    </div>

                    <div class="sd-insp-section">
                        <label>Colors</label>
                        <div class="sd-color-row">
                            <span class="sd-swatch"></span>
                            <div class="sd-col-info">
                                <span class="sd-label">Text</span>
                                <div id="sd-insp-text-color"></div>
                            </div>
                        </div>
                        <div class="sd-color-row" id="sd-insp-bg-row">
                            <span class="sd-swatch"></span>
                            <div class="sd-col-info">
                                <span class="sd-label">Background</span>
                                <div id="sd-insp-bg-color"></div>
                            </div>
                        </div>
                        <div class="sd-color-row" id="sd-insp-grad-row" style="display:none;">
                            <span class="sd-swatch"></span>
                            <div class="sd-col-info">
                                <span class="sd-label">Gradient</span>
                                <div id="sd-insp-grad-color"></div>
                            </div>
                        </div>
                         <div class="sd-color-row" id="sd-insp-duo-row" style="display:none;">
                            <span class="sd-swatch"></span>
                            <div class="sd-col-info">
                                <span class="sd-label">Duotone</span>
                                <div id="sd-insp-duo-color"></div>
                            </div>
                        </div>
                    </div>

                    <div class="sd-insp-section">
                        <label>Typography</label>
                        <div class="sd-detail-row"><span class="sd-label">Family</span> <div id="sd-insp-font-family"></div></div>
                        <div class="sd-detail-row"><span class="sd-label">Size</span> <div id="sd-insp-font-size"></div></div>
                    </div>

                    <div class="sd-insp-section sd-insp-shadow-group" style="display:none;">
                         <label>Shadows</label>
                         <div id="sd-insp-shadow-val"></div>
                    </div>

                    <div class="sd-insp-section">
                        <label>Spacing</label>

                        <div id="sd-insp-padding-list" class="sd-spacing-list"></div>
                        <div id="sd-insp-margin-list" class="sd-spacing-list"></div>

                        <div class="sd-box-model-container">
                             <div class="sd-box-layer sd-box-margin">
                                 <span class="sd-box-label">margin</span>
                                 <div class="sd-box-top" id="sd-box-mt">-</div>
                                 <div class="sd-box-row">
                                    <div class="sd-box-left" id="sd-box-ml">-</div>
                                    <div class="sd-box-layer sd-box-border">
                                        <div class="sd-box-top" id="sd-box-bt">-</div>
                                        <div class="sd-box-row">
                                            <div class="sd-box-left" id="sd-box-bl">-</div>
                                            <div class="sd-box-layer sd-box-padding">
                                                <span class="sd-box-label">padding</span>
                                                <div class="sd-box-top" id="sd-box-pt">-</div>
                                                <div class="sd-box-row">
                                                    <div class="sd-box-left" id="sd-box-pl">-</div>
                                                    <div class="sd-box-content"><span id="sd-insp-w"></span>x<span id="sd-insp-h"></span></div>
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
			url = url.split("#")[0]
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

		renderBreadcrumbs: function (crumbs, activeIndex) {
			const container = $("#sd-insp-breadcrumbs")
			if (!container.length) return
			container.empty()

			crumbs.forEach((crumb, i) => {
				const isLast = i === crumbs.length - 1
				const isActive = i === activeIndex
				const label = crumb.name === "body" ? "Site Root" : crumb.name
				const html = `
                    <span class="sd-breadcrumb-item ${isActive ? "active" : ""}" data-index="${i}" title="${crumb.tagName}">
                        ${label}
                    </span>
                    ${!isLast ? '<span class="sd-breadcrumb-sep">/</span>' : ""}
                `
				container.append(html)
			})
		},
	}

	if (window.self === window.top)
		$(document).ready(function () {
			RetailSystem.init()
		})
	else $("html").addClass("sd-in-frame")
})(jQuery)
