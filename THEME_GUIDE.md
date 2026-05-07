# FoodDash Dark Mode & Light Mode Implementation Guide

## Overview

A modern Dark Mode and Light Mode theme system has been integrated into FoodDash. The feature provides:
- Smooth theme switching with localStorage persistence
- System preference detection
- Comprehensive styling for all UI components
- Seamless transitions without layout changes
- Full accessibility support

---

## Features Implemented

### ✅ **Dark Mode**
- Professional dark color scheme (#1A1814, #2D2824, #3D3632)
- High-contrast text for readability
- Subtle shadows and soft glow accents
- Smooth animations and transitions

### ✅ **Light Mode**
- Preserved original light theme appearance
- Clean and readable typography
- Consistent with original branding
- Default theme on first visit

### ✅ **Theme Toggle Button**
- Located in navbar (next to user email)
- Moon emoji (🌙) for Light Mode
- Sun emoji (☀️) for Dark Mode
- Mobile-friendly responsive design
- Visual feedback on hover and click

### ✅ **localStorage Persistence**
- User theme preference saved to browser storage
- Persists across sessions and browser refreshes
- Preference survives logout/login

### ✅ **System Preference Detection**
- Detects OS-level dark mode preference (prefers-color-scheme)
- Automatically applies system theme if user hasn't set preference
- Responds to system theme changes in real-time

### ✅ **Smooth Transitions**
- 0.3s ease transitions for all theme changes
- No jarring color flashes
- Smooth animations for UI elements
- Respects prefers-reduced-motion for accessibility

---

## File Structure

### New Files Created:

1. **`public/css/themes.css`** (550+ lines)
   - CSS variables for light and dark themes
   - Component-specific styling
   - Responsive design
   - Accessibility features

2. **`public/js/theme-toggle.js`** (150+ lines)
   - ThemeToggle class for theme management
   - localStorage handling
   - System preference detection
   - Custom events for theme changes

3. **`app/Views/layouts/auth.php`** (200+ lines)
   - Theme-aware layout for authentication pages
   - Supports dark/light modes on login page
   - Consistent with dashboard styling

### Modified Files:

1. **`app/Views/layouts/dashboard.php`**
   - Added themes.css link
   - Added theme-toggle.js script
   - Added theme toggle button in navbar
   - Added click handler for theme toggle
   - Enhanced CSS for button styling

---

## Usage

### For Users

#### Toggle Theme
1. Click the moon/sun icon (🌙/☀️) in the top-right navbar
2. Theme changes instantly across all pages
3. Preference is automatically saved

#### System Preference
- If no preference is set, FoodDash uses your OS theme
- Change your OS theme to auto-switch FoodDash (if no manual preference)
- Set a manual preference to override OS default

### For Developers

#### Access Current Theme
```javascript
// Get current theme
const currentTheme = window.themeToggle.getCurrentTheme();
console.log(currentTheme); // 'light' or 'dark'
```

#### Toggle Theme Programmatically
```javascript
// Toggle to opposite theme
window.themeToggle.toggle();

// Set specific theme
window.themeToggle.setTheme('dark', true);
```

#### Listen for Theme Changes
```javascript
// Custom event when theme changes
window.addEventListener('themechange', (event) => {
  console.log('New theme:', event.detail.theme);
});
```

#### Using Theme CSS Variables
```css
/* In your custom CSS, use the theme variables */
.custom-element {
  background-color: var(--theme-bg-primary);
  color: var(--theme-text-primary);
  border: 1px solid var(--theme-border-color);
  box-shadow: var(--theme-shadow-md);
}
```

---

## Color Palette

### Light Mode
| Element | Color | Hex |
|---------|-------|-----|
| Background (Primary) | Light Tan | #F6F4EF |
| Background (Secondary) | White | #FFFFFF |
| Text (Primary) | Dark Brown | #241C0C |
| Text (Secondary) | Muted Brown | #5A5450 |
| Border | Light Gray | #E5E0D8 |
| Cards | White | #FFFFFF |
| Shadow (Small) | rgba(0,0,0,0.05) | - |
| Shadow (Medium) | rgba(0,0,0,0.08) | - |

### Dark Mode
| Element | Color | Hex |
|---------|-------|-----|
| Background (Primary) | Very Dark Brown | #1A1814 |
| Background (Secondary) | Dark Brown | #2D2824 |
| Text (Primary) | Light Cream | #F5F1E8 |
| Text (Secondary) | Light Gray | #D4CCBF |
| Border | Dark Gray | #4A4540 |
| Cards | Dark Brown | #2D2824 |
| Shadow (Small) | rgba(0,0,0,0.3) | - |
| Shadow (Medium) | rgba(0,0,0,0.4) | - |

---

## CSS Variables Available

All theme variables are prefixed with `--theme-` and include:

### Background Colors
- `--theme-bg-primary`
- `--theme-bg-secondary`
- `--theme-bg-tertiary`

### Text Colors
- `--theme-text-primary`
- `--theme-text-secondary`
- `--theme-text-tertiary`

### Component Backgrounds
- `--theme-card-bg`
- `--theme-input-bg`
- `--theme-table-header-bg`
- `--theme-sidebar-bg`

### Borders and Shadows
- `--theme-border-color`
- `--theme-border-light`
- `--theme-shadow-sm`
- `--theme-shadow-md`
- `--theme-shadow-lg`

### State Backgrounds
- `--theme-success-bg`
- `--theme-warning-bg`
- `--theme-danger-bg`
- `--theme-info-bg`

---

## Supported Pages & Components

The theme system has been comprehensively applied to:

### Pages
- ✅ Admin Dashboard
- ✅ Restaurant Dashboard
- ✅ User Management
- ✅ Restaurant Approvals
- ✅ Driver Approvals
- ✅ Order History
- ✅ Menu Management
- ✅ Settings Page
- ✅ All admin panels

### Components
- ✅ Navbar
- ✅ Sidebar
- ✅ Cards & Summary Cards
- ✅ Forms & Input Fields
- ✅ Tables & Data Tables
- ✅ Buttons (all variants)
- ✅ Modals & Dialogs
- ✅ Alerts & Messages
- ✅ Badges
- ✅ Dropdowns
- ✅ Tabs & Navigation
- ✅ Pagination

---

## Browser Support

The theme system works on:
- ✅ Chrome/Edge (latest 2 versions)
- ✅ Firefox (latest 2 versions)
- ✅ Safari (latest 2 versions)
- ✅ Mobile browsers (iOS Safari, Chrome Mobile)

### Requirements
- localStorage support (available on all modern browsers)
- CSS Variables support (CSS Custom Properties)
- JavaScript enabled

---

## Accessibility

The implementation includes:
- ✅ ARIA labels for theme button (`aria-pressed`, `aria-label`)
- ✅ Keyboard navigation support (Tab, Enter to toggle)
- ✅ Respects `prefers-reduced-motion` for accessibility
- ✅ High contrast text in both modes
- ✅ Screen reader support
- ✅ Focus indicators visible in both themes

---

## Performance Considerations

- **CSS-based**: Uses CSS variables for instant theme switching
- **Minimal JS**: Only ~150 lines of JavaScript
- **No reloads**: Theme changes instantly without page refresh
- **localStorage**: Asynchronous, non-blocking storage
- **Transitions**: Smooth but performant (0.3s ease)
- **Small footprint**: themes.css is ~8KB, theme-toggle.js is ~3KB

---

## Troubleshooting

### Theme not persisting
- Check if localStorage is enabled in browser
- Clear browser cache and try again
- Check browser console for errors

### Dark mode not applying
- Hard refresh page (Cmd+Shift+R or Ctrl+Shift+R)
- Ensure themes.css is loaded (check Network tab)
- Check that HTML element has `data-theme` attribute

### Button not showing
- Ensure you're logged in (navbar only visible on dashboard)
- Check if JavaScript is enabled
- Verify theme-toggle.js is loaded

### Colors look wrong
- Check system color preference settings
- Verify CSS is not overridden by custom styles
- Look for CSS specificity issues in browser DevTools

---

## Future Enhancements

Potential improvements for future versions:
- Additional theme options (e.g., High Contrast, Automatic)
- Custom color picker for themes
- Theme scheduler (auto-switch at specific times)
- Per-component theme overrides
- Theme presets/skins

---

## Development Notes

### Adding Theme Support to New Components

1. Use CSS variables in your styles:
```css
.new-component {
  background-color: var(--theme-bg-secondary);
  color: var(--theme-text-primary);
  border: 1px solid var(--theme-border-color);
  box-shadow: var(--theme-shadow-sm);
}
```

2. The theme will automatically apply through the `data-theme` attribute on the html element

### Testing the Theme System

1. Open browser DevTools (F12)
2. Go to Console
3. Test commands:
```javascript
// Toggle theme
window.themeToggle.toggle();

// Get current theme
window.themeToggle.getCurrentTheme();

// Set specific theme
window.themeToggle.setTheme('dark', true);

// Check localStorage
localStorage.getItem('fooddash-theme-preference');
```

---

## Support & Maintenance

### Monitoring
- Check browser console for errors
- Monitor localStorage usage
- Test across different browsers/devices

### Updates
- Update CSS variables in `public/css/themes.css`
- Modify theme logic in `public/js/theme-toggle.js`
- Test on all supported pages after changes

---

## Technical Details

### How It Works

1. **Initialization**: theme-toggle.js runs on page load
2. **Detection**: Checks localStorage for saved preference
3. **Fallback**: Uses system preference if no saved preference
4. **Application**: Sets `data-theme` attribute on html element
5. **Styling**: CSS applies theme based on `data-theme` value
6. **Persistence**: User changes are saved to localStorage
7. **Listening**: Watches for system theme changes

### Data Flow
```
Page Load
    ↓
Check localStorage
    ↓
If saved → Use saved theme
If not → Check system preference
    ↓
Set data-theme attribute
    ↓
CSS applies variables
    ↓
Elements update instantly
    ↓
Listen for user/system changes
```

---

## Credits

Theme colors inspired by FoodDash brand colors:
- Mustard Yellow: #F2C200
- Teal: #0B2423
- Espresso Brown: #241C0C
- Light Background: #F6F4EF

---

**Last Updated**: May 7, 2026  
**Version**: 1.0 - Initial Release
