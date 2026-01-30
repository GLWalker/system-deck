# SystemDeck Documentation Index

**Last Updated:** January 27, 2026

## 🎯 Quick Start

- **[README.md](./README.md)** - Project overview and getting started
- **[DEVELOPER_QUICK_REFERENCE.md](./DEVELOPER_QUICK_REFERENCE.md)** - Developer quick reference
- **[UserGuide.md](./UserGuide.md)** - End user documentation
- **[API_REFERENCE.md](./API_REFERENCE.md)** - Complete API documentation ⭐ NEW
- **[TESTING_GUIDE.md](./TESTING_GUIDE.md)** - Testing procedures and checklists ⭐ NEW
- **[NOTES_WIDGET_GUIDE.md](./NOTES_WIDGET_GUIDE.md)** - Guide for Contextual Notes & Todo Lists ⭐ NEW
- **[TROUBLESHOOTING.md](./TROUBLESHOOTING.md)** - Common issues and solutions ⭐ NEW

## 📊 Current Status

- **[CRITICAL_FIXES_STATUS.md](./CRITICAL_FIXES_STATUS.md)** - ✅ All critical fixes complete
- **[MASTER_MANIFEST.md](./MASTER_MANIFEST.md)** - Primary operational guide
- **[LOGIC_EVALUATION_REPORT.md](../LOGIC_EVALUATION_REPORT.md)** - System logic audit
- **[CHANGELOG.md](../CHANGELOG.md)** - Version history and release notes ⭐ NEW
- **[CLEANUP_REPORT_JAN_27_2026.md](./CLEANUP_REPORT_JAN_27_2026.md)** - Latest cleanup session ⭐ NEW

## 🔧 Technical Reference

### Core System

- **[/core/CSSVariables.md](./core/CSSVariables.md)** - CSS variable system
- **[/core/CachingStrategy.md](./core/CachingStrategy.md)** - Caching implementation
- **[/core/HtmlAttributes.md](./core/HtmlAttributes.md)** - HTML attribute system
- **[/core/StyleArchitecture.md](./core/StyleArchitecture.md)** - CSS architecture

### WordPress Integration

- **[/hooks/actions.md](./hooks/actions.md)** - WordPress action hooks
- **[/hooks/filters.md](./hooks/filters.md)** - WordPress filter hooks
- **[/hooks/javascript-events.md](./hooks/javascript-events.md)** - Custom JavaScript events

### API Reference

- **[/reference/FileStructure.md](./reference/FileStructure.md)** - File organization
- **[/reference/Functions.md](./reference/Functions.md)** - Function reference
- **[/reference/WP_Colors_Analysis.md](./reference/WP_Colors_Analysis.md)** - Color scheme analysis

### WordPress Components

- **[WORDPRESS_COMPONENTS_CSS_REFERENCE.md](./WORDPRESS_COMPONENTS_CSS_REFERENCE.md)** - Complete WP components guide
- **[WP_COMPONENTS_QUICK_REF.md](./WP_COMPONENTS_QUICK_REF.md)** - Quick reference card

## 🏗️ Architecture

- **[/blueprints/system-architecture.md](./blueprints/system-architecture.md)** - System architecture overview

## 🔒 Security

- **[SECURITY_AUDIT.md](./SECURITY_AUDIT.md)** - Security audit and best practices

## 📦 Archive

Historical documentation and implementation notes have been moved to:

- **[/archive/](./archive/)** - Archived documentation (see archive/README.md)

## 🗂️ File Locations

| Type          | Path                                                |
| ------------- | --------------------------------------------------- |
| Documentation | `/wp-content/plugins/system-deck/documentation/`    |
| JavaScript    | `/wp-content/plugins/system-deck/assets/js/`        |
| CSS           | `/wp-content/plugins/system-deck/assets/css/`       |
| PHP Core      | `/wp-content/plugins/system-deck/includes/Core/`    |
| PHP Modules   | `/wp-content/plugins/system-deck/includes/Modules/` |
| PHP Widgets   | `/wp-content/plugins/system-deck/includes/Widgets/` |
| Templates     | `/wp-content/plugins/system-deck/templates/`        |

## 🚀 Recent Updates (January 2026)

### ✅ Completed Fixes

1. **Widget/Pin IDs** - Added proper ID attributes for persistence
2. **Unpin Button** - Implemented close button on pinned items
3. **Panel Collapse** - Fixed collapse/expand functionality
4. **Layout Persistence** - Resolved auto-save race condition
5. **Delete Flicker** - Fixed confirmation dialog flicker
6. **Console Logs** - Removed debug logging from production
7. **AJAX Endpoint** - Added missing `render_widget` handler
8. **Documentation** - Organized and archived historical docs

### 🎨 Code Quality

- Removed debug console.log statements
- Added missing AJAX endpoint for widget lazy-loading
- Organized documentation into logical structure
- Created archive for historical implementation notes

---

**Maintainer:** SystemDeck Development Team
