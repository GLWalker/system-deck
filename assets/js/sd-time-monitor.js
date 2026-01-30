/**
 * SystemDeck Time Monitor
 * Handles the live clock ticking for Server, WP, and Browser time.
 */
;(function ($) {
	"use strict"

	const TimeMonitor = {
		init: function () {
			const self = this
			this.interval = setInterval(function () {
				const el = $("#sd-time-module")
				if (el.length) {
					self.wrapper = el
					// Base Timestamps (PHP provided)
					self.serverBase =
						parseInt(self.wrapper.data("server-ts"), 10) * 1000
					self.wpBase =
						parseInt(self.wrapper.data("wp-ts"), 10) * 1000
					self.clientStart = Date.now()

					self.bindEvents()
					self.startTicker()
					clearInterval(self.interval) // Stop searching
				}
			}, 500) // Check every 500ms
		},

		bindEvents: function () {
			const self = this
			this.wrapper.on("click", "#sd-time-ping", function () {
				self.measureLatency()
			})
		},

		startTicker: function () {
			const self = this
			const update = function () {
				const now = Date.now()
				const delta = now - self.clientStart

				// Update Server Time (Simulated)
				self.updateClock(self.serverBase + delta, "server-time")

				// Update WP Time (Simulated based on offset)
				self.updateClock(self.wpBase + delta, "wp-time")

				// Update Local Browser Time
				self.updateClock(now, "user-time")

				requestAnimationFrame(update)
			}
			requestAnimationFrame(update)
		},

		updateClock: function (ts, targetRole) {
			const date = new Date(ts)
			const str = date.toLocaleTimeString([], {
				hour12: false,
				hour: "2-digit",
				minute: "2-digit",
				second: "2-digit",
			})
			this.wrapper.find('[data-role="' + targetRole + '"]').text(str)
		},

		measureLatency: function () {
			const start = Date.now()
			const btn = $("#sd-time-ping")
			const originalText = btn.text()

			btn.prop("disabled", true).text("Pinging...")

			$.post(
				window.sd_vars?.ajax_url || ajaxurl,
				{
					action: "sd_ping_latency",
					nonce: window.sd_vars?.nonce || "",
				},
				function (res) {
					const end = Date.now()
					const latency = end - start

					// Update UI
					$("#sd-ping-val").text(latency + "ms")

					// Flash color
					const color =
						latency < 200
							? "green"
							: latency < 500
								? "orange"
								: "red"
					$("#sd-ping-val").css("color", color)

					btn.prop("disabled", false).text(originalText)
				},
			)
		},
	}

	$(document).ready(function () {
		// Init if present on load
		TimeMonitor.init()

		// Re-init if workspace loads dynamically
		$(document).on("sd_workspace_rendered", function () {
			TimeMonitor.init() // Safe to re-run, check inside init prevents duplicates if we cared, but actually we want to re-bind to new DOM
		})
	})
})(jQuery)
