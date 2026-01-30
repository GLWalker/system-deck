/**
 * SystemDeck - WordPress Native Grid Experiment
 * Using wp.components.__experimentalGrid (WordPress's own grid system)
 */
;(function (wp, $) {
	const {
		render,
		useState,
		useEffect,
		useMemo,
		useRef,
		Fragment,
		createElement: el,
	} = wp.element
	const {
		__experimentalGrid: Grid,
		Card,
		CardBody,
		Panel,
		PanelBody,
		Button,
		DropdownMenu,
		MenuGroup,
		MenuItem,
		Spinner,
	} = wp.components || {}

	// -------------------------------------------------------------------------
	// ICONS
	// -------------------------------------------------------------------------

	// Custom Horizontal Resize Icon (<-->)
	const DOUBLE_ARROW_ICON = el(
		"svg",
		{
			width: 20,
			height: 20,
			viewBox: "0 0 24 24",
			xmlns: "http://www.w3.org/2000/svg",
			style: { transform: "rotate(90deg)" },
		},
		el("path", {
			d: "M16.59 5.59L18 7l-6 6 6 6-1.41 1.41L10.17 14.83 16.59 5.59zm-9.18 0L6 7l6 6-6 6 1.41 1.41L13.83 14.83 7.41 5.59z",
			fill: "currentColor",
		}),
	)

	// -------------------------------------------------------------------------
	// MASONRY ITEM COMPONENT
	// -------------------------------------------------------------------------
	const MasonryItem = ({ children, style, ...props }) => {
		const contentRef = useRef(null)
		const [span, setSpan] = useState(1)

		useEffect(() => {
			if (!contentRef.current) return

			const resizeObserver = new ResizeObserver(() => {
				const content = contentRef.current
				if (!content) return

				const height = content.getBoundingClientRect().height
				const rowHeight = 1 // 1px precision
				const gap = 0 // Row gap is handled by margin

				const rowSpan = Math.ceil((height + 20) / (rowHeight + gap))
				setSpan(rowSpan)
			})

			resizeObserver.observe(contentRef.current)
			return () => resizeObserver.disconnect()
		}, [])

		return el(
			"div",
			{
				...props,
				style: {
					...style,
					gridRowEnd: `span ${span}`,
					marginBottom: "20px", // Visual Gap
				},
			},
			el("div", { ref: contentRef, style: { height: "100%" } }, children),
		)
	}

	// -------------------------------------------------------------------------
	// COMPONENT: WidgetShell (The Unified Native Wrapper)
	// -------------------------------------------------------------------------
	const WidgetShell = ({
		title,
		children,
		widthControl,
		isCollapsed,
		onToggle,
		dragHandle,
		className = "",
	}) => {
		return el(
			"div",
			{
				className: `postbox ${className} ${isCollapsed ? "closed" : ""}`,
				style: {
					height: "auto",
					marginBottom: 0,
					display: "flex",
					flexDirection: "column",
				},
			},
			// The Header
			el(
				"div",
				{ className: "postbox-header" },
				el(
					"h2",
					{
						className: "hndle ui-sortable-handle",
						style: {
							cursor: "move",
							display: "flex",
							alignItems: "center",
							flexGrow: 1,
							fontSize: "14px",
							fontWeight: 600,
							color: "var(--sd-)",
							padding: "8px 0",
						},
					},
					el("span", {}, title), // Clean Title (No Icon)
				),

				// The Controls Area (Right Aligned)
				el(
					"div",
					{
						className: "handle-actions",
						style: {
							display: "flex",
							alignItems: "center",
							gap: "0",
						},
					},
					// 1. Width Changer (Double Arrow)
					widthControl,

					// 2. Collapse Button (Standard WP)
					el(Button, {
						icon: isCollapsed ? "arrow-down" : "arrow-up",
						label: isCollapsed ? "Expand" : "Collapse",
						onClick: onToggle,
						className: "handlediv",
						style: {
							width: "36px", // Standard WP hit area
							height: "36px",
							padding: 0,
							color: "#a7aaad",
							boxShadow: "none",
							background: "transparent",
							display: "flex",
							alignItems: "center",
							justifyContent: "center",
						},
					}),
				),
			),
			// The Content
			el(
				"div",
				{
					className: "inside",
					style: {
						flexGrow: 1,
						margin: 0,
						padding: 0,
						position: "relative",
						display: isCollapsed ? "none" : "flex",
						flexDirection: "column",
					},
				},
				children,
			),
		)
	}

	// -------------------------------------------------------------------------
	// COMPONENT: DashboardWidgetFrame (The Tunnel)
	// -------------------------------------------------------------------------
	const DashboardWidgetFrame = ({ widgetId, minHeight = 15 }) => {
		const iframeRef = useRef(null)
		const adminUrl = window.sd_vars?.admin_url || "/wp-admin/"
		const nonce = window.sd_vars?.nonce || ""

		const url = new URL(adminUrl + "admin.php", window.location.origin)
		url.searchParams.set("page", "sd-dashboard-tunnel")
		url.searchParams.set("widget", widgetId)
		url.searchParams.set("nonce", nonce)

		// Auto-reload iframe when computer wakes from sleep
		useEffect(() => {
			let lastTime = Date.now()

			const checkWake = () => {
				const currentTime = Date.now()
				// If more than 5 seconds have passed since last check, computer likely slept
				if (currentTime > lastTime + 5000) {
					// Reload the iframe
					if (iframeRef.current) {
						const currentSrc = iframeRef.current.src
						iframeRef.current.src = ""
						setTimeout(() => {
							if (iframeRef.current) {
								iframeRef.current.src = currentSrc
							}
						}, 100)
					}
				}
				lastTime = currentTime
			}

			// Check every 2 seconds
			const interval = setInterval(checkWake, 2000)

			// Also listen for visibility change (tab becomes active again)
			const handleVisibilityChange = () => {
				if (!document.hidden && iframeRef.current) {
					// Small delay to let things settle
					setTimeout(() => {
						if (iframeRef.current) {
							const currentSrc = iframeRef.current.src
							iframeRef.current.src = ""
							setTimeout(() => {
								if (iframeRef.current) {
									iframeRef.current.src = currentSrc
								}
							}, 100)
						}
					}, 500)
				}
			}

			document.addEventListener(
				"visibilitychange",
				handleVisibilityChange,
			)

			return () => {
				clearInterval(interval)
				document.removeEventListener(
					"visibilitychange",
					handleVisibilityChange,
				)
			}
		}, [widgetId])

		return el(
			"div",
			{
				className: "sd-proxy-frame-wrapper",
				style: {
					width: "100%",
					height: "auto",
					display: "flex",
					flexDirection: "column",
				},
			},
			el("iframe", {
				ref: iframeRef,
				src: url.toString(),
				frameBorder: "0",
				scrolling: "no",
				loading: "lazy",
				allowTransparency: "true",
				style: {
					width: "100%",
					height: "auto",
					flexGrow: 1,
					background: "transparent",
					border: "none",
					overflow: "hidden",
					minHeight: minHeight + "px",
				},
			}),
		)
	}

	// -------------------------------------------------------------------------
	// AJAX & UTILS
	// -------------------------------------------------------------------------
	const apiFetch = (action, data = {}) => {
		const ajaxUrl = window.sd_vars?.ajax_url || ajaxurl
		const nonce = window.sd_vars?.nonce || ""
		return new Promise((resolve, reject) => {
			$.ajax({
				url: ajaxUrl,
				type: "POST",
				dataType: "json",
				data: { action: `sd_${action}`, nonce: nonce, ...data },
				success: (res) =>
					res.success ? resolve(res.data) : reject(res.data || res),
				error: (xhr) => reject(xhr),
			})
		})
	}

	const saveLayout = (items, workspaceId, collapsedMap = {}) => {
		apiFetch("save_layout", {
			workspaceId: workspaceId,
			layout: JSON.stringify(
				items.map((item) => ({
					id: item.id,
					type: item.type,
					settings: {
						collapsed: !!collapsedMap[item.id],
						...(item.settings || {}),
					},
					position: {
						w: item.w || item.span || 6,
						...(item.position || {}),
					},
				})),
			),
		})
			.then(() => {})
			.catch((err) => console.error("❌ Save error:", err))
	}

	// -------------------------------------------------------------------------
	// TOOLBOX
	// -------------------------------------------------------------------------
	const ToolBox = ({ registry, activeItems, onToggle }) => {
		if (!registry) return null
		const widgets = Object.values(registry)
		return el(
			Fragment,
			{},
			widgets.map((widget) => {
				const isSelected = activeItems.some(
					(item) => item.id === widget.id && item.type === "widget",
				)
				const id = `${widget.id}-hide`
				return el(
					"label",
					{ key: widget.id, htmlFor: id },
					el("input", {
						className: "hide-postbox-tog",
						name: id,
						type: "checkbox",
						id: id,
						value: widget.id,
						checked: isSelected,
						onChange: () => onToggle(widget.id, !isSelected),
					}),
					widget.title,
				)
			}),
		)
	}

	// -------------------------------------------------------------------------
	// MAIN APP
	// -------------------------------------------------------------------------
	const ExperimentalGridApp = () => {
		const manifest = window.SD_Manifest || {}
		const [registry, setRegistry] = useState(manifest.registry || {})
		const isInitialMount = useRef(true)
		const getWidgetData = (id) => (registry ? registry[id] : null)
		const normalizePins = (raw) =>
			Array.isArray(raw) ? raw : raw ? Object.values(raw) : []
		const pins = normalizePins(manifest.user?.pins)
		// Local state for widget collapse
		const [collapsedWidgets, setCollapsedWidgets] = useState(() => {
			const initial = {}
			const layout = manifest.user?.layout || []
			layout.forEach((item) => {
				if (item.settings?.collapsed) {
					initial[item.id] = true
				}
			})
			return initial
		})

		// Normalize items to ensure w is at top level for easy grid consumption
		const [items, setItems] = useState(() => {
			const rawLayout = manifest.user?.layout || []
			return rawLayout.map((item) => ({
				...item,
				w: item.position?.w || item.w || 6,
			}))
		})
		const [draggedIndex, setDraggedIndex] = useState(null)
		const [columns, setColumns] = useState(12)

		const fetchHydratedWidget = (widgetId) => {
			if (registry[widgetId]?.content || registry[widgetId]?.isLoading)
				return
			setRegistry((prev) => ({
				...prev,
				[widgetId]: { ...prev[widgetId], isLoading: true },
			}))
			apiFetch("render_widget", { widget: widgetId })
				.then((data) => {
					setRegistry((prev) => ({
						...prev,
						[widgetId]: {
							...prev[widgetId],
							content: data.html || data.content,
							isLoading: false,
						},
					}))
				})
				.catch((err) => {
					setRegistry((prev) => ({
						...prev,
						[widgetId]: {
							...prev[widgetId],
							content: `<div class="sd-error">Failed to load content.</div>`,
							isLoading: false,
						},
					}))
				})
		}

		useEffect(() => {
			const updateColumns = () => {
				const width = window.innerWidth
				setColumns(width > 782 ? 12 : width > 600 ? 6 : 2)
			}
			updateColumns()
			window.addEventListener("resize", updateColumns)
			return () => window.removeEventListener("resize", updateColumns)
		}, [])

		useEffect(() => {
			const handleWidgetToggle = (event, widgetId, isSelected) => {
				if (isSelected) {
					setItems((prev) => {
						if (
							prev.some(
								(item) =>
									item.id === widgetId &&
									item.type === "widget",
							)
						)
							return prev
						return [...prev, { id: widgetId, type: "widget", w: 6 }]
					})
					const widget = registry[widgetId]
					if (
						widget &&
						!widget.content &&
						!widget.id.startsWith("sd_proxy_")
					) {
						fetchHydratedWidget(widgetId)
					}
				} else {
					setItems((prev) =>
						prev.filter(
							(item) =>
								!(
									item.id === widgetId &&
									item.type === "widget"
								),
						),
					)
				}
			}
			$(document).on("sd_widget_toggled", handleWidgetToggle)
			return () =>
				$(document).off("sd_widget_toggled", handleWidgetToggle)
		}, [registry])

		useEffect(() => {
			const timer = setTimeout(() => {
				isInitialMount.current = false
			}, 2000)
			return () => clearTimeout(timer)
		}, [])

		useEffect(() => {
			if (isInitialMount.current) return
			if (!registry || Object.keys(registry).length === 0) return
			const timer = setTimeout(() => {
				saveLayout(items, manifest.slug || "default", collapsedWidgets)
				$(document).trigger("sd_layout_updated", [items])
			}, 1000)
			return () => clearTimeout(timer)
		}, [items, collapsedWidgets])

		// Handlers
		const toggleWidgetCollapse = (id) => {
			setCollapsedWidgets((prev) => ({ ...prev, [id]: !prev[id] }))
		}

		const updateWidgetSpan = (widgetId, newSpan) => {
			const updatedItems = items.map((item) =>
				item.id === widgetId && item.type === "widget"
					? { ...item, w: newSpan }
					: item,
			)
			setItems(updatedItems)
		}

		const handleUnpin = (pinId) => {
			setItems((prev) => prev.filter((item) => item.id !== pinId))
		}

		const handleDragStart = (index) => (e) => {
			if (
				e.target.closest(".sd-toolbox-item") ||
				e.target.closest(".handle-actions")
			)
				return
			setDraggedIndex(index)
			e.dataTransfer.effectAllowed = "move"
			e.currentTarget.style.opacity = "0.4"
		}
		const handleDragEnd = (e) => {
			e.currentTarget.style.opacity = "1"
			setDraggedIndex(null)
		}
		const handleDragOver = (index) => (e) => {
			e.preventDefault()
			e.dataTransfer.dropEffect = "move"
			if (draggedIndex === null || draggedIndex === index) return
			const newItems = [...items]
			const draggedItem = newItems[draggedIndex]
			newItems.splice(draggedIndex, 1)
			newItems.splice(index, 0, draggedItem)
			setItems(newItems)
			setDraggedIndex(index)
		}

		if (!Grid)
			return el(
				"div",
				{ className: "sd-error" },
				"WordPress Grid component not available",
			)

		const gridItems = useMemo(() => {
			return items
				.map((item, index) => {
					// --- PIN RENDERING LOGIC (Restored) ---
					if (item.type === "pin") {
						const pinData = pins.find((p) => p.id === item.id)
						if (!pinData) return null

						const unpinButton = el(Button, {
							icon: "no-alt",
							label: "Unpin",
							onClick: (e) => {
								e.stopPropagation()
								handleUnpin(item.id)
							},
							style: {
								position: "absolute",
								top: "4px",
								right: "4px",
								minWidth: "24px",
								height: "24px",
								padding: "0",
							},
							isSmall: true,
							isDestructive: true,
						})

						return el(
							"div",
							{
								key: item.id,
								id: item.id,
								className: `sd-grid-pin ${draggedIndex === index ? "sd-drag-active" : ""}`,
								style: {
									gridColumn: "span 3",
									cursor: "grab",
									position: "relative",
								},
								draggable: true,
								onDragStart: handleDragStart(index),
								onDragEnd: handleDragEnd,
								onDragOver: handleDragOver(index),
							},
							el(
								Card,
								{
									size: "small",
									style: { position: "relative" },
								},
								unpinButton,
								el(
									CardBody,
									{ size: "small" },
									el(
										"div",
										{
											style: {
												fontSize: "12px",
												fontWeight: 600,
											},
										},
										pinData.label || pinData.title,
									),
									el("div", {
										style: {
											fontSize: "14px",
											marginTop: "4px",
										},
										dangerouslySetInnerHTML: {
											__html: pinData.value || "",
										},
									}),
								),
							),
						)
					}
					// --- WIDGET RENDERING LOGIC ---
					else if (item.type === "widget") {
						const data = getWidgetData(item.id)
						if (!data) return null

						// Span Logic
						const customSpan = item.w || item.span || null
						let columnSpan = customSpan
							? Math.min(customSpan, columns)
							: columns === 12
								? 6
								: columns === 6
									? 3
									: 2

						const isProxy =
							item.id.startsWith("sd_proxy_") ||
							item.id.startsWith("wpseo") ||
							item.id.startsWith("woocommerce")
						const isCollapsed = collapsedWidgets[item.id] || false

						// 1. WIDTH CONTROL (Native Dropdown)
						const widthControl =
							columns === 12 && DropdownMenu
								? el(
										DropdownMenu,
										{
											icon: DOUBLE_ARROW_ICON,
											label: "Change Width",
											className: "sd-width-dropdown",
											popoverProps: {
												position: "bottom left",
												className: "sd-width-popover",
											},
											toggleProps: {
												style: {
													color: "#a7aaad",
													padding: 0,
													width: "36px",
													height: "36px",
													boxShadow: "none",
												},
												className: "sd-width-toggle",
											},
										},
										({ onClose }) =>
											el(
												MenuGroup,
												{},
												[
													{
														label: "Auto",
														val: null,
													},
													{ label: "1/4", val: 3 },
													{ label: "1/3", val: 4 },
													{ label: "1/2", val: 6 },
													{ label: "2/3", val: 8 },
													{ label: "Full", val: 12 },
												].map((opt) =>
													el(
														MenuItem,
														{
															key: opt.label,
															icon:
																item.w ===
																opt.val
																	? "yes"
																	: null,
															onClick: () => {
																updateWidgetSpan(
																	item.id,
																	opt.val,
																)
																onClose()
															},
														},
														opt.label,
													),
												),
											),
									)
								: null

						// 2. DRAG HANDLE REMOVED

						// 3. CONTENT
						let contentElement
						const origin =
							data.origin ||
							(isProxy ? item.id.replace("sd_proxy_", "") : null)

						if (isProxy && origin) {
							contentElement = el(DashboardWidgetFrame, {
								widgetId: origin,
								minHeight: 15,
							})
						} else {
							let innerHTML = data.content
							if (!innerHTML) {
								if (registry[item.id]?.isLoading)
									innerHTML =
										'<div class="sd-widget-loading"><span class="spinner is-active" style="float:none;margin:0 5px 0 0;"></span>Loading...</div>'
								else if (!registry[item.id]?.content) {
									setTimeout(
										() => fetchHydratedWidget(item.id),
										0,
									)
									innerHTML =
										'<div class="sd-widget-loading">Loading Widget...</div>'
								} else
									innerHTML =
										'<div class="sd-widget-error">Unavailable</div>'
							}
							contentElement = el("div", {
								className: "sd-widget-content-wrapper",
								style: { padding: "12px" },
								dangerouslySetInnerHTML: { __html: innerHTML },
							})
						}

						return el(
							MasonryItem,
							{
								key: item.id,
								id: item.id,
								className: `sd-grid-widget ${isProxy ? "sd-proxy-widget" : ""} ${draggedIndex === index ? "sd-drag-active" : ""}`,
								"data-grid": {
									i: item.id,
									x: 0,
									y: 0,
									w: columnSpan,
									h: 1,
								},
								style: {
									gridColumn: `span ${columnSpan}`,
									cursor: "default",
								},
								draggable: true,
								onDragStart: handleDragStart(index),
								onDragEnd: handleDragEnd,
								onDragOver: handleDragOver(index),
							},
							el(
								WidgetShell,
								{
									title: data.title || "Widget",
									widthControl: widthControl,
									isCollapsed: isCollapsed,
									onToggle: () =>
										toggleWidgetCollapse(item.id),
								},
								contentElement,
							),
						)
					}
					return null
				})
				.filter(Boolean)
		}, [items, pins, columns, draggedIndex, registry, collapsedWidgets])

		return el(
			"div",
			{ className: "sd-wp-grid-experiment" },
			el(
				Grid,
				{
					className: "sd-native-grid",
					style: {
						gridTemplateColumns: `repeat(${columns}, 1fr)`,
						display: "grid",
						gap: "0 20px", // 0 Row Gap, 20px Col Gap
						gridAutoRows: "1px", // High precision
						gridAutoFlow: "dense",
					},
				},
				gridItems,
			),
		)
	}

	function mountApp() {
		const root = document.getElementById("sd-react-root")
		if (root) render(el(ExperimentalGridApp), root)

		const toolboxRoot = document.getElementById("sd-toolbox-content")
		if (toolboxRoot && window.SD_Manifest) {
			const manifest = window.SD_Manifest
			const ToolBoxStandalone = () => {
				const [registry] = useState(manifest.registry || {})
				const [items, setItems] = useState(manifest.user?.layout || [])
				useEffect(() => {
					const syncItems = (e, newItems) => setItems(newItems)
					$(document).on("sd_layout_updated", syncItems)
					return () => $(document).off("sd_layout_updated", syncItems)
				}, [])
				const toggleWidget = (widgetId, isSelected) => {
					if (isSelected)
						setItems((prev) => [
							...prev,
							{ id: widgetId, type: "widget", w: 6 },
						])
					else
						setItems((prev) =>
							prev.filter(
								(item) =>
									!(
										item.id === widgetId &&
										item.type === "widget"
									),
							),
						)
					$(document).trigger("sd_widget_toggled", [
						widgetId,
						isSelected,
					])
				}
				return el(ToolBox, {
					registry: registry,
					activeItems: items,
					onToggle: toggleWidget,
				})
			}
			render(el(ToolBoxStandalone), toolboxRoot)
		}
	}
	$(document).ready(mountApp)
	$(document).on("sd_workspace_rendered", () => setTimeout(mountApp, 0))
})(window.wp, jQuery)
