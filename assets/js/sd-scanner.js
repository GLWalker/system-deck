/**
 * SystemDeck Widget Scanner
 * Handles "Deep Scanning" of the real WordPress Dashboard via an invisible iframe.
 */

/**
 * Clean and format widget titles
 * Handles React widgets, removes noise, and prioritizes user-friendly names
 */
function cleanWidgetTitle(title, widgetId) {
	if (!title) return widgetId

	// Remove common noise words
	title = title
		.replace(
			/Actions|Move up|Move down|Toggle panel|Configure|Settings/gi,
			"",
		)
		.trim()

	// Remove duplicate words (e.g., "SEO SEO Setup" -> "SEO Setup")
	const words = title.split(/\s+/)
	const uniqueWords = []
	words.forEach((word) => {
		if (!uniqueWords.includes(word)) {
			uniqueWords.push(word)
		}
	})
	title = uniqueWords.join(" ")

	// Handle common prefixes
	title = title.replace(/^(AIOSEO|Yoast|WooCommerce|Jetpack)\s+/i, "$1 - ")

	// If title is still just the ID, format it nicely
	if (title === widgetId || title.length < 3) {
		title = widgetId
			.replace(/wpseo-|aioseo-|dashboard_|wc-/gi, "")
			.replace(/[-_]/g, " ")
			.replace(/\b\w/g, (l) => l.toUpperCase())
			.trim()
	}

	// Final cleanup
	title = title.replace(/\s+/g, " ").trim()

	return title || widgetId
}

