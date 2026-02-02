/**
 * SystemDeck Inspector Engine - Diagnostic & Failsafe
 * Version 1.5.1 (Glass Box + Unconditional Init)
 * * Changes:
 * - Removed window.self === window.top guard (Fatal in WP Previews)
 * - Added window.SD_Inspector exposure
 * - Added sd_preview=1 auto-activation failsafe
 * - Added verbose console logging
 */
;(function () {
	"use strict"

	const Inspector = {
		active: false,
		hovered: null,
		selected: null,
		isInitialized: false,
		selectedAncestry: [],

		init: function () {
			// Diagnostic Log: Verify Environment
			// We log the hierarchy for debugging, but we DO NOT abort based on it.
			console.log("SD: Inspector Engine Loading...", {
				self: window.self,
				top: window.top,
				isIframe: window.self !== window.top,
				url: window.location.href,
			})

			// [CRITICAL FIX]
			// The guard clause (window.self === window.top) has been REMOVED.
			// WordPress retail previews collapse the window identity, making self === top true.
			// We rely on 'sd_preview=1' and the handshake to determine validity.

			if (this.isInitialized) return
			this.isInitialized = true

			// Expose to Global Scope for Debugging
			window.SD_Inspector = this

			const mount = () => {
				if (!document.body) {
					setTimeout(mount, 50)
					return
				}
				this.injectStyles()
				this.bindEvents()
				this.checkFailsafe()

				// Announce readiness
				console.log("SD: Sending Handshake -> sd_inspector_ready")
				window.parent.postMessage(
					{ command: "sd_inspector_ready" },
					"*",
				)
				window.addEventListener(
					"message",
					this.handleMessage.bind(this),
				)
			}
			mount()
		},

		// Failsafe: Only auto-activate if specifically requested via URL
		checkFailsafe: function () {
			const params = new URLSearchParams(window.location.search)
			if (params.get("sd_inspect_force") === "1") {
				console.log("SD: Force Activation Mode")
				this.active = true
			}
		},

		injectStyles: function () {
			if (document.getElementById("sd-inspector-css")) return
			const style = document.createElement("style")
			style.id = "sd-inspector-css"
			style.textContent = `
                .sd-ghost-hover {
                    outline: 2px dashed #2271b1 !important;
                    outline-offset: -2px !important;
                    cursor: crosshair !important;
                    background: rgba(34, 113, 177, 0.05) !important;
                    position: relative;
                    z-index: 2147483647 !important;
                }
                .sd-ghost-selected {
                    outline: 2px solid #2271b1 !important;
                    outline-offset: -2px !important;
                    background: rgba(34, 113, 177, 0.1) !important;
                    position: relative;
                    z-index: 2147483646 !important;
                }
                .sd-ghost-label {
                    position: absolute;
                    top: -20px;
                    left: 0;
                    background: #2271b1 !important;
                    color: #fff !important;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif !important;
                    font-size: 10px !important;
                    font-weight: 600 !important;
                    padding: 0 6px !important;
                    border-radius: 2px 2px 0 0 !important;
                    line-height: 20px !important;
                    pointer-events: none !important;
                    z-index: 2147483647 !important;
                    white-space: nowrap !important;
                }
            `
			document.head.appendChild(style)
			console.log("SD: Styles Injected")
		},

		bindEvents: function () {
			const self = this

			// Link Interception for Preview Continuity
			document.addEventListener(
				"click",
				(e) => {
					// 0. DO NOT intercept if Inspector is active (Magic Mouse should take over)
					if (this.active) return

					// Only intercept if we are in a preview context
					const params = new URLSearchParams(window.location.search)
					if (params.get("sd_preview") !== "1") return

					const link = e.target.closest("a")
					if (!link || !link.href) return

					// Skip internal anchors/hashes and external links
					const isInternal = link.origin === window.location.origin
					const isHash = link.getAttribute("href").startsWith("#")

					if (!isInternal || isHash) return

					// Stop default navigation to stay within 'Visual Mode'
					e.preventDefault()

					let url = new URL(link.href)
					url.searchParams.set("sd_preview", "1")

					// Carry over current style variation if present
					if (params.has("sd_style")) {
						url.searchParams.set("sd_style", params.get("sd_style"))
					}

					console.log("SD: Intercepted Navigation ->", url.toString())
					window.location.href = url.toString()
				},
				true,
			) // Capture phase to bypass theme listeners

			// 1. Hover/Highlight Logic
			document.addEventListener(
				"mouseover",
				(e) => {
					if (!this.active) return
					e.stopPropagation()

					let target = e.target
					if (
						!target ||
						target === document ||
						target === document.documentElement
					)
						return

					// Filter out SystemDeck's own labels
					if (target.classList.contains("sd-ghost-label")) return

					// Intelligent Targeting (Cursor Aware)
					const contentTags = [
						"P",
						"H1",
						"H2",
						"H3",
						"H4",
						"H5",
						"H6",
						"LI",
						"EM",
						"STRONG",
						"A",
						"SPAN",
						"IMG",
						"BUTTON",
						"CODE",
						"MARK",
					]

					if (!contentTags.includes(target.tagName)) {
						const block = target.closest(
							"[data-block], [data-type], [class*='wp-block-']",
						)
						if (block) target = block
					}

					// Only act if target changed
					if (this.hovered === target) return

					if (this.hovered) this.clearHighlight(this.hovered)
					this.highlight(target)
				},
				true,
			)

			// 2. Clear on Out
			document.addEventListener(
				"mouseout",
				(e) => {
					if (!this.active) return
					if (
						this.hovered &&
						!this.hovered.contains(e.relatedTarget)
					) {
						this.clearHighlight(this.hovered)
					}
				},
				true,
			)

			document.addEventListener(
				"click",
				function (e) {
					if (!self.active) return
					e.preventDefault()
					e.stopPropagation()
					const target = self.hovered || e.target
					if (target) self.select(target)
				},
				true,
			)
			console.log("SD: Events Bound")
		},

		handleMessage: function (e) {
			if (!e.data) return
			if (e.data.command === "sd_inspector_toggle") {
				console.log("SD: Toggle Received", e.data.active)
				this.active = !!e.data.active
				if (!this.active) this.clearHighlight(this.hovered)
			}
			if (e.data.command === "sd_grid_toggle") {
				this.toggleGrid(e.data.active)
			}
			if (e.data.type === "sd_request_reselection") {
				const index = e.data.index
				if (this.selectedAncestry[index]) {
					this.select(this.selectedAncestry[index], true)
				}
			}
		},

		toggleGrid: function (active) {
			let grid = document.getElementById("sd-grid-overlay-layer")
			let legend = document.getElementById("sd-specs-legend")

			if (!grid) {
				// 1. Resolve Layout Variables
				const layout =
					window.sd_env && sd_env.layout ? sd_env.layout : {}
				const contentSize = layout.contentSize || "840px"
				const wideSize = layout.wideSize || "1200px"

				// 2. Inject Styles
				const style = document.createElement("style")
				style.id = "sd-grid-styles"
				style.textContent = `
					:root {
						--sd-grid-content: ${contentSize};
						--sd-grid-wide: ${wideSize};
						--sd-grid-magenta: rgba(255, 0, 255, 0.1);
						--sd-guide-cyan: #00f2ff;
						--sd-guide-orange: #ff9900;
					}
					#sd-grid-overlay-layer {
						position: fixed;
						top: 0; left: 0; right: 0; bottom: 0;
						z-index: 1999999999;
						pointer-events: none;
						mix-blend-mode: exclusion;
						display: none;
					}
					.sd-grid-container {
						max-width: var(--sd-grid-wide);
						margin: 0 auto;
						height: 100%;
						display: grid;
						grid-template-columns: repeat(12, 1fr);
						gap: 20px;
						border-left: 1px solid var(--sd-guide-orange);
						border-right: 1px solid var(--sd-guide-orange);
						position: relative;
					}
					.sd-grid-container::after {
						content: '';
						position: absolute;
						top: 0; bottom: 0;
						left: 50%;
						transform: translateX(-50%);
						width: var(--sd-grid-content);
						border-left: 1px solid var(--sd-guide-cyan);
						border-right: 1px solid var(--sd-guide-cyan);
					}
					.sd-grid-col {
						background: var(--sd-grid-magenta);
						height: 100%;
					}
					/* Legend System */
					#sd-specs-legend {
						position: fixed;
						bottom: 20px;
						right: 20px;
						background: rgba(0, 0, 0, 0.85);
						color: #fff;
						padding: 10px 15px;
						border-radius: 8px;
						font-family: monospace;
						font-size: 11px;
						z-index: 2000000000;
						border: 1px solid rgba(255,255,255,0.1);
						backdrop-filter: blur(5px);
						display: none;
						pointer-events: none;
					}
					.sd-spec-row { margin-bottom: 4px; display: flex; justify-content: space-between; gap: 20px; }
					.sd-spec-label { color: #aaa; }
					.sd-spec-val { color: #00f2ff; font-weight: bold; }
				`
				document.head.appendChild(style)

				// 3. Create Grid Structure
				grid = document.createElement("div")
				grid.id = "sd-grid-overlay-layer"
				const container = document.createElement("div")
				container.className = "sd-grid-container"
				for (let i = 0; i < 12; i++) {
					let col = document.createElement("div")
					col.className = "sd-grid-col"
					container.appendChild(col)
				}
				grid.appendChild(container)
				document.body.appendChild(grid)

				// 4. Create Legend
				legend = document.createElement("div")
				legend.id = "sd-specs-legend"
				legend.innerHTML = `
					<div class="sd-spec-row"><span class="sd-spec-label">Viewport:</span> <span class="sd-spec-val" id="sd-val-vp">--</span></div>
					<div class="sd-spec-row"><span class="sd-spec-label">Content:</span> <span class="sd-spec-val">${contentSize}</span></div>
					<div class="sd-spec-row"><span class="sd-spec-label">Wide:</span> <span class="sd-spec-val">${wideSize}</span></div>
					<div class="sd-spec-row"><span class="sd-spec-label">Spacing:</span> <span class="sd-spec-val" id="sd-val-space">--</span></div>
				`
				document.body.appendChild(legend)

				// 5. Live Update Loop
				const updateSpecs = () => {
					const vp = document.getElementById("sd-val-vp")
					if (vp) vp.textContent = window.innerWidth + "px"

					const space = document.getElementById("sd-val-space")
					if (space) {
						const bodyStyle = window.getComputedStyle(document.body)
						space.textContent = bodyStyle.paddingLeft || "--"
					}
				}
				window.addEventListener("resize", updateSpecs)
				updateSpecs()
			}

			const display = active ? "block" : "none"
			grid.style.display = display
			legend.style.display = display
			console.log("SD: Diagnostic Grid ->", active)
		},

		highlight: function (el) {
			if (!el || !el.classList) return
			this.hovered = el
			el.classList.add("sd-ghost-hover")

			let name =
				el.getAttribute("data-type") || el.getAttribute("data-block")
			if (!name) {
				const classes =
					typeof el.className === "string"
						? el.className
						: el.className?.baseVal || ""
				const match = classes.match(/wp-block-([a-z0-9-]+)/)
				name = match ? "core/" + match[1] : el.tagName.toLowerCase()
			}

			if (!el.querySelector(":scope > .sd-ghost-label")) {
				const label = document.createElement("div")
				label.className = "sd-ghost-label"
				label.innerText = name
				el.appendChild(label)
			}
		},

		clearHighlight: function (el) {
			if (!el || !el.classList) return
			el.classList.remove("sd-ghost-hover")

			// ONLY remove label if it's NOT the selected element
			if (el !== this.selected) {
				const label = el.querySelector(":scope > .sd-ghost-label")
				if (label) label.remove()
			}

			if (this.hovered === el) this.hovered = null
		},

		resolveSubject: function (el, blockName) {
			if (
				!window.sd_env ||
				!sd_env.blockDefinitions ||
				!sd_env.blockDefinitions[blockName]
			) {
				// Fallback to robust heuristics
				if (
					blockName === "core/paragraph" ||
					el.classList.contains("wp-block-paragraph")
				) {
					return el.querySelector("p") || el
				}
				if (
					blockName === "core/button" ||
					el.classList.contains("wp-block-button")
				) {
					return el.querySelector(".wp-block-button__link") || el
				}
				if (blockName && blockName.startsWith("core/heading")) {
					return el.querySelector("h1, h2, h3, h4, h5, h6") || el
				}
				return el
			}

			const def = sd_env.blockDefinitions[blockName]
			let subject = el

			if (def.selectors && def.selectors.root) {
				let selector = def.selectors.root
				if (el.matches(selector)) {
					subject = el
				} else {
					const blockClass = "." + blockName.replace("/", "-")
					if (selector.startsWith(blockClass + " ")) {
						const innerSelector = selector.replace(
							blockClass + " ",
							"",
						)
						const found = el.querySelector(innerSelector)
						if (found) subject = found
					} else {
						const found = el.querySelector(selector)
						if (found) subject = found
					}
				}
			} else if (def.experimentalSelector) {
				const found = el.querySelector(def.experimentalSelector)
				if (found) subject = found
			}

			return subject
		},

		select: function (el, isReselection = false) {
			if (!el) return

			// 0. Manage persistent selection classes
			if (this.selected && this.selected !== el) {
				const prev = this.selected
				prev.classList.remove("sd-ghost-selected")
				// If we are moving away from the previous selection AND it's not currently hovered, purge its label
				if (prev !== this.hovered) {
					const label = prev.querySelector(":scope > .sd-ghost-label")
					if (label) label.remove()
				}
			}

			this.selected = el
			el.classList.add("sd-ghost-selected")

			// Log selection for forensics
			console.log("SD: Selecting Element", el)

			// We don't clear highlight here anymore, we want the solid outline to take over
			// this.clearHighlight(el)

			// 1. Identity Resolution (The Block)
			const blockRoot =
				el.closest("[data-block], [class*='wp-block-']") || el
			let blockName =
				blockRoot.getAttribute("data-type") ||
				blockRoot.getAttribute("data-block")
			if (!blockName) {
				const match = (
					typeof blockRoot.className === "string"
						? blockRoot.className
						: ""
				).match(/wp-block-([a-z0-9-]+)/)
				blockName = match
					? "core/" + match[1]
					: blockRoot.tagName.toLowerCase()
			}

			// 2. Forensic Resolution (The Element to Inspect)
			// Decide if we use the click target directly or dig for the block's content element.
			let subject = this.resolveSubject(blockRoot, blockName)

			// If we moused over/clicked exactly on a text/content node, that IS our forensic subject.
			const contentTags = [
				"P",
				"H1",
				"H2",
				"H3",
				"H4",
				"H5",
				"H6",
				"LI",
				"EM",
				"STRONG",
				"A",
				"SPAN",
				"IMG",
				"BUTTON",
				"CODE",
				"MARK",
			]
			if (contentTags.includes(el.tagName)) {
				subject = el
			}

			// 3. READ STYLES (CRITICAL: Read from subject for accurate sizing/weight)
			const computed = window.getComputedStyle(subject)
			const inline = subject.getAttribute("style") || ""
			const classes =
				typeof subject.className === "string" ? subject.className : ""

			// 4. ANCESTRY & BREADCRUMBS
			if (!isReselection) {
				this.selectedAncestry = []
				let curr = blockRoot
				while (curr) {
					this.selectedAncestry.unshift(curr)
					if (curr.tagName === "HTML") break
					curr = curr.parentElement
				}
			}

			const breadcrumbs = this.selectedAncestry.map((node) => {
				let name =
					node.getAttribute("data-type") ||
					node.getAttribute("data-block")
				if (!name) name = node.tagName.toLowerCase()
				return { name: name, tagName: node.tagName.toLowerCase() }
			})

			// 5. FORENSIC HELPER (Detects if property is explicitly set)
			const forensicValue = (comp, inl, prop, cls) => {
				const propSlug = prop.toLowerCase()
				const inlineLower = (inl || "").toLowerCase()
				const cssName = prop.replace(/([A-Z])/g, "-$1").toLowerCase()
				const isSet =
					inlineLower.includes(cssName + ":") ||
					cls.includes(
						"has-" +
							propSlug
								.replace("backgroundcolor", "background")
								.replace("fontsize", "font-size") +
							"-",
					) ||
					inlineLower.includes("--wp--preset--")
				return {
					rendered: comp[prop],
					isSet: isSet,
					isInherited: !isSet,
				}
			}

			// 6. BUILD PAYLOAD (Structured for RetailSystem.js)
			const payload = {
				type: "sd_element_selected",
				data: {
					block: blockName,
					tagName: subject.tagName.toLowerCase(),
					className: classes,
					breadcrumbs: breadcrumbs,
					selectedIndex: this.selectedAncestry.indexOf(blockRoot),

					isLink: subject.tagName === "A",
					isButton:
						subject.tagName === "BUTTON" ||
						classes.includes("button"),
					isHeading: ["H1", "H2", "H3", "H4", "H5", "H6"].includes(
						subject.tagName,
					),

					htmlAnchor: subject.id || blockRoot.id || null,
					variation:
						(classes.match(/is-style-([a-z0-9-]+)/) || [])[1] ||
						null,
					patternTitle: null, // Harder to detect on frontend, but keeping for parity

					box: {
						width: subject.getBoundingClientRect().width,
						height: subject.getBoundingClientRect().height,
						display: computed.display,
						position: computed.position,
						zIndex: computed.zIndex,
						margin: computed.margin,
						padding: computed.padding,
						gap: computed.gap,
					},

					styles: {
						typography: {
							color: forensicValue(
								computed,
								inline,
								"color",
								classes,
							),
							fontSize: forensicValue(
								computed,
								inline,
								"fontSize",
								classes,
							),
							fontFamily: forensicValue(
								computed,
								inline,
								"fontFamily",
								classes,
							),
							fontWeight: {
								rendered: computed.fontWeight,
								isSet: inline.includes("font-weight:"),
							},
							fontStyle: computed.fontStyle,
							lineHeight: {
								rendered: computed.lineHeight,
								isSet: inline.includes("line-height:"),
							},
							letterSpacing: computed.letterSpacing,
							textTransform: computed.textTransform,
							textDecoration: computed.textDecorationLine,
						},
						colors: {
							background: forensicValue(
								computed,
								inline,
								"backgroundColor",
								classes,
							),
						},
						background: {
							image: {
								rendered: computed.backgroundImage,
								isSet: inline.includes("background-image:"),
							},
						},
						border: {
							radius: {
								rendered: computed.borderRadius,
								isSet: inline.includes("border-radius:"),
							},
							sides: {
								top: `${computed.borderTopWidth} ${computed.borderTopStyle} ${computed.borderTopColor}`,
								right: `${computed.borderRightWidth} ${computed.borderRightStyle} ${computed.borderRightColor}`,
								bottom: `${computed.borderBottomWidth} ${computed.borderBottomStyle} ${computed.borderBottomColor}`,
								left: `${computed.borderLeftWidth} ${computed.borderLeftStyle} ${computed.borderLeftColor}`,
							},
						},
						fx: {
							shadow: {
								rendered: computed.boxShadow,
								isSet: inline.includes("box-shadow:"),
							},
							opacity: {
								rendered: computed.opacity,
								isSet: inline.includes("opacity:"),
							},
						},
					},
				},
			}

			window.parent.postMessage(payload, "*")
			window.parent.postMessage(
				{ command: "sd_inspector_data", data: payload.data },
				"*",
			)
		},
	}

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", () => Inspector.init())
	} else {
		Inspector.init()
	}
})()
