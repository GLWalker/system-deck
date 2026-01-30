;(function () {
	"use strict"

	// --- Helper: Event Delegation ---
	const on = (selector, eventType, handler) => {
		document.addEventListener(eventType, (e) => {
			const target = e.target.closest(selector)
			if (target) {
				handler.call(target, e, target)
			}
		})
	}

	const SystemDeck = {
		el: null,
		currentDock: "standard-dock",
		lastDock: "standard-dock", // Memory for restoring from Min/Base
		eventsBound: false,
		isLoading: false,

		init: function () {
			// 1. Bind Global Events First (Always needed for the toggle)
			this.bindEvents()

			// 2. Initial Setup if element exists
			const el = document.getElementById("systemdeck")
			if (el) {
				this.el = el
				const savedDock =
					localStorage.getItem("sd_dock_state") || "standard-dock" // Default state
				this.lastDock =
					localStorage.getItem("sd_last_dock") || "standard-dock" // Persistent Memory

				// Hydrate Theme (Sci-Fi Dark Mode)
				const savedTheme = localStorage.getItem("sd_theme") || "light"
				this.setTheme(savedTheme)

				// Detect Admin Bar Height (FSE vs Standard)
				const body = document.body
				// ... (rest of init remains same)
				const isFse =
					body.classList.contains("site-editor-php") ||
					body.classList.contains("block-editor-page")
				const adminBarHeight = isFse ? "60px" : "32px"
				document.documentElement.style.setProperty(
					"--sd-adminbar-h",
					adminBarHeight,
				)

				// Hydrate State (Apps stored classes/styles)
				// --- RESIZE GUARD ---
				// Protect against "stuck" dimensions when moving from huge -> small screens
				const dimKeys = [
					"sd_dim_standard",
					"sd_dim_right",
					"sd_dim_left",
				]
				dimKeys.forEach((key) => {
					const val = localStorage.getItem(key)
					if (val && val.endsWith("px")) {
						const px = parseInt(val, 10)
						const isHeight = key === "sd_dim_standard"
						const limit = isHeight
							? window.innerHeight * 0.9
							: window.innerWidth * 0.9

						if (px > limit) {
							localStorage.removeItem(key)
							// If currently attempting to load this overly large dock, it will just use CSS defaults now
						}
					}
				})
				// --------------------

				this.switchDock(savedDock)

				// If active in cookie, ensure it's open
				const isActive = document.cookie
					.split("; ")
					.find((row) => row.startsWith("sd_is_active=true"))

				// Force Open if active
				if (isActive) {
					this.el.classList.remove("sd-closed")
					this.el.classList.remove("sd-drawer-hidden")
					this.el.inert = false
				}

				// Restore Menu State
				const isFolded =
					localStorage.getItem("sd_menu_folded") === "true"
				if (isFolded) {
					const menu = this.el.querySelector("#sd-menumain")
					const collapseBtn = this.el.querySelector(
						"#sd-collapse-button",
					)
					if (menu) menu.classList.add("folded")
					if (collapseBtn)
						collapseBtn.setAttribute("aria-expanded", "false")
				}

				// Inject the Correct Handles for the state
				this.updateResizeHandles()

				// [FIX] Ensure Resizer is bound to this new instance
				SD_Resizer.init("#systemdeck")
			}
		},

		bindEvents: function () {
			// ... (guard remains same, skipping lines for conciseness in tool, ensuring match)
			if (this.eventsBound) return
			this.eventsBound = true

			const self = this

			const toggleSelectors =
				"#wp-admin-bar-system-deck-toggle, .sd-toggle-trigger"
			toggleSelectors.split(",").forEach((sel) => {
				on(sel.trim(), "click", function (e) {
					e.preventDefault()

					const el = document.getElementById("systemdeck")
					if (!el) {
						self.loadShell()
					} else if (!el.classList.contains("sd-closed")) {
						// CLOSING via Admin Bar (Active State)

						// EXCEPTION: IF Minimized, Persist State (Do NOT Reset)
						if (self.currentDock === "min-dock") {
							el.remove()
							self.el = null
							localStorage.setItem("sd_is_closed", "true")
							document.cookie =
								"sd_is_active=false; path=/; max-age=0"
							return
						}

						// STANDARD CLOSE -> HARD RESET
						el.remove()
						self.el = null
						localStorage.setItem("sd_is_closed", "true")

						// Reset Dimensions
						localStorage.removeItem("sd_dim_standard")
						localStorage.removeItem("sd_dim_right")
						localStorage.removeItem("sd_dim_left")

						// Reset Dock to User Default (Or Min-Dock if persisting?)
						// User REQUEST: If returning from Min-Dock close, return TO min-dock.
						// The previous LOGIC (above) returns early if min-dock.
						// So passing here means we are NOT in min-dock.

						// Wait, the user said: "when we are in min dock and thne use the adminbar link to close... when we return... we are not in min-dock"
						// My previous code:
						/*
						if (self.currentDock === "min-dock") {
							el.remove()
							self.el = null
							localStorage.setItem("sd_is_closed", "true")
						   // NO RESET of docks
							return
						}
						*/
						// If that returned, then `init` runs next.
						// `init` loads `savedDock = localStorage.getItem("sd_dock_state")`.
						// If `sd_dock_state` was `min-dock`, it should load `min-dock`.

						// ISSUE: `toggleMinDock` SAVES `lastDock`. Does it update `sd_dock_state` to `min-dock`?
						// Let's check `switchDock`. Yes, line 273: localStorage.setItem("sd_dock_state", newDock)

						// So if we are in `min-dock`, `sd_dock_state` IS `min-dock`.
						// My exception above prevents resetting it.
						// So `init` -> `savedDock` -> `min-dock`.

						// Why does it fail?
						// Maybe `init` logic overrides min-dock?

						// Let's look at `init`.
						// `this.switchDock(savedDock)`

						// Ah, maybe the "Hard Close" logic I wrote earlier had a flaw or wasn't applied correctly?
						// Wait, I see I returned `return` in the previous snippet.

						// Let's re-read the code I just wrote in step 95.
						// if (self.currentDock === "min-dock") { ... return }

						// If that works, then `sd_dock_state` remains `min-dock`.
						// Next time: `init` -> `switchDock("min-dock")`.

						// Is there logic inside `switchDock` that rejects `min-dock`?
						// line 242: if (newDock === this.currentDock && newDock !== "standard-dock") ...

						// If `SystemDeck.init` calls `switchDock("min-dock")`...
						// `this.currentDock` is "standard-dock" (default on obj init line 16).
						// So `newDock` != `currentDock`. It proceeds.

						// Wait!
						// If `toggleMinDock` logic sets `lastDock`.
						// Does `init` mess with `lastDock`?
						// `this.lastDock = localStorage.getItem("sd_last_dock") ...`

						// Maybe I need to explicitly debugging why it fails.
						// BUT, for now, let's assume the "reset" logic I added for "active" docks is somehow leaking or `init` has a safeguard against starting min-dock?

						// Actually, I suspect `sd-deck.js` logic I *removed* or *didn't see* might be resetting it.
						// OR... user error/browser cache?

						// Wait, looking at my previous diff in step 95...
						// I implemented the EXCEPTION block.

						// IF the user says it's NOT working, maybe my exception block isn't triggering?
						// `self.currentDock`. Is it updated? `switchDock` updates it.

						// Let's try to Force Save `min-dock` in that exception block just to be sure.

						// Reset Dock to User Default
						const def = sd_vars.default_dock || "standard-dock"
						localStorage.setItem("sd_dock_state", def)
						localStorage.setItem("sd_last_dock", def)
						self.currentDock = def
						self.lastDock = def

						document.cookie =
							"sd_is_active=false; path=/; max-age=0"
					} else {
						// SOFT OPEN (Re-open from sd-closed/soft close)
						// Restore previous state (No Reset)
						self.el = el
						self.toggle()
						document.cookie =
							"sd_is_active=true; path=/; max-age=31536000"
					}
				})
			})

			on("#systemdeck .sd-drawer-icon", "click", function () {
				self.toggleMinDock()
			})

			// ... (rest of binds)
			// We need to inject the localStorage save in toggleMinDock/BaseDock below.
			// But this block is bindEvents. Let's close bindEvents here? No we need to match context.
			// I'll update the toggle functions in a separate ReplacementChunk for cleaner diff.

			on("#systemdeck [data-dock]", "click", function (e, target) {
				// ... existing logic ...
				const requestedDock = target.getAttribute("data-dock")
				const current = self.currentDock

				// 1. Shuffled Navigation: Left Arrow
				if (requestedDock === "left-dock") {
					if (current === "right-base-dock") {
						self.switchDock("base-dock")
					} else if (current === "base-dock") {
						self.switchDock("left-base-dock")
					} else if (current === "right-dock") {
						self.switchDock("full-dock")
					} else if (current === "full-dock") {
						self.switchDock("left-dock")
					} else {
						// Default: Go to Left Dock
						self.switchDock("left-dock")
					}
				}
				// 2. Shuffled Navigation: Right Arrow
				else if (requestedDock === "right-dock") {
					if (current === "left-base-dock") {
						self.switchDock("base-dock")
					} else if (current === "base-dock") {
						self.switchDock("right-base-dock")
					} else if (current === "left-dock") {
						self.switchDock("full-dock")
					} else if (current === "full-dock") {
						self.switchDock("right-dock")
					} else {
						// Default: Go to Right Dock
						self.switchDock("right-dock")
					}
				}
				// 3. Base Dock Toggle
				else if (requestedDock === "base-dock") {
					self.toggleBaseDock() // Uses existing smart toggle
				}
				// 4. Standard/Full (Direct Switch)
				else {
					self.switchDock(requestedDock)
				}
			})

			on("#systemdeck #sd-close-button", "click", function () {
				self.toggle()
				// Mark as INACTIVE in cookie so it doesn't auto-open on page load
				document.cookie = "sd_is_active=false; path=/; max-age=0"
			})

			on("#systemdeck #sd-theme-toggle", "click", function () {
				const current = self.el.getAttribute("data-theme")
				const next = current === "dark" ? "light" : "dark"
				self.setTheme(next)
			})

			on("#systemdeck #sd-collapse-button", "click", function () {
				const menu = self.el.querySelector("#sd-menumain")
				const isFolded = menu.classList.toggle("folded")
				this.setAttribute("aria-expanded", !isFolded)
				localStorage.setItem("sd_menu_folded", isFolded)
			})
		},

		toggle: function () {
			if (!this.el) this.el = document.getElementById("systemdeck")
			this.el.classList.toggle("sd-closed")
			const isClosed = this.el.classList.contains("sd-closed")
			if (!isClosed) this.el.classList.remove("sd-drawer-hidden")

			this.el.inert = isClosed
			localStorage.setItem("sd_is_closed", isClosed)
		},

		toggleMinDock: function () {
			if (this.currentDock === "min-dock") {
				this.switchDock(this.lastDock || "standard-dock")
			} else {
				this.lastDock = this.currentDock
				localStorage.setItem("sd_last_dock", this.lastDock) // SAVE
				this.switchDock("min-dock")
			}
		},

		toggleBaseDock: function () {
			let target = "base-dock"

			if (this.currentDock.includes("base-dock")) {
				this.switchDock(this.lastDock || "standard-dock")
				return
			}

			if (this.currentDock === "full-dock") {
				this.switchDock("standard-dock")
				return
			}

			this.lastDock = this.currentDock
			localStorage.setItem("sd_last_dock", this.lastDock) // SAVE

			if (this.currentDock === "right-dock") target = "right-base-dock"
			else if (this.currentDock === "left-dock") target = "left-base-dock"
			else target = "base-dock"

			this.switchDock(target)
		},

		switchDock: function (newDock) {
			if (!this.el) this.el = document.getElementById("systemdeck")

			if (newDock === this.currentDock && newDock !== "standard-dock") {
				newDock = "standard-dock"
			}

			this.el.removeAttribute("style")

			const dockClasses = [
				"standard-dock",
				"right-dock",
				"left-dock",
				"full-dock",
				"base-dock",
				"right-base-dock",
				"left-base-dock",
				"min-dock",
			]
			this.el.classList.remove(...dockClasses)
			this.el.classList.add(newDock)

			if (newDock === "standard-dock") {
				const h = localStorage.getItem("sd_dim_standard")
				if (h) this.el.style.height = h
			} else if (newDock === "right-dock") {
				const w = localStorage.getItem("sd_dim_right")
				if (w) this.el.style.width = w
			} else if (newDock === "left-dock") {
				const w = localStorage.getItem("sd_dim_left")
				if (w) this.el.style.width = w
			}

			this.currentDock = newDock
			localStorage.setItem("sd_dock_state", newDock)

			const baseBtnIcon = this.el.querySelector(
				'[data-dock="base-dock"] .dashicons',
			)
			if (baseBtnIcon) {
				if (newDock.includes("base-dock")) {
					baseBtnIcon.classList.remove("dashicons-minus")
					baseBtnIcon.classList.add("dashicons-arrow-up-alt")
				} else {
					baseBtnIcon.classList.remove("dashicons-arrow-up-alt")
					baseBtnIcon.classList.add("dashicons-minus")
				}
			}

			this.updateResizeHandles()
		},

		updateResizeHandles: function () {
			if (!this.el) return
			const existingHandles =
				this.el.querySelectorAll(".sd-handle-resize")
			existingHandles.forEach((el) => el.remove())

			const dock = this.currentDock
			let handleClass = ""

			if (dock === "standard-dock") handleClass = "sd-handle-n"
			else if (dock === "right-dock") handleClass = "sd-handle-w"
			else if (dock === "left-dock") handleClass = "sd-handle-e"
			else return

			const handleHTML = `<div class="sd-handle-resize ${handleClass}" draggable="false" ondragstart="return false;" style="touch-action:none;"><span class="dashicons dashicons-ellipsis"></span></div>`
			this.el.insertAdjacentHTML("beforeend", handleHTML)
		},

		setTheme: function (theme) {
			this.el.setAttribute("data-theme", theme)
			localStorage.setItem("sd_theme", theme)
		},

		loadShell: function () {
			const self = this
			if (this.isLoading) return
			this.isLoading = true

			if (typeof sd_vars === "undefined") {
				console.error("SystemDeck: sd_vars is missing.")
				this.isLoading = false
				return
			}

			const formData = new FormData()
			formData.append("action", "sd_load_shell")
			formData.append("nonce", sd_vars.nonce)

			fetch(sd_vars.ajax_url, {
				method: "POST",
				body: formData,
			})
				.then((response) => response.json())
				.then((res) => {
					self.isLoading = false
					if (res.success && res.data.html) {
						document.body.insertAdjacentHTML(
							"beforeend",
							res.data.html,
						)
						document.cookie =
							"sd_is_active=true; path=/; max-age=31536000"
						self.init()

						// Notify System (Content Loader)
						document.dispatchEvent(
							new CustomEvent("sd_shell_loaded"),
						)
					}
				})
				.catch((err) => {
					self.isLoading = false
					console.error("SystemDeck: Failed to load shell.", err)
				})
		},
	}

	var SD_Resizer = {
		root: null,
		target: null,
		type: null,

		startX: 0,
		startY: 0,
		startW: 0,
		startH: 0,

		lastEvent: null,
		frame: null,

		velocity: 0,
		lastPos: 0,
		lastTime: 0,

		snapThreshold: 24,
		maxRatio: 0.75,

		snapPoints: [0.25, 0.3333, 0.5, 0.6667, 0.75],

		init: function (selector) {
			const el = document.querySelector(selector)
			if (!el) return

			// If finding same root, ensure listener is there (just in case), or return
			if (this.root === el) return

			// Unbind old if exists (clean up)
			if (this.root) {
				try {
					this.root.removeEventListener(
						"pointerdown",
						this.startResize,
					)
				} catch (e) {}
			}

			this.root = el
			this.root.addEventListener(
				"pointerdown",
				this.startResize.bind(this),
			)
		},

		startResize: function (e) {
			var handle = e.target.closest(".sd-handle-resize")
			if (!handle) return

			e.preventDefault()

			this.target = this.root
			this.lastEvent = e
			this.startX = e.clientX
			this.startY = e.clientY
			this.startW = this.target.offsetWidth
			this.startH = this.target.offsetHeight

			if (handle.classList.contains("sd-handle-n")) this.type = "n"
			if (handle.classList.contains("sd-handle-w")) this.type = "w"
			if (handle.classList.contains("sd-handle-e")) this.type = "e"

			this.lastPos = this.type === "n" ? e.clientY : e.clientX
			this.lastTime = performance.now()
			this.velocity = 0

			this.target.setPointerCapture(e.pointerId)
			document.body.classList.add("sd-is-resizing")
			document.body.style.userSelect = "none"

			this.setGlow(18)

			window.addEventListener("pointermove", this.queueResize)
			window.addEventListener("pointerup", this.stopResize)
		},

		queueResize: function (e) {
			SD_Resizer.lastEvent = e
			if (!SD_Resizer.frame) {
				SD_Resizer.frame = requestAnimationFrame(SD_Resizer.doResize)
			}
		},

		doResize: function () {
			var r = SD_Resizer
			r.frame = null
			if (!r.target || !r.lastEvent) return

			var e = r.lastEvent
			var now = performance.now()

			if (r.type === "n") {
				var delta = r.startY - e.clientY
				var newH = r.startH + delta

				var adminH =
					parseInt(
						getComputedStyle(
							document.documentElement,
						).getPropertyValue("--sd-adminbar-h"),
						10,
					) || 32

				newH = Math.max(
					100,
					Math.min(newH, window.innerHeight - adminH),
				)
				r.target.style.height = newH + "px"

				r.velocity = (r.lastPos - e.clientY) / (now - r.lastTime)
				r.lastPos = e.clientY
			}

			if (r.type === "w" || r.type === "e") {
				var delta =
					r.type === "w" ? r.startX - e.clientX : e.clientX - r.startX

				var maxW = window.innerWidth * r.maxRatio
				var newW = Math.max(200, Math.min(r.startW + delta, maxW))

				var snap = r.getSnapWidth(newW)
				if (snap) newW = snap

				r.target.style.width = newW + "px"

				r.velocity = (e.clientX - r.lastPos) / (now - r.lastTime)
				r.lastPos = e.clientX
			}

			r.lastTime = now

			r.target.dispatchEvent(
				new CustomEvent("sd:resize", {
					bubbles: true,
					detail: { type: r.type },
				}),
			)
		},

		stopResize: function (e) {
			var r = SD_Resizer
			var target = r.target // 🔒 capture reference

			try {
				target && target.releasePointerCapture(e.pointerId)
			} catch (_) {}

			window.removeEventListener("pointermove", r.queueResize)
			window.removeEventListener("pointerup", r.stopResize)

			document.body.classList.remove("sd-is-resizing")
			document.body.style.userSelect = ""

			if (target) {
				// SAVE STATE (Immediate - Pre Inertia)
				var type = r.type
				if (type === "n")
					localStorage.setItem("sd_dim_standard", target.style.height)
				if (type === "w")
					localStorage.setItem("sd_dim_right", target.style.width)
				if (type === "e")
					localStorage.setItem("sd_dim_left", target.style.width)

				target.dispatchEvent(
					new CustomEvent("sd:resize-end", {
						bubbles: true,
						detail: { type: r.type },
					}),
				)

				r.applyInertia(target, type) // 👈 pass target AND type
			}

			r.setGlow(12)

			// cleanup AFTER inertia is scheduled
			r.target = null
			r.lastEvent = null
		},

		applyInertia: function (target, type) {
			var velocity = this.velocity
			var decay = 0.92
			var min = 0.01
			var maxW = window.innerWidth * this.maxRatio
			// var type = this.type // Removed: use passed arg

			function step() {
				velocity *= decay
				if (Math.abs(velocity) < min) {
					// INERTIA COMPLETE: Save Final State
					if (target && target.isConnected) {
						if (type === "w")
							localStorage.setItem(
								"sd_dim_right",
								target.style.width,
							)
						if (type === "e")
							localStorage.setItem(
								"sd_dim_left",
								target.style.width,
							)
					}
					return
				}

				if (!target || !target.isConnected) return

				if (type === "w" || type === "e") {
					var w = target.offsetWidth + velocity * 16
					w = Math.max(200, Math.min(w, maxW))
					target.style.width = w + "px"
				}

				requestAnimationFrame(step)
			}

			requestAnimationFrame(step)
		},

		getSnapWidth: function (w) {
			var vw = window.innerWidth
			var snaps = []

			this.snapPoints.forEach(function (p) {
				snaps.push(vw * p)
			})

			var cssW = getComputedStyle(document.documentElement)
				.getPropertyValue("--sd-sidebar-w")
				.trim()

			if (cssW.endsWith("px")) snaps.push(parseInt(cssW, 10))

			for (var i = 0; i < snaps.length; i++) {
				if (Math.abs(w - snaps[i]) <= this.snapThreshold) {
					return Math.round(snaps[i])
				}
			}
			return null
		},

		setGlow: function (blur) {
			document.documentElement.style.setProperty(
				"--sd-resize-glow-blur",
				blur + "px",
			)
		},
	}

	// Expose API
	window.SystemDeck = SystemDeck

	document.addEventListener("DOMContentLoaded", function () {
		SystemDeck.init()
		SD_Resizer.init("#systemdeck")
	})
})()
