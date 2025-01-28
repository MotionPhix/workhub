import { ref, watch, onMounted, onUnmounted } from 'vue'

export function useTabPersistence(
  defaultTab: string = 'overview',
  storageKey: string = 'group_active_tab'
) {
  const activeTab = ref(defaultTab)

  // Load tab from localStorage on component mount
  onMounted(() => {
    const savedTab = localStorage.getItem(storageKey)
    if (savedTab) {
      activeTab.value = savedTab
    }
  })

  // Watch for tab changes and save to localStorage
  watch(activeTab, (newTab) => {
    localStorage.setItem(storageKey, newTab)
  })

  // Clear localStorage when navigating away from the group page
  const clearTabPersistence = () => {
    localStorage.removeItem(storageKey)
  }

  // Handle tab change
  const handleTabChange = (newTab: string) => {
    activeTab.value = newTab
  }

  // Optional: Add route-based cleanup
  onUnmounted(() => {
    clearTabPersistence()
  })

  return {
    activeTab,
    handleTabChange,
    clearTabPersistence
  }
}
