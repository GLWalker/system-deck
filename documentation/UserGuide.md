# SystemDeck User Guide: Docking & Navigation

SystemDeck provides a flexible "Docking" system that allows you to position the interface exactly where you need it. This guide explains the available modes, how to customize your workspace, and how the system remembers your preferences.

## 1. Dock Modes & Layouts

You can switch between different layouts using the toolbar icons or by dragging resize handles.

- **Standard Dock**: The default view. Anchored to the bottom of the screen, taking up about 60% of the height. Ideal for general use.
- **Full Screen**: Expands to cover the entire viewport, giving you maximum space for complex tasks.
- **Side Docks (Left / Right)**: Anchors the deck to the left or right side of the screen as a sidebar. Perfect for widescreen monitors.
- **Base Dock**: Collapses the deck into a thin bar at the bottom or side, showing only the toolbar.
- **Min-Dock (Floating Icon)**: Minimizes the entire System deck into a small circular icon. Click the icon to restore your previous view.

## 2. Navigation Toolbar

The header bar contains controls to manage your view:

- **Minimize (Start Icon)**: Switches to **Min-Dock** mode.
- **Left Arrow**: Docks to the Left (cycles between Left and Left-Base).
- **Minus**: Docks to the Base (Bottom Bar).
- **Right Arrow**: Docks to the Right (cycles between Right and Right-Base).
- **Randomize/Shuffle Icon**: Instantly resets the view to the **Standard Dock**.
- **Square (Fullscreen)**: Toggles Full Screen mode.
- **X (Close)**: Soft-closes the deck (hides it).

## 3. Resizing & Customization

You can manually resize the System Deck in most modes:

- **Height**: Drag the top edge in Standard Dock.
- **Width**: Drag the side edge in Left or Right Dock.

**Memory**: SystemDeck remembers the dimensions you set for _each_ specific view. If you make the Right Dock wide and the Left Dock narrow, it will remember those sizes individually.

## 4. Closing & Persistence Behaviors

SystemDeck is smart about how you close it.

### A. The Admin Bar Link (Toggle)

Clicking "System Deck" in the WordPress Admin Bar (top of screen) acts as the master toggle.

- **Clean Reset**: If you close the deck while it is fully open, it performs a **Reset**. Next time you open it, it will return to your default settings (ignoring temporary resizing). This ensures you always have a fresh start.
- **Persistence Exception (Min-Dock)**: If you are in **Min-Dock** mode, the system assumes you want to stay there. Closing via the Admin Bar _preserves_ this state. Next time you click the link, it re-appears as the Min-Dock icon.

### B. The Close Button (X)

Clicking the `X` inside the System Deck performs a **Soft Close**.

- It hides the deck but **remembers exactly how you left it**.
- **Re-opening**: When you click the Admin Bar link to open it again, it **Restores** your previous view, including any custom resizing functionality.
- **Note**: Using the Soft Close also prevents the deck from auto-opening on new pages.

## 5. Advanced Features

### Incognito Mode

For an ultra-minimalist experience, you can enable **Incognito Mode**.

- When enabled, the **Min-Dock** button becomes invisible (0% opacity) when you are not using it.
- It reappears only when you hover your mouse over its position (bottom left corner).

### User Profile Settings

You can customize the default behavior in your **User Profile**:

1.  Go to **Users > Profile** in WordPress.
2.  Scroll down to **SystemDeck Settings**.
3.  **Default Dock State**: Choose your preferred starting layout (e.g., always start in Right Dock).
4.  **Incognito Mode**: Toggle the invisible button feature on/off.

## 6. The Notes Widget

SystemDeck includes a powerful **Context-Aware Notes Widget**.

- **Stick Notes to Pages**: Use "Capture URL" and the **Filter** button to see notes relevant to your current screen.
- **Todo Lists**: Create interactive check-lists using `[ ]` syntax.
- **Code Snippets**: Save code fragments with syntax highlighting.

👉 **[Read the Full Notes Guide](./NOTES_WIDGET_GUIDE.md)**
