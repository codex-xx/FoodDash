/**
 * FoodDash Theme Toggle System
 * Handles switching between Light and Dark modes with localStorage persistence
 */

class ThemeToggle {
  constructor() {
    this.storageKey = 'fooddash-theme-preference';
    this.defaultTheme = 'light';
    this.htmlElement = document.documentElement;
    this.toggleButton = null;
    this.init();
  }

  /**
   * Initialize theme system
   */
  init() {
    // Load saved theme preference
    const savedTheme = this.getSavedTheme();
    const prefersDark = this.prefersColorScheme();
    const initialTheme = savedTheme || (prefersDark ? 'dark' : 'light');
    
    // Apply initial theme
    this.setTheme(initialTheme, false);
    
    // Listen for system theme changes
    this.watchSystemTheme();
  }

  /**
   * Get saved theme from localStorage
   */
  getSavedTheme() {
    try {
      return localStorage.getItem(this.storageKey);
    } catch (e) {
      console.warn('localStorage not available:', e);
      return null;
    }
  }

  /**
   * Save theme to localStorage
   */
  saveTheme(theme) {
    try {
      localStorage.setItem(this.storageKey, theme);
    } catch (e) {
      console.warn('Could not save theme to localStorage:', e);
    }
  }

  /**
   * Check system color scheme preference
   */
  prefersColorScheme() {
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
      return true;
    }
    return false;
  }

  /**
   * Watch for system theme changes
   */
  watchSystemTheme() {
    if (window.matchMedia) {
      const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');
      darkModeQuery.addEventListener('change', (e) => {
        // Only apply system preference if user hasn't set a preference
        if (!this.getSavedTheme()) {
          const newTheme = e.matches ? 'dark' : 'light';
          this.setTheme(newTheme, false);
        }
      });
    }
  }

  /**
   * Set theme and apply to DOM
   */
  setTheme(theme, save = true) {
    if (theme !== 'light' && theme !== 'dark') {
      theme = this.defaultTheme;
    }

    // Apply theme to HTML element
    this.htmlElement.setAttribute('data-theme', theme);

    // Keep dashboard charts readable across themes
    this.applyChartTheme(theme);

    // Update toggle button if it exists
    this.updateToggleButton(theme);

    // Save preference if requested
    if (save) {
      this.saveTheme(theme);
    }

    // Dispatch custom event for any listeners
    window.dispatchEvent(new CustomEvent('themechange', { detail: { theme } }));
  }

  /**
   * Apply chart colors based on current theme
   */
  applyChartTheme(theme) {
    if (typeof window.Chart === 'undefined') {
      return;
    }

    const isDark = theme === 'dark';
    const textColor = isDark ? '#FFFFFF' : '#1A1A1A';
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.75)' : 'rgba(26, 26, 26, 0.2)';
    const borderColor = isDark ? 'rgba(255, 255, 255, 0.85)' : 'rgba(26, 26, 26, 0.35)';

    // Global defaults for newly created charts
    window.Chart.defaults.color = textColor;
    if (window.Chart.defaults.borderColor !== undefined) {
      window.Chart.defaults.borderColor = borderColor;
    }

    const updateScaleColors = (scaleOptions) => {
      if (!scaleOptions) {
        return;
      }

      scaleOptions.grid = scaleOptions.grid || {};
      scaleOptions.ticks = scaleOptions.ticks || {};
      scaleOptions.border = scaleOptions.border || {};

      scaleOptions.grid.color = gridColor;
      scaleOptions.ticks.color = textColor;
      scaleOptions.border.color = borderColor;
    };

    const applyOptionsToChart = (chart) => {
      if (!chart || !chart.options) {
        return;
      }

      chart.options.plugins = chart.options.plugins || {};

      if (chart.options.plugins.legend) {
        chart.options.plugins.legend.labels = chart.options.plugins.legend.labels || {};
        chart.options.plugins.legend.labels.color = textColor;
      }

      if (chart.options.plugins.title) {
        chart.options.plugins.title.color = textColor;
      }

      if (chart.options.plugins.tooltip) {
        chart.options.plugins.tooltip.titleColor = textColor;
        chart.options.plugins.tooltip.bodyColor = textColor;
      }

      if (chart.options.scales) {
        Object.keys(chart.options.scales).forEach((key) => {
          updateScaleColors(chart.options.scales[key]);
        });
      }

      // Improve dataset visibility for dark mode analytics charts (especially line charts).
      if (chart.data && Array.isArray(chart.data.datasets)) {
        chart.data.datasets.forEach((dataset) => {
          const datasetType = dataset.type || chart.config.type;

          if (datasetType === 'line') {
            dataset.borderColor = isDark ? '#FFFFFF' : '#111827';
            dataset.pointBorderColor = isDark ? '#FFFFFF' : '#111827';
            dataset.pointBackgroundColor = isDark ? '#0A0A0A' : '#FFFFFF';
            dataset.pointHoverBorderColor = isDark ? '#FFFFFF' : '#111827';
            dataset.pointHoverBackgroundColor = isDark ? '#111827' : '#FFFFFF';
            dataset.backgroundColor = isDark ? 'rgba(37, 99, 235, 0.18)' : 'rgba(29, 78, 216, 0.16)';
          }
        });
      }

      chart.update('none');
    };

    // Update already rendered charts immediately
    const instances = window.Chart.instances;
    if (!instances) {
      return;
    }

    if (typeof instances.forEach === 'function') {
      instances.forEach((chart) => applyOptionsToChart(chart));
      return;
    }

    Object.keys(instances).forEach((key) => applyOptionsToChart(instances[key]));
  }

  /**
   * Toggle between light and dark themes
   */
  toggle() {
    const currentTheme = this.htmlElement.getAttribute('data-theme') || 'light';
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    this.setTheme(newTheme, true);
    return newTheme;
  }

  /**
   * Update toggle button UI
   */
  updateToggleButton(theme) {
    const isDark = theme === 'dark';
    const dashboardButton = document.getElementById('theme-toggle-btn');
    const authButton = document.getElementById('auth-theme-toggle-btn');

    // Update dashboard button
    if (dashboardButton) {
      const icon = dashboardButton.querySelector('.theme-toggle-icon');
      const label = dashboardButton.querySelector('.theme-toggle-label');

      // Update icon
      if (icon) {
        icon.textContent = isDark ? '☀️' : '🌙';
        icon.setAttribute('data-theme', theme);
      }

      // Update label text - use textContent to avoid HTML issues
      if (label) {
        label.textContent = isDark ? 'Light Mode' : 'Dark Mode';
        label.setAttribute('data-theme', theme);
      }

      // Set ARIA and title attributes
      dashboardButton.setAttribute('title', isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode');
      dashboardButton.setAttribute('aria-pressed', isDark ? 'true' : 'false');
      dashboardButton.setAttribute('data-theme', theme);

      // Force a synchronous DOM reflow to ensure immediate visual update
      try {
        void dashboardButton.offsetHeight;
      } catch (e) {
        // ignore
      }
    }

    // Update auth button if present
    if (authButton) {
      authButton.textContent = isDark ? '☀️' : '🌙';
      authButton.setAttribute('title', isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode');
      authButton.setAttribute('aria-pressed', isDark ? 'true' : 'false');
      authButton.setAttribute('data-theme', theme);
    }
  }

  /**
   * Get current theme
   */
  getCurrentTheme() {
    return this.htmlElement.getAttribute('data-theme') || 'light';
  }
}

// Initialize theme system exactly once to avoid label/icon race conditions.
const initThemeToggle = () => {
  if (!window.themeToggle) {
    window.themeToggle = new ThemeToggle();
    return;
  }

  const currentTheme = window.themeToggle.getCurrentTheme();
  window.themeToggle.updateToggleButton(currentTheme);
  window.themeToggle.applyChartTheme(currentTheme);
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initThemeToggle, { once: true });
} else {
  initThemeToggle();
}
