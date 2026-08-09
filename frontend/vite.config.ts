import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import { resolve } from 'path'

export default defineConfig({
  plugins: [
    tailwindcss(),
    vue(),
  ],
  resolve: {
    alias: {
      // Replaced __dirname with import.meta.dirname to fix the Vite warning
      '@': resolve(import.meta.dirname, 'src'),
    },
  },
  server: {
    port: 5173,
    watch: {
      // Tell Vite's file watcher to completely ignore the corrupted folder
      ignored: ['**/src/locales/**']
    }
  },
})