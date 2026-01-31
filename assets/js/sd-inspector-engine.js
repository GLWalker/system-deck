/**
 * SystemDeck Inspector Engine
 * Phase 4 Upgrade: Deep Structure Capture
 */
;(function ($) {
	"use strict"

	const Inspector = {
		active: false,
		hovered: null,
		selectedAncestry: [],

		init: function () {
			if (window.self === window.top) return
			console.log("SystemDeck Inspector: Active")
			this.active = true
			this.injectStyles()
			this.bindEvents()
			window.addEventListener("message", this.handleMessage.bind(this))
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

		handleMessage: function (e) {
			if (!e.data || e.data.type !== "sd_request_reselection") return
			const index = e.data.index
			if (this.selectedAncestry[index]) {
				this.select(this.selectedAncestry[index], true)
			}
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

		select: function (el, isReselection = false) {
			const computed = window.getComputedStyle(el)
			const blockName =
				el.getAttribute("data-sd-block") ||
				(el.classList.contains("wp-site-blocks") ? "Canvas" : "html")

			// Capture Ancestry
			if (!isReselection) {
				this.selectedAncestry = []
				let curr = el
				while (curr) {
					this.selectedAncestry.unshift(curr)
					if (curr.tagName === "HTML") break
					curr = curr.parentElement
				}
			}

			const breadcrumbs = this.selectedAncestry
				.map((node) => {
					let name = node.getAttribute("data-sd-block")
					const isBlock = node.className.includes
						? node.className.includes("wp-block")
						: false
					const isSiteRoot = node.classList.contains("wp-site-blocks")
					const isBody = node.tagName === "BODY"
					const isHTML = node.tagName === "HTML"

					// Literal Name Fallback
					if (!name) {
						if (isSiteRoot) name = "Canvas"
						else if (isBody) name = "body"
						else if (isHTML) name = "html"
						else name = node.tagName.toLowerCase()
					}

					// NOISE FILTER: Only keep blocks, structural containers, or root elements
					// If we have a 'Canvas' (wp-site-blocks), we can skip the higher-level html/body clutter
					const hasCanvas = this.selectedAncestry.some((n) =>
						n.classList.contains("wp-site-blocks"),
					)
					if (hasCanvas && (isHTML || isBody)) return null

					if (
						!name &&
						!isBlock &&
						!isSiteRoot &&
						!isBody &&
						!isHTML
					) {
						return null
					}

					return {
						name: name,
						tagName: node.tagName.toLowerCase(),
						isBlock: isBlock,
					}
				})
				.filter((c) => c !== null)

			// 2) COMPONENT-AWARE SUBJECT PROBING
			// Blocks like Button, Image, and Heading often have the "real" styles on a child.
			let subject = el
			let subjectComputed = computed
			let subjectClasses =
				el.className instanceof SVGAnimatedString
					? el.className.baseVal
					: el.className

			if (blockName === "core/button") {
				const link = el.querySelector(".wp-block-button__link")
				if (link) subject = link
			} else if (blockName === "core/image") {
				const img = el.querySelector("img")
				if (img) subject = img
			} else if (blockName.startsWith("core/heading")) {
				const h = el.querySelector("h1, h2, h3, h4, h5, h6")
				if (h) subject = h
			}

			if (subject !== el) {
				subjectComputed = window.getComputedStyle(subject)
				subjectClasses +=
					" " +
					(subject.className instanceof SVGAnimatedString
						? subject.className.baseVal
						: subject.className)
			}

			// CLEANUP: Remove Inspector's own UI noise from the payload
			subjectClasses = subjectClasses
				.replace(/sd-ghost-hover/g, "")
				.replace(/sd-inspectable/g, "")
				.replace(/\s+/g, " ")
				.trim()

			const payload = {
				type: "sd_element_selected",
				data: {
					tagName: el.tagName.toLowerCase(),
					subjectTag: subject.tagName.toLowerCase(),
					id: el.id ? el.id.replace("sd-ghost-label", "").trim() : "",
					className: subjectClasses,
					inlineStyle: (
						(el.getAttribute("style") || "") +
						" " +
						(subject.getAttribute("style") || "")
					)
						.replace(/undefined/g, "")
						.replace(/sd-ghost-hover/g, "")
						.trim(),

					block: blockName,
					breadcrumbs: breadcrumbs,
					selectedIndex: this.selectedAncestry.indexOf(el),

					box: {
						width: el.offsetWidth,
						height: el.offsetHeight,
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
						// Merge Logic: Use Subject for visual attributes, Container for structural
						color:
							subjectComputed.color !== "rgba(0, 0, 0, 0)" &&
							subjectComputed.color !== "transparent"
								? subjectComputed.color
								: computed.color,
						backgroundColor:
							subjectComputed.backgroundColor !==
								"rgba(0, 0, 0, 0)" &&
							subjectComputed.backgroundColor !== "transparent"
								? subjectComputed.backgroundColor
								: computed.backgroundColor,
						backgroundImage:
							subjectComputed.backgroundImage !== "none"
								? subjectComputed.backgroundImage
								: computed.backgroundImage,
						fontFamily: subjectComputed.fontFamily,
						fontSize: subjectComputed.fontSize,
						fontWeight: subjectComputed.fontWeight,
						boxShadow:
							subjectComputed.boxShadow !== "none"
								? subjectComputed.boxShadow
								: computed.boxShadow,
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
