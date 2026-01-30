/**
 * SystemDeck Inspector Engine
 * Phase 4 Upgrade: Deep Structure Capture
 */
;(function ($) {
	"use strict"

	const Inspector = {
		active: false,
		hovered: null,

		init: function () {
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
                    position: absolute; top: -24px; left: 0;
                    background: #2271b1; color: #fff;
                    font-family: system-ui, sans-serif; font-size: 10px; font-weight: 600;
                    padding: 2px 6px; border-radius: 2px 2px 0 0;
                    pointer-events: none; z-index: 10001;
                }
            `
			document.head.appendChild(style)
		},

		bindEvents: function () {
			const self = this
			document.body.addEventListener(
				"mouseover",
				function (e) {
					if (!self.active) return
					e.stopPropagation()
					if (self.hovered && self.hovered !== e.target)
						self.clearHighlight(self.hovered)

					let target = e.target
					const block = target.closest("[data-sd-block]")
					if (block && !target.hasAttribute("data-sd-block"))
						target = block

					self.highlight(target)
				},
				true,
			)

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
			let name =
				el.getAttribute("data-sd-block") || el.tagName.toLowerCase()
			if (el.id) name += "#" + el.id

			// Avoid duplicates
			if (!el.querySelector(".sd-ghost-label")) {
				const label = document.createElement("div")
				label.className = "sd-ghost-label"
				label.innerText = name
				el.appendChild(label)
			}
		},

		clearHighlight: function (el) {
			if (!el) return
			el.classList.remove("sd-ghost-hover")
			const label = el.querySelector(".sd-ghost-label")
			if (label) label.remove()
		},

		select: function (el) {
			const computed = window.getComputedStyle(el)
			const blockName = el.getAttribute("data-sd-block") || "html"

			// PHASE 4: Capture Deep Box Model
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
						// Content Box Dimensions (Approx)
						contentW:
							el.clientWidth -
							parseFloat(computed.paddingLeft) -
							parseFloat(computed.paddingRight),
						contentH:
							el.clientHeight -
							parseFloat(computed.paddingTop) -
							parseFloat(computed.paddingBottom),
					},
					styles: {
						color: computed.color,
						backgroundColor: computed.backgroundColor,
						fontFamily: computed.fontFamily,
						fontSize: computed.fontSize,
						fontWeight: computed.fontWeight,
						// Granular Spacing Data
						spacing: {
							mt: computed.marginTop,
							mr: computed.marginRight,
							mb: computed.marginBottom,
							ml: computed.marginLeft,
							pt: computed.paddingTop,
							pr: computed.paddingRight,
							pb: computed.paddingBottom,
							pl: computed.paddingLeft,
							bt: computed.borderTopWidth,
							br: computed.borderRightWidth,
							bb: computed.borderBottomWidth,
							bl: computed.borderLeftWidth,
						},
					},
				},
			}

			window.parent.postMessage(payload, "*")
			this.clearHighlight(el)
		},
	}

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", () => Inspector.init())
	} else {
		Inspector.init()
	}
})(jQuery)
