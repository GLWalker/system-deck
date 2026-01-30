/**
 * SystemDeck Inspector Engine (The "Magic Mouse")
 * Injected into the Retail Iframe to handle hover/click inspection.
 */
;(function ($) {
	"use strict"

	const Inspector = {
		active: false,
		hovered: null,

		init: function () {
			// Only run if we are inside the Retail System iframe
			if (window.self === window.top) return

			console.log("SystemDeck Inspector: Active")
			this.active = true
			this.injectStyles()
			this.bindEvents()
		},

		injectStyles: function () {
			const style = document.createElement("style")
			style.id = "sd-inspector-css"
			style.textContent = `
                .sd-ghost-hover {
                    outline: 2px solid #2271b1 !important;
                    outline-offset: -2px;
                    cursor: crosshair !important;
                    background: rgba(34, 113, 177, 0.05) !important;
                    position: relative;
                    z-index: 10000;
                }
                .sd-ghost-label {
                    position: absolute;
                    top: -24px;
                    left: 0;
                    background: #2271b1;
                    color: #fff;
                    font-family: system-ui, -apple-system, sans-serif;
                    font-size: 10px;
                    font-weight: 600;
                    padding: 2px 6px;
                    border-radius: 2px 2px 0 0;
                    white-space: nowrap;
                    pointer-events: none;
                    z-index: 10001;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
            `
			document.head.appendChild(style)
		},

		bindEvents: function () {
			const self = this

			// HOVER: Draw Ghost Box
			document.body.addEventListener(
				"mouseover",
				function (e) {
					if (!self.active) return
					e.stopPropagation()

					// Cleanup previous
					if (self.hovered && self.hovered !== e.target) {
						self.clearHighlight(self.hovered)
					}

					// Identify Target (Prioritize Blocks)
					let target = e.target
					const block = target.closest("[data-sd-block]")

					// If the target is just a generic div inside a block, highlight the block instead
					if (block && !target.hasAttribute("data-sd-block")) {
						target = block
					}

					self.highlight(target)
				},
				true,
			) // Capture phase

			// CLICK: Select & Report
			document.body.addEventListener(
				"click",
				function (e) {
					if (!self.active) return
					e.preventDefault()
					e.stopPropagation()

					const target = self.hovered || e.target
					self.select(target)
				},
				true,
			)
		},

		highlight: function (el) {
			this.hovered = el
			el.classList.add("sd-ghost-hover")

			// Add Label
			let name =
				el.getAttribute("data-sd-block") || el.tagName.toLowerCase()
			if (el.id) name += "#" + el.id

			const label = document.createElement("div")
			label.className = "sd-ghost-label"
			label.innerText = name
			el.appendChild(label)
		},

		clearHighlight: function (el) {
			if (!el) return
			el.classList.remove("sd-ghost-hover")
			const label = el.querySelector(".sd-ghost-label")
			if (label) label.remove()
		},

		select: function (el) {
			// 1. Gather Data
			const computed = window.getComputedStyle(el)
			const blockName = el.getAttribute("data-sd-block") || "html"

			const payload = {
				type: "sd_element_selected",
				data: {
					tagName: el.tagName.toLowerCase(),
					id: el.id,
					className: el.className
						.replace("sd-ghost-hover", "")
						.trim(),
					block: blockName,
					box: {
						width: el.offsetWidth,
						height: el.offsetHeight,
						top: el.offsetTop,
						left: el.offsetLeft,
					},
					styles: {
						color: computed.color,
						backgroundColor: computed.backgroundColor,
						fontFamily: computed.fontFamily,
						fontSize: computed.fontSize,
						fontWeight: computed.fontWeight,
						spacing: {
							padding: computed.padding,
							margin: computed.margin,
						},
					},
				},
			}

			// 2. Send to Parent (Retail System)
			window.parent.postMessage(payload, "*")

			// 3. Visual Feedback
			this.clearHighlight(el)
			// Optional: Add a "selected" persistent outline here
		},
	}

	// Auto-start
	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", () => Inspector.init())
	} else {
		Inspector.init()
	}
})(jQuery)
