## ✅ QUICK ANSWER: WordPress Components CSS

### Where it's loaded:

**File:** `/includes/Core/Assets.php` line 79

```php
wp_enqueue_style('wp-components');
```

### Physical location:

```
/wp-includes/css/dist/components/style.min.css
Size: 100 KB
Lines: 3,487
```

### Components We Use:

1. **Grid** → `.components-grid` (Experimental)
2. **Card** → `.components-card`, `.components-card__body`
3. **Panel** → `.components-panel`, `.components-panel__body`
4. **Button** → `.components-button`

### Documentation Links:

- **Storybook:** https://wordpress.github.io/gutenberg/?path=/docs/components-intro--docs
- **GitHub:** https://github.com/WordPress/gutenberg/tree/trunk/packages/components
- **Card Docs:** https://wordpress.github.io/gutenberg/?path=/docs/components-card--docs
- **Panel Docs:** https://wordpress.github.io/gutenberg/?path=/docs/components-panel--docs
- **Button Docs:** https://wordpress.github.io/gutenberg/?path=/docs/components-button--docs

### To inspect locally:

```bash
# Open in code editor
code /Users/glwalker/DevKinsta/public/thedrawer/wp-includes/css/dist/components/style.css

# Or view in terminal
cat /Users/glwalker/DevKinsta/public/thedrawer/wp-includes/css/dist/components/style.css | less
```

### Browser DevTools:

1. Right-click any widget/card → Inspect
2. Look for classes starting with `.components-`
3. See which CSS file defines them (will show `style.min.css`)

---

### Full reference saved to:

`~/Desktop/WORDPRESS_COMPONENTS_CSS_REFERENCE.md` ✅
