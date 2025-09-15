import { ref, computed, watch } from 'vue'

// Global dark mode state - this will be shared across all components
const isDark = ref(false)

// Initialize theme from localStorage or system preference
function initializeTheme() {
  // Check localStorage first
  const stored = localStorage.getItem('theme')
  if (stored) {
    isDark.value = stored === 'dark'
  } else {
    // Fall back to system preference
    isDark.value = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
  }

  // Apply initial theme
  applyTheme(isDark.value)

  // Watch for changes and apply them
  watch(isDark, (dark) => {
    applyTheme(dark)
    localStorage.setItem('theme', dark ? 'dark' : 'light')
    console.log('Theme changed to:', dark ? 'dark' : 'light')
  })
}

// Apply theme to HTML element
function applyTheme(dark) {
  const html = document.documentElement
  if (dark) {
    html.classList.add('dark')
  } else {
    html.classList.remove('dark')
  }
}

// Toggle function
function toggleDark() {
  isDark.value = !isDark.value
}

// Export a composable function that returns the global state
export function useTheme() {
  return {
    isDark,
    toggleDark,
    // Helper methods
    setDark: () => isDark.value = true,
    setLight: () => isDark.value = false,
    // Get current theme as string
    currentTheme: computed(() => isDark.value ? 'dark' : 'light')
  }
}

// Initialize theme immediately when this module is imported
// This ensures the theme is set before any components render
export { initializeTheme }