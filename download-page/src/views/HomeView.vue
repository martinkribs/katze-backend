<template>
  <main class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
      <div class="text-center mb-12">
        <h2 class="text-3xl font-bold text-text-light dark:text-text-dark mb-4">
          Willkommen bei der Katze App!
        </h2>
        <p class="text-lg text-gray-600 dark:text-gray-300">
          Laden Sie unsere App herunter und entdecken Sie eine neue Welt der Unterhaltung.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Android Download -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-xl transition-shadow p-8">
          <h3 class="text-xl font-bold text-text-light dark:text-text-dark mb-4">Android Version</h3>
          <p class="text-gray-600 dark:text-gray-300 mb-6">Laden Sie die Android APK direkt herunter</p>
          <a 
            href="/android/katze-app.apk" 
            class="block w-full py-3 px-6 text-center bg-primary hover:opacity-90 transition-opacity text-text-light dark:text-text-dark rounded-lg font-medium"
            download
          >
            Download APK
          </a>
        </div>

        <!-- iOS Download -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-xl transition-shadow p-8">
          <h3 class="text-xl font-bold text-text-light dark:text-text-dark mb-4">iOS Version</h3>
          <p class="text-gray-600 dark:text-gray-300 mb-6">Laden Sie die App über den App Store</p>
          <a 
            href="#" 
            class="block w-full py-3 px-6 text-center bg-primary hover:opacity-90 transition-opacity text-text-light dark:text-text-dark rounded-lg font-medium"
            target="_blank"
            rel="noopener noreferrer"
          >
            Im App Store öffnen
          </a>
        </div>
      </div>
    </div>
  </main>
</template>