jQuery(document).ready(function ($) {
	"use strict"

	// 1. SAVE LOGIC
	$(document).on("click", "#sd-save-proxies", function (e) {
		e.preventDefault()
		var btn = $(this)
		var spinner = btn.siblings(".spinner")
		var msg = $("#sd-proxy-save-msg")

		spinner.addClass("is-active")
		var selected = []
		$("#sd-proxy-manager-form input:checked").each(function () {
			selected.push($(this).val())
		})

		var ajaxUrl = window.sd_vars?.ajax_url || ajaxurl
		var nonce = window.sd_vars?.nonce || ""

		$.post(
			ajaxUrl,
			{
				action: "sd_save_proxy_selection",
				widgets: selected,
				nonce: nonce,
			},
			function (res) {
				spinner.removeClass("is-active")
				if (res.success) {
					msg.css("color", "green").text("Saved!")
					setTimeout(function () {
						msg.text("")
					}, 2000)
				} else {
					msg.css("color", "red").text("Error")
				}
			},
		)
	})

	// 2. DEEP SCAN LOGIC
	$(document).on("click", ".sd-deep-scan-btn", function (e) {
		e.preventDefault()
		var btn = $(this)
		var originalText = btn.text()
		btn.text("Loading Dashboard...").prop("disabled", true)

		// Create invisible scanner frame
		var iframe = document.createElement("iframe")
		iframe.style.display = "none"
		iframe.src = "index.php" // Load real dashboard
		document.body.appendChild(iframe)

		iframe.onload = function () {
			btn.text("Scanning DOM...")

			// WAIT for React/JS widgets to mount (Critical for Yoast)
			setTimeout(function () {
				try {
					var doc =
						iframe.contentDocument || iframe.contentWindow.document
					var foundCount = 0

					// A. Find all dashboard widgets
					var targets = $(doc).find(".postbox").toArray()

					// B. Filter out React fragments and duplicates
					var validWidgets = []
					var processedIds = {}

					targets.forEach(function (el) {
						var $el = $(el)
						var id = $el.attr("id")

						// Skip if no ID, already processed, or SystemDeck widget
						if (!id || id.startsWith("sd_") || processedIds[id]) {
							return
						}

						// FILTER: Skip React fragments (elements without proper widget structure)
						// A real widget should have either:
						// 1. A .postbox-header with title
						// 2. A .inside content area
						// 3. Or be a known plugin widget container
						var hasHeader =
							$el.find(".postbox-header, .hndle").length > 0
						var hasInside = $el.find(".inside").length > 0
						var isKnownPlugin = id.match(
							/^(wpseo|aioseo|woocommerce|jetpack|dashboard_)/,
						)

						// Skip if it looks like a fragment (no header AND no inside AND not a known plugin)
						if (!hasHeader && !hasInside && !isKnownPlugin) {
							console.log("Skipping fragment:", id)
							return
						}

						// FILTER: Skip if it's nested inside another .postbox (likely a fragment)
						var parentPostbox = $el.parent().closest(".postbox")
						if (
							parentPostbox.length > 0 &&
							parentPostbox.attr("id") !== id
						) {
							console.log("Skipping nested widget:", id)
							return
						}

						processedIds[id] = true
						validWidgets.push(el)
					})

					// C. Process valid widgets
					validWidgets.forEach(function (el) {
						var $el = $(el)
						var id = $el.attr("id")

						// TITLE EXTRACTION: Pull exact title from widget header
						// Priority order:
						// 1. .postbox-header h2 or .hndle (standard WordPress)
						// 2. .postbox-header .sd-widget-title (SystemDeck custom)
						// 3. Any h2 in the widget
						// 4. Specific plugin headers (Yoast, AIOSEO, etc.)
						var title = ""

						// Try standard WordPress header
						var headerTitle = $el
							.find(".postbox-header h2, .postbox-header .hndle")
							.first()
						if (headerTitle.length) {
							title = headerTitle.text().trim()
						}

						// Try Yoast-specific header
						if (!title) {
							var yoastTitle = $el
								.find(
									".yoast-card-header-title, .wpseo-metabox-title",
								)
								.first()
							if (yoastTitle.length) {
								title = yoastTitle.text().trim()
							}
						}

						// Try AIOSEO-specific header
						if (!title) {
							var aioseoTitle = $el
								.find(
									".aioseo-widget-header, .aioseo-card-header",
								)
								.first()
							if (aioseoTitle.length) {
								title = aioseoTitle.text().trim()
							}
						}

						// Fallback to any h2
						if (!title) {
							title = $el.find("h2").first().text().trim()
						}

						// Last resort: use ID
						if (!title) {
							title = id
						}

						// Clean the title using our helper function
						title = cleanWidgetTitle(title, id)

						// Inject into UI if not already present
						if (
							!$(`#sd-proxy-manager-form input[value="${id}"]`)
								.length
						) {
							var html = `
                                <label class="sd-widget-option" style="display:block; padding:10px; border:1px solid #46b450; background:#f0fbe4; border-radius:4px; cursor:pointer; animation: highlight 1s;">
                                    <input type="checkbox" name="widgets[]" value="${id}" checked>
                                    <span style="font-weight:600; font-size:14px;">${title}</span>
                                    <div style="color:#666; font-size:11px; margin-top:3px; font-family:monospace;">${id} (Deep Scan)</div>
                                </label>`
							$(".sd-widget-grid").prepend(html)
							foundCount++
						}
					})

					btn.text(
						foundCount > 0
							? "Found " + foundCount + " new!"
							: "Scan Complete",
					).css("color", foundCount > 0 ? "green" : "")
				} catch (err) {
					console.error("Scan Error:", err)
					btn.text("Scan Blocked (See Console)")
				}

				setTimeout(() => {
					if (document.body.contains(iframe))
						document.body.removeChild(iframe)
					btn.prop("disabled", false)
						.text(originalText)
						.css("color", "")
				}, 3000)
			}, 2000) // 2 Second Delay for React
		}
	})

	// 3. MANUAL WIDGET BUILDER LOGIC
	$(document).on("click", "#sd-manual-add-btn", function (e) {
		e.preventDefault()
		var id = $("#sd-manual-widget-id").val().trim()
		if (!id) return

		// Clean ID
		id = id.replace(/[^a-zA-Z0-9-_]/g, "")

		if ($(`#sd-proxy-manager-form input[value="${id}"]`).length) {
			alert("Widget already exists in list!")
			return
		}

		// Use the same title cleaning function
		var title = cleanWidgetTitle(id, id)

		var html = `
            <label class="sd-widget-option" style="display:block; padding:10px; border:1px solid #2271b1; background:#f0f6fc; border-radius:4px; cursor:pointer; animation: highlight 1s;">
                <input type="checkbox" name="widgets[]" value="${id}" checked>
                <span style="font-weight:600; font-size:14px;">${title}</span>
                <div style="color:#666; font-size:11px; margin-top:3px; font-family:monospace;">${id} (Manual)</div>
            </label>`
		$(".sd-widget-grid").prepend(html)
		$("#sd-manual-widget-id").val("")
	})
})
