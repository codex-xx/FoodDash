/**
 * FoodDash Global Theme Manager v2.0
 * Centralized, state-managed theme system with global synchronization
 * 
 * Features:
 * - Instant global theme updates across all pages
 * - Centralized state management
 * - localStorage persistence
 * - Real-time chart synchronization
 * - No lag or delays
 */

class GlobalThemeManager {
  constructor() {
    this.storageKey = 'fooddash-theme-preference';
    this.defaultTheme = 'light';
    this.currentTheme = 'light';
    this.htmlElement = document.documentElement;
    this.chartInstances = new Map();
    this.initialized = false;
    
    // Bind methods to preserve context
    this.setTheme = this.setTheme.bind(this);
    this.toggle = this.toggle.bind(this);
    this.updateToggleButton = this.updateToggleButton.bind(this);
    this.applyChartTheme = this.applyChartTheme.bind(this);
    this.registerChart = this.registerChart.bind(this);
    this.forceUpdate = this.forceUpdate.bind(this);
  }

  /**
   * Initialize the theme manager
   */
  init() {
    if (this.initialized) return;
    
    const savedTheme = this.getSavedTheme();
    const prefersDark = this.prefersColorScheme();
    const initialTheme = savedTheme || (prefersDark ? 'dark' : 'light');
    
    this.currentTheme = initialTheme;
    
    // Apply theme immediately
    this.applyTheme(initialTheme, false);
    
    this.watchSystemTheme();
    this.setupGlobalListeners();
    this.initialized = true;
    
    // Ensure button is updated on init
    setTimeout(() => {
      this.updateToggleButton(initialTheme);
      // Force reflow to ensure CSS variables are applied
      void this.htmlElement.offsetHeight;
    }, 0);
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
        if (!this.getSavedTheme()) {
          const newTheme = e.matches ? 'dark' : 'light';
          this.setTheme(newTheme, false);
        }
      });
    }
  }

  /**
   * Setup global listeners and mutation observers
   */
  setupGlobalListeners() {
    // Listen for dynamically created charts
    const originalGetContext = HTMLCanvasElement.prototype.getContext;
    const self = this;
    
    HTMLCanvasElement.prototype.getContext = function(...args) {
      const ctx = originalGetContext.apply(this, args);
      if (args[0] === '2d' || this.id) {
        // Store reference to canvas for theme updates
        self.canvasElements = self.canvasElements || new Set();
        self.canvasElements.add(this);
      }
      return ctx;
    };

    // Listen for Chart.js instances
    const checkCharts = () => {
      if (window.Chart && window.Chart.instances) {
        window.Chart.instances.forEach((chart, idx) => {
          const key = `chart-${idx}`;
          if (!this.chartInstances.has(key)) {
            this.chartInstances.set(key, chart);
          }
        });
      }
    };
    
    // Check periodically during dashboard load
    let checkCount = 0;
    const chartCheckInterval = setInterval(() => {
      checkCharts();
      checkCount++;
      if (checkCount > 20) clearInterval(chartCheckInterval);
    }, 100);
  }

  /**
   * Apply theme - called when theme changes
   */
  applyTheme(theme, save = true) {
    const isDark = theme === 'dark';
    const prevTheme = this.currentTheme;
    this.currentTheme = theme;

    // Update DOM
    this.htmlElement.setAttribute('data-theme', theme);
    this.htmlElement.setAttribute('data-current-theme', theme);

    // Force immediate style recalculation
    if (this.htmlElement.style) {
      this.htmlElement.style.setProperty('--current-theme', theme);
    }

    // Update all theme-dependent elements
    this.updateAllElements(theme);

    // Update charts immediately
    this.updateAllCharts(theme);

    // Update toggle button
    this.updateToggleButton(theme);

    // Save preference
    if (save) {
      this.saveTheme(theme);
    }

    // Dispatch global event for components to listen
    const event = new CustomEvent('themechange', {
      detail: {
        theme: theme,
        isDark: isDark,
        prevTheme: prevTheme,
        timestamp: Date.now()
      }
    });
    window.dispatchEvent(event);

    // Force layout recalculation
    try {
      void this.htmlElement.offsetHeight;
    } catch (e) {
      // ignore
    }
  }

  /**
   * Set a specific theme
   */
  setTheme(theme, save = true) {
    if (theme !== 'light' && theme !== 'dark') {
      theme = this.defaultTheme;
    }

    if (theme === this.currentTheme) {
      return; // Already on this theme
    }

    this.applyTheme(theme, save);
  }

  /**
   * Toggle between light and dark themes
   */
  toggle() {
    const newTheme = this.currentTheme === 'light' ? 'dark' : 'light';
    this.setTheme(newTheme, true);
    return newTheme;
  }

  /**
   * Get current theme
   */
  getCurrentTheme() {
    return this.currentTheme;
  }

  /**
   * Update all theme-dependent elements on the page
   */
  updateAllElements(theme) {
    const isDark = theme === 'dark';

    // Update all elements with data-theme-dependent attribute
    const elements = document.querySelectorAll('[data-theme-dependent]');
    elements.forEach(el => {
      if (isDark) {
        el.classList.add('dark-theme-applied');
        el.classList.remove('light-theme-applied');
      } else {
        el.classList.remove('dark-theme-applied');
        el.classList.add('light-theme-applied');
      }
    });

    // Force redraw of all components
    document.querySelectorAll('.card, .btn, .form-control, .table, .badge').forEach(el => {
      // Trigger CSS recalculation
      const display = el.style.display;
      el.style.display = 'none';
      void el.offsetHeight; // Force reflow
      el.style.display = display;
    });
  }

  /**
   * Update all Chart.js instances with new theme colors
   */
  updateAllCharts(theme) {
    const isDark = theme === 'dark';
    
    // Update Chart.js defaults
    if (typeof window.Chart !== 'undefined') {
      this.applyChartDefaults(isDark);
    }

    // Update all registered chart instances
    this.chartInstances.forEach((chart) => {
      this.updateChart(chart, isDark);
    });

    // Update Window.Chart.instances if available
    if (window.Chart && window.Chart.instances) {
      if (typeof window.Chart.instances.forEach === 'function') {
        window.Chart.instances.forEach((chart) => {
          this.updateChart(chart, isDark);
        });
      } else {
        Object.values(window.Chart.instances).forEach((chart) => {
          this.updateChart(chart, isDark);
        });
      }
    }
  }

  /**
   * Register a chart instance for theme management
   */
  registerChart(chartId, chart) {
    this.chartInstances.set(chartId, chart);
  }

  /**
   * Apply Chart.js global defaults based on theme
   */
  applyChartDefaults(isDark) {
    const textColor = isDark ? '#FFFFFF' : '#1A1A1A';
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.12)' : 'rgba(0, 0, 0, 0.08)';
    const borderColor = isDark ? 'rgba(255, 255, 255, 0.2)' : 'rgba(0, 0, 0, 0.15)';

    window.Chart.defaults.color = textColor;
    window.Chart.defaults.borderColor = borderColor;
    window.Chart.defaults.backgroundColor = isDark ? 'rgba(20, 20, 20, 0.8)' : 'rgba(255, 255, 255, 0.8)';

    if (window.Chart.defaults.plugins && window.Chart.defaults.plugins.legend) {
      window.Chart.defaults.plugins.legend.labels = window.Chart.defaults.plugins.legend.labels || {};
      window.Chart.defaults.plugins.legend.labels.color = textColor;
    }

    if (window.Chart.defaults.plugins && window.Chart.defaults.plugins.tooltip) {
      window.Chart.defaults.plugins.tooltip.titleColor = textColor;
      window.Chart.defaults.plugins.tooltip.bodyColor = textColor;
      window.Chart.defaults.plugins.tooltip.backgroundColor = isDark ? 'rgba(0, 0, 0, 0.8)' : 'rgba(255, 255, 255, 0.9)';
    }
  }

  /**
   * Update a single chart instance
   */
  updateChart(chart, isDark) {
    if (!chart || !chart.options) return;

    const textColor = isDark ? '#FFFFFF' : '#1A1A1A';
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.12)' : 'rgba(0, 0, 0, 0.08)';
    const borderColor = isDark ? 'rgba(255, 255, 255, 0.2)' : 'rgba(0, 0, 0, 0.15)';

    // Update legend
    if (chart.options.plugins && chart.options.plugins.legend) {
      chart.options.plugins.legend.labels = chart.options.plugins.legend.labels || {};
      chart.options.plugins.legend.labels.color = textColor;
    }

    // Update tooltip
    if (chart.options.plugins && chart.options.plugins.tooltip) {
      chart.options.plugins.tooltip.titleColor = textColor;
      chart.options.plugins.tooltip.bodyColor = textColor;
      chart.options.plugins.tooltip.backgroundColor = isDark ? 'rgba(0, 0, 0, 0.8)' : 'rgba(255, 255, 255, 0.9)';
    }

    // Update title
    if (chart.options.plugins && chart.options.plugins.title) {
      chart.options.plugins.title.color = textColor;
    }

    // Update scales/axes
    if (chart.options.scales) {
      Object.keys(chart.options.scales).forEach((scaleKey) => {
        const scale = chart.options.scales[scaleKey];
        if (!scale) return;

        if (scale.ticks) {
          scale.ticks.color = textColor;
        }
        if (scale.grid) {
          scale.grid.color = gridColor;
        }
        if (scale.border) {
          scale.border.color = borderColor;
        }
      });
    }

    // Update datasets
    if (chart.data && chart.data.datasets) {
      chart.data.datasets.forEach((dataset) => {
        const chartType = dataset.type || chart.config.type;

        if (chartType === 'line') {
          dataset.borderColor = isDark ? '#FFFFFF' : '#111827';
          dataset.pointBorderColor = isDark ? '#FFFFFF' : '#111827';
          dataset.pointBackgroundColor = isDark ? '#141414' : '#FFFFFF';
          dataset.pointHoverBorderColor = isDark ? '#F2C200' : '#111827';
          dataset.pointHoverBackgroundColor = isDark ? '#111827' : '#FFFFFF';
          dataset.backgroundColor = isDark ? 'rgba(242, 194, 0, 0.1)' : 'rgba(29, 78, 216, 0.08)';
        } else if (chartType === 'doughnut' || chartType === 'pie') {
          dataset.borderColor = isDark ? '#141414' : '#FFFFFF';
        } else if (chartType === 'bar') {
          dataset.backgroundColor = isDark ? 'rgba(242, 194, 0, 0.8)' : 'rgba(13, 110, 253, 0.8)';
          dataset.borderColor = isDark ? '#F2C200' : '#0D6EFD';
        }
      });
    }

    // Force update without animation
    try {
      chart.update('none');
    } catch (e) {
      console.warn('Chart update error:', e);
    }
  }

  /**
   * Force a complete update (for DOM mutations)
   */
  forceUpdate() {
    this.updateAllElements(this.currentTheme);
    this.updateAllCharts(this.currentTheme === 'dark');
  }

  /**
   * Update toggle button UI
   */
  updateToggleButton(theme) {
    const isDark = theme === 'dark';
    const dashboardButton = document.getElementById('theme-toggle-btn');
    const authButton = document.getElementById('auth-theme-toggle-btn');

    if (dashboardButton) {
      const icon = dashboardButton.querySelector('.theme-toggle-icon');
      const label = dashboardButton.querySelector('.theme-toggle-label');

      if (icon) {
        icon.textContent = isDark ? '☀️' : '🌙';
        icon.setAttribute('aria-label', isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode');
      }

      if (label) {
        label.textContent = isDark ? 'Light Mode' : 'Dark Mode';
        label.setAttribute('aria-label', isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode');
      }

      dashboardButton.setAttribute('title', isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode');
      dashboardButton.setAttribute('aria-pressed', isDark ? 'true' : 'false');
      dashboardButton.setAttribute('data-theme', theme);

      // Force reflow
      try {
        void dashboardButton.offsetHeight;
      } catch (e) {
        // ignore
      }
    }

    if (authButton) {
      authButton.textContent = isDark ? '☀️' : '🌙';
      authButton.setAttribute('title', isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode');
      authButton.setAttribute('aria-pressed', isDark ? 'true' : 'false');
      authButton.setAttribute('data-theme', theme);
    }
  }
}

// Create global instance
window.globalThemeManager = new GlobalThemeManager();

// Initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    window.globalThemeManager.init();
  });
} else {
  window.globalThemeManager.init();
}

// Keep backward compatibility with old ThemeToggle class
window.themeToggle = window.globalThemeManager;
