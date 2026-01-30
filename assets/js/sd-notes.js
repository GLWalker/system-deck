/**
 * SystemDeck Notes Widget
 * Handles note creation, saving, pinning, and list management.
 */
;(function ($) {
	"use strict"

	const NotesWidget = {
		interval: null,
		init: function () {
			// Stop any previous search
			if (this.interval) clearInterval(this.interval)

			const self = this
			this.interval = setInterval(function () {
				const el = $("#sd-notes-widget")
				if (el.length) {
					clearInterval(self.interval)

					// Prevent double-init on the same DOM element
					if (el.data("sd-notes-init")) return
					el.data("sd-notes-init", true)

					self.wrapper = el
					self.bindEvents()
					self.loadNotes()
				}
			}, 500)
		},

		editor: null, // CodeMirror instance

		bindEvents: function () {
			const self = this
			this.wrapper.off(".sdNotes")

			// New Note Button
			this.wrapper.on("click.sdNotes", "#sd-note-new", function () {
				self.resetForm()
				if ($("#sd-note-capture").is(":checked")) {
					self.injectCaptureData()
				}
			})

			// Save Note
			this.wrapper.on("click.sdNotes", "#sd-note-save", function () {
				self.saveNote()
			})

			// Delete Note
			this.wrapper.on("click.sdNotes", "#sd-note-delete", function () {
				if (confirm("Are you sure you want to delete this note?")) {
					self.deleteNote($(this).data("id"))
				}
			})

			// Edit Note
			this.wrapper.on("click.sdNotes", ".sd-note-item", function (e) {
				if ($(e.target).closest(".note-actions").length) return
				self.editNote($(this))
			})

			// Pin/Unpin
			this.wrapper.on("click.sdNotes", ".sd-note-pin-btn", function (e) {
				e.stopPropagation()
				self.togglePin($(this).closest("li").data("id"))
			})

			// View All Toggle
			this.wrapper.on(
				"click.sdNotes",
				"#sd-note-trigger-view-all",
				function () {
					self.openViewAll()
				},
			)

			// Close View All
			this.wrapper.on("click.sdNotes", ".sd-drawer-close", function () {
				$("#sd-note-view-all-drawer").removeClass("open")
			})

			// Click Note in View All List
			this.wrapper.on(
				"click.sdNotes",
				"#sd-notes-all-list .sd-note-item",
				function () {
					self.editNote($(this))
					$("#sd-note-view-all-drawer").removeClass("open")
				},
			)

			// Capture URL Checkbox logic
			this.wrapper.on("change.sdNotes", "#sd-note-capture", function () {
				if ($(this).is(":checked")) {
					self.injectCaptureData()
				}
			})

			// Code Snippet Toggle
			this.wrapper.on("change.sdNotes", "#sd-note-is-code", function () {
				self.toggleEditorMode()
			})

			// Todo Checkbox Logic
			this.wrapper.on(
				"change.sdNotes",
				".sd-todo-checkbox",
				function (e) {
					e.preventDefault()
					e.stopPropagation()
					self.toggleTaskStatus($(this))
				},
			)

			// Context Filter Button
			this.wrapper.on(
				"click.sdNotes",
				"#sd-note-context-filter",
				function () {
					$(this).toggleClass("active")
					const icon = $(this).find(".dashicons")
					if ($(this).hasClass("active")) {
						$(this).css("color", "#2271b1") // Active Blue
						icon.css("color", "#2271b1")
					} else {
						$(this).css("color", "#666") // Inactive Gray
						icon.css("color", "#666")
					}
					self.loadNotes()
				},
			)
		},

		openViewAll: function () {
			const self = this
			const drawer = $("#sd-note-view-all-drawer")
			const list = $("#sd-notes-all-list")
			drawer.addClass("open")
			list.html('<li class="loading-text">Loading all notes...</li>')

			$.post(
				window.sd_vars?.ajax_url || ajaxurl,
				{
					action: "sd_get_all_notes",
					nonce: window.sd_vars?.nonce || "",
				},
				function (res) {
					if (res.success) {
						list.empty()
						if (!res.data.notes || res.data.notes.length === 0) {
							list.html(
								'<li class="empty-text">No notes found.</li>',
							)
							return
						}

						res.data.notes.forEach((note) => {
							const safeCode = (note.code_content || "")
								.replace(/&/g, "&amp;")
								.replace(/</g, "&lt;")
								.replace(/>/g, "&gt;")
								.replace(/"/g, "&quot;")

							const taskHtml = self.renderTaskContent(
								note.content,
							)
							const previewHtml = taskHtml
								? taskHtml
								: $("<div>")
										.html(note.content)
										.text()
										.substring(0, 50) + "..."

							const html = `
                            <li class="sd-note-item" data-id="${note.id}" data-excerpt="${note.excerpt || ""}" data-is-code="${note.is_code ? 1 : 0}" data-context="${note.context || ""}">
                                <div style="width:100%">
                                    <span class="note-title" style="font-weight:600">${note.title}</span>
                                    <span class="note-meta" style="font-size:11px;color:#888;">${note.date}</span>
                                    <div class="note-preview" style="font-size:12px;color:#666;margin-top:2px;overflow:hidden;text-overflow:ellipsis;">
                                        ${previewHtml}
                                    </div>
                                </div>
                                <div class="note-content-hidden" style="display:none;">${note.content}</div>
                                <div class="note-code-hidden" style="display:none;">${safeCode}</div>
                            </li>
                        `
							list.append(html)
						})
					} else {
						list.html(
							'<li class="error-text">Error loading notes.</li>',
						)
					}
				},
			)
		},

		injectCaptureData: function () {
			const titleInput = $("#sd-note-title")

			// Auto Title
			if (!titleInput.val()) {
				titleInput.val(document.title)
			}
			// Auto URL is now handled by Excerpt on save
		},

		loadNotes: function () {
			// ... no change needed here yet
			const list = $("#sd-notes-list")
			list.html('<li class="loading-text">Loading...</li>')

			const filterActive = $("#sd-note-context-filter").hasClass("active")
			const context = filterActive ? window.location.href : ""

			$.post(
				window.sd_vars?.ajax_url || ajaxurl,
				{
					action: "sd_get_notes",
					limit: 5,
					context: context,
					nonce: window.sd_vars?.nonce || "",
				},
				function (res) {
					if (res.success) {
						list.empty()
						if (!res.data.notes || res.data.notes.length === 0) {
							list.html(
								'<li class="empty-text" style="color:#aaa;text-align:center;padding:10px;">No recent notes.</li>',
							)
							return
						}

						res.data.notes.forEach((note) => {
							const isPinned = note.is_pinned ? "pinned" : ""
							const pinClass = note.is_pinned ? "active" : ""

							const safeCode = (note.code_content || "")
								.replace(/&/g, "&amp;")
								.replace(/</g, "&lt;")
								.replace(/>/g, "&gt;")
								.replace(/"/g, "&quot;")

							const html = `
                            <li class="sd-note-item ${isPinned}" data-id="${note.id}" data-excerpt="${note.excerpt || ""}" data-is-code="${note.is_code ? 1 : 0}" data-context="${note.context || ""}">
                                <span class="note-title">${note.title}</span>
                                <div class="note-actions">
                                    <span class="dashicons dashicons-admin-post sd-btn-icon sd-note-pin-btn ${pinClass}" title="Pin/Unpin"></span>
                                    <span class="note-meta">${note.date}</span>
                                </div>
                                <div class="note-content-hidden" style="display:none;">${note.content}</div>
                                <div class="note-code-hidden" style="display:none;">${safeCode}</div>
                            </li>
                        `
							list.append(html)
						})
					}
				},
			)
		},

		saveNote: function () {
			const self = this
			const isCode = $("#sd-note-is-code").is(":checked")
			const title = $("#sd-note-title").val().trim()
			const content = $("#sd-note-content").val().trim()

			let codeContent = ""
			if (isCode) {
				if (this.editor && this.editor.codemirror) {
					codeContent = this.editor.codemirror.getValue()
				} else {
					codeContent = $("#sd-note-code-content").val()
				}
			}
			const id = $("#sd-note-id").val()
			const btn = $("#sd-note-save")
			const spinner = $(".sd-spinner")

			if (!title && !content) {
				alert("Please enter a title or content.")
				return
			}

			btn.prop("disabled", true)
			spinner.addClass("active")

			// Handle Capture / Excerpt
			let finalExcerpt = $("#sd-note-excerpt").val()
			if ($("#sd-note-capture").is(":checked")) {
				finalExcerpt = window.location.href
			}

			$.post(
				window.sd_vars?.ajax_url || ajaxurl,
				{
					action: "sd_save_note",
					id: id,
					title: title,
					content: content,
					excerpt: finalExcerpt,
					code_content: codeContent,
					is_code: $("#sd-note-is-code").is(":checked") ? 1 : 0,
					nonce: window.sd_vars?.nonce || "",
				},

				function (res) {
					btn.prop("disabled", false)
					spinner.removeClass("active")

					if (res.success) {
						self.loadNotes()
						self.resetForm()
					} else {
						alert(
							"Error saving note: " +
								(res.data.error || "Unknown"),
						)
					}
				},
			)
		},

		editNote: function (row) {
			const id = row.data("id")
			const title = row.find(".note-title").text()
			const content = row.find(".note-content-hidden").html()
			const codeContent = row.find(".note-code-hidden").text()
			const excerpt = row.data("excerpt")
			const isCode = row.data("is-code") ? true : false

			$("#sd-note-id").val(id)
			$("#sd-note-title").val(title)

			const decodedContent = this.decodeHtml(content)
			$("#sd-note-content").val(decodedContent)
			$("#sd-note-code-content").val(codeContent)
			if (this.editor && this.editor.codemirror) {
				this.editor.codemirror.setValue(codeContent)
			}

			$("#sd-note-excerpt").val(excerpt)
			$("#sd-note-is-code").prop("checked", isCode)

			this.toggleEditorMode()

			const linkBtn = $("#sd-note-visit-link")
			if (
				excerpt &&
				(excerpt.startsWith("http") || excerpt.startsWith("//"))
			) {
				linkBtn.attr("href", excerpt).css("display", "inline-flex")
			} else {
				linkBtn.hide()
			}

			$("#sd-note-delete").show().data("id", id)
			$("#sd-note-save").text("Update Note")

			// Highlight active row?
			$(".sd-note-item").removeClass("active-edit")
			row.addClass("active-edit")
		},

		deleteNote: function (id) {
			const self = this
			$.post(
				window.sd_vars?.ajax_url || ajaxurl,
				{
					action: "sd_delete_note",
					id: id,
					nonce: window.sd_vars?.nonce || "",
				},
				function (res) {
					if (res.success) {
						self.loadNotes()
						self.resetForm()
					}
				},
			)
		},

		togglePin: function (id) {
			const self = this
			$.post(
				window.sd_vars?.ajax_url || ajaxurl,
				{
					action: "sd_pin_note",
					id: id,
					nonce: window.sd_vars?.nonce || "",
				},
				function (res) {
					if (res.success) {
						self.loadNotes() // Reload to sort
					}
				},
			)
		},

		toggleEditorMode: function () {
			const isCode = $("#sd-note-is-code").is(":checked")
			const textWrap = $("#sd-note-content-wrapper")
			const codeWrap = $("#sd-note-code-wrapper")

			if (isCode) {
				// Show BOTH: Textarea (for description) and Code Editor
				textWrap.show()
				codeWrap.slideDown(200)
				this.initEditor()
				if (this.editor && this.editor.codemirror) {
					setTimeout(() => this.editor.codemirror.refresh(), 50)
				}
			} else {
				// Hide Code
				codeWrap.slideUp(200)
			}
		},

		initEditor: function () {
			if (this.editor) return
			if (typeof wp !== "undefined" && wp.codeEditor) {
				const settings = {
					codemirror: {
						mode: "text/html",
						lineNumbers: true,
						indentUnit: 4,
					},
				}
				this.editor = wp.codeEditor.initialize(
					$("#sd-note-code-content"),
					settings,
				)
			}
		},

		resetForm: function () {
			$("#sd-note-id").val("")
			$("#sd-note-title").val("")
			$("#sd-note-content").val("")
			$("#sd-note-excerpt").val("")
			$("#sd-note-visit-link").hide()
			$("#sd-note-delete").hide()
			$("#sd-note-save").text("Save Note")
			$(".sd-note-item").removeClass("active-edit")
			$("#sd-note-capture").prop("checked", false)
			$("#sd-note-is-code").prop("checked", false)

			// Reset UI
			$("#sd-note-content-wrapper").show()
			$("#sd-note-code-wrapper").hide()
			if (this.editor && this.editor.codemirror) {
				this.editor.codemirror.setValue("")
			}
			$("#sd-note-code-content").val("")
		},

		decodeHtml: function (html) {
			var txt = document.createElement("textarea")
			txt.innerHTML = html
			return txt.value
		},

		renderTaskContent: function (content) {
			if (!content || !content.match(/\[(\s|x|X)\]/)) return null

			let index = 0
			const html = content.replace(/\[(\s|x|X)\]/g, function (match) {
				const isChecked = match.toLowerCase().indexOf("x") !== -1
				const cb = `<input type="checkbox" class="sd-todo-checkbox" data-task-index="${index}" ${isChecked ? "checked" : ""} style="margin-right:6px;">`
				index++
				return cb
			})
			return `<div class="sd-todo-list" style="margin-top:4px;">${html}</div>`
		},

		toggleTaskStatus: function (checkbox) {
			const noteItem = checkbox.closest(".sd-note-item")
			const id = noteItem.data("id")
			const index = checkbox.data("task-index")
			const isChecked = checkbox.is(":checked")

			let rawContent = noteItem.find(".note-content-hidden").html()
			let matchCount = 0

			const newContent = rawContent.replace(
				/\[(\s|x|X)\]/g,
				function (match) {
					if (matchCount === index) {
						matchCount++
						return isChecked ? "[x]" : "[ ]"
					}
					matchCount++
					return match
				},
			)

			noteItem.find(".note-content-hidden").html(newContent)

			$.post(window.sd_vars?.ajax_url || ajaxurl, {
				action: "sd_save_note",
				id: id,
				title: noteItem.find(".note-title").text(),
				content: this.decodeHtml(newContent),
				excerpt: noteItem.data("excerpt"),
				code_content: noteItem.find(".note-code-hidden").text(),
				is_code: noteItem.data("is-code") ? 1 : 0,
				context: noteItem.data("context") || "", // Need to add this
				nonce: window.sd_vars?.nonce || "",
			})
		},
	}

	$(document).ready(function () {
		NotesWidget.init()
		$(document).on("sd_workspace_rendered", function () {
			NotesWidget.init()
		})
	})
})(jQuery)
