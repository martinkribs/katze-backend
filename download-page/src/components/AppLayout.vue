<template>
  <div :class="{'dark': themeStore.isDark}" class="min-h-screen flex flex-col">
    <div class="flex-1 flex flex-col bg-background-light dark:bg-background-dark transition-colors duration-300">
      <!-- Header -->
      <header class="bg-white dark:bg-gray-800 shadow-md">
        <div class="container mx-auto px-4 py-4">
          <div class="flex justify-between items-center">
            <div class="flex items-center space-x-4">
              <img src="../assets/katze.png" alt="Katze Logo" class="h-10 w-10">
              <h1 class="text-2xl font-bold text-text-light dark:text-text-dark">Katze App</h1>
            </div>
            <button 
              @click="themeStore.toggleTheme()"
              class="p-3 rounded-lg bg-primary hover:opacity-90 transition-opacity text-text-light dark:text-text-dark"
            >
              {{ themeStore.isDark ? '🌞' : '🌙' }}
            </button>
          </div>
        </div>
      </header>

      <!-- Main Content -->
      <div class="flex-1">
        <slot />
      </div>

      <!-- Footer -->
      <footer class="bg-white dark:bg-gray-800 shadow-md">
        <div class="container mx-auto px-4 py-6">
          <div class="flex flex-col md:flex-row justify-between items-center">
            <div class="text-gray-600 dark:text-gray-300 mb-4 md:mb-0">
              © 2024 Katze App. Alle Rechte vorbehalten.
            </div>
            <div class="flex space-x-6">
              <a href="#" class="text-gray-600 dark:text-gray-300 hover:text-primary transition-colors">
                Datenschutz
              </a>
              <a href="#" class="text-gray-600 dark:text-gray-300 hover:text-primary transition-colors">
                Impressum
              </a>
              <a href="#" class="text-gray-600 dark:text-gray-300 hover:text-primary transition-colors">
                Kontakt
              </a>
            </div>
          </div>
        </div>
      </footer>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useThemeStore } from '../stores/theme'

const themeStore = useThemeStore()
</script>
