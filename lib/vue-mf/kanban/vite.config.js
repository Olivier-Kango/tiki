import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vitejs.dev/config/
export default defineConfig({
  build: {
    emptyOutDir: true,
    // minify: false,
    rollupOptions: {
      input: 'src/main.js',
      output: {
        // dir: '../../../storage/public/vue/kanban/',
        dir: null,
        file: '../../../storage/public/vue/kanban/vue-mf-kanban.min.js',
        manualChunks: undefined,
        format: 'system'
      },
      preserveEntrySignatures: true
    }
  },
  plugins: [vue()]
})
