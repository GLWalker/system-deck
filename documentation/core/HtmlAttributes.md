# HtmlAttributes Reference

**Namespace:** `SystemDeck\Core`
**Class:** `HtmlAttributes`

The `HtmlAttributes` class is a singleton service responsible for centralizing and filtering all HTML attributes (classes, IDs, data attributes, ARIA roles) for key SystemDeck elements.

## Purpose

This prevents "Attribute Soup" in templates and allows developers to safely extend the UI without modifying core files.

## Usage

### In Templates

Use the global helper function `\SystemDeck\Core\sd_attr()` to output the full attribute string for a given context.

```php
<div <?php \SystemDeck\Core\sd_attr('system-deck'); ?>>
    <!-- Content -->
</div>
```

### Contexts

Currently supported contexts:

| Context       | Element        | Description                                                                  |
| :------------ | :------------- | :--------------------------------------------------------------------------- |
| `system-deck` | Main Container | The root wrapper (`#systemdeck`). Handles Classes, ID, ARIA, and Data attrs. |
| `header`      | Header Bar     | The top control bar (`#sd-header-bar`).                                      |
| `workspace`   | Content Area   | The inner content wrapper (`#sd-workspace-content`).                         |

## Extensibility (Hooks)

### `sd_parse_attr`

_Filter_

Modify the attributes array before it is rendered.

**Parameters:**

-   `$attributes` _(array)_: Associative array of attributes (`'class' => 'foo'`, `'data-bar' => 'baz'`).
-   `$context` _(string)_: The context string (e.g., `'system-deck'`).

**Example:**

```php
add_filter('sd_parse_attr', function($attrs, $context) {
    if ($context === 'system-deck') {
        // Add a custom data attribute
        $attrs['data-project'] = 'secret-sauce';

        // Add a custom class
        $attrs['class'] .= ' my-custom-skin';
    }
    return $attrs;
}, 10, 2);
```
