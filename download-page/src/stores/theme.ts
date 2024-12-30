import { defineStore } from 'pinia'

export const useThemeStore = defineStore('theme', {
  state: () => ({
    isDark: window.matchMedia('(prefers-color-scheme: dark)').matches
  }),
  actions: {
    toggleTheme() {
      this.isDark = !this.isDark
      if (this.isDark) {
        document.documentElement.classList.add('dark')
      } else {
        document.documentElement.classList.remove('dark')
      }
    },
    initTheme() {
      if (this.isDark) {
        document.documentElement.classList.add('dark')
      }
    }
  }
})
