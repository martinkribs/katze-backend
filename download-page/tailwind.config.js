/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        primary: '#E7D49E',
        background: {
          light: '#F3F4F6',
          dark: '#111827'
        },
        text: {
          light: '#000000',
          dark: '#FFFFFF'
        }
      }
    },
  },
  plugins: [],
}
