/**
 * Workspace Tool Box Toggle (Exact WordPress Recreation)
 * Mimics WordPress 'Screen Options' behavior using the exact markup structure.
 * Now with state persistence!
 */
jQuery(document).ready(function ($) {
	const TOOLBOX_STATE_KEY = "sd_toolbox_state"

	// Restore toolbox state on page load
	function restoreToolboxState() {
		const savedState = localStorage.getItem(TOOLBOX_STATE_KEY)
		const $link = $("#show-widgets-link")
		const $panel = $("#workspace-meta")

		if (savedState === "open" && $panel.length && $link.length) {
			$panel.show() // Use show() instead of slideDown() for instant restore
			$link
				.addClass("workspace-meta-active")
				.attr("aria-expanded", "true")
		}
	}

	// Save toolbox state
	function saveToolboxState(isOpen) {
		localStorage.setItem(TOOLBOX_STATE_KEY, isOpen ? "open" : "closed")
	}

	// Restore state on load
	restoreToolboxState()

	// Use delegated event binding to handle potential dynamic renders
	$(document).on("click", "#show-widgets-link", function (e) {
		e.preventDefault()

		const $link = $(this)
		// Find panel relative to workspace or by ID
		const $panel = $("#workspace-meta")

		if ($panel.is(":visible")) {
			// Close
			$panel.slideUp("fast", function () {
				$link
					.removeClass("workspace-meta-active")
					.attr("aria-expanded", "false")
				saveToolboxState(false)
			})
		} else {
			// Open
			$panel.slideDown("fast", function () {
				$link
					.addClass("workspace-meta-active")
					.attr("aria-expanded", "true")
				saveToolboxState(true)
			})
		}

		// Notify React
		$(document).trigger("sd_toolbox_toggle")
	})

	// Close when clicking outside
	$(document).on("mouseup", function (e) {
		const $link = $("#show-widgets-link")
		const $panel = $("#workspace-meta")

		if (
			$panel.is(":visible") &&
			!$link.is(e.target) &&
			$link.has(e.target).length === 0 &&
			!$panel.is(e.target) &&
			$panel.has(e.target).length === 0
		) {
			$panel.slideUp("fast", function () {
				$link
					.removeClass("workspace-meta-active")
					.attr("aria-expanded", "false")
				saveToolboxState(false)
			})
		}
	})
})
