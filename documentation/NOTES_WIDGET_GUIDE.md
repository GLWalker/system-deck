# SystemDeck: The Notes Widget Guide

The **Notes Widget** is a powerful productivity tool built directly into SystemDeck. It goes beyond simple text notes, offering context-aware sticking, interactive todo lists, and a dedicated code editor with syntax highlighting.

---

## 🚀 Key Features

- **Quick Notes**: Instantly jot down thoughts without leaving the page.
- **Sticky Context**: Pin notes to specific admin screens (e.g., "Plugins" or "Settings").
- **Code Editor**: A high-contrast, syntax-highlighted editor for saving code snippets.
- **Interactive Todo Lists**: Use Markdown-style checkboxes `[ ]` that you can click to toggle.
- **Auto-Capture**: Automatically save the current page URL with your note.
- **Mixed Mode**: Create notes that have both a description AND a code snippet.

---

## 📝 Creating a Note

1.  Open the **Quick Notes** widget in your SystemDeck dashboard.
2.  **Title**: Enter a summary for your note (optional, but recommended).
3.  **Description**: Type your main content.
    - _Tip:_ You can use standard text or create lists.
4.  **Save**: Click **"Save Note"** to store it. It appears instantly in the "Recent Notes" list below.

---

## 📍 Context & Sticky Notes

The Notes Widget handles "Context" smartly using the **Capture URL** feature.

### How to Stick a Note to a Page

1.  When creating a note, check the **Capture URL** box.
2.  This saves the current page's URL (e.g., `.../wp-admin/plugins.php`) with the note.

### Filtering by Context

1.  Look at the "Recent Notes" header.
2.  Click the **"This Page"** filter button (Filter Icon).
3.  The list will update to show **ONLY** notes that were captured on the current page.
4.  Click it again to toggle back to "All Recent Notes."

_Use Case:_ Create a "Deployment Checklist" on your Plugins page and a "Theme Tweaks" note on your Themes page. Use the filter to see only what's relevant to where you are.

---

## ✅ Interactive Todo Lists

Turn any note into a functional Todo List.

1.  In the note content, use standard Markdown checkbox syntax:
    ```markdown
    To Do:
    [ ] Task 1
    [x] Task 2 (Completed)
    [ ] Task 3
    ```
2.  Save the note.
3.  **Click to Check**: In the **View All (Drawer)** or the Recent List, the text `[ ]` converts into a **real interactive checkbox**.
4.  Clicking the box instantly updates the note in the database. No need to open "Edit" mode!

---

## 💻 Code Snippets

Need to save a CSS snippet or a PHP function?

1.  Check the **"Code"** box in the toolbar.
    - This reveals the **Source Code Editor**.
    - _Note:_ The editor uses a high-contrast dark theme (VS Code style) for readability.
2.  Type your description in the top box (e.g., "Fix for header padding").
3.  Type your code in the dark editor key.
4.  Save.
    - When viewing the note later, the code is preserved in a dedicated block.

---

## 📂 View All (The Drawer)

The widget shows your 5 most recent notes. To manage everything:

1.  Click **"View All →"** in the widget header.
2.  A large Drawer slides up from the bottom.
3.  Here you can see full content, check off tasks, and scroll through your entire history.
