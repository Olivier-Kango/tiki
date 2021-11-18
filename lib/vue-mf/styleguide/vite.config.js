import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
const { resolve } = require('path');

// https://vitejs.dev/config/
export default defineConfig({
    resolve: {
        extensions: ['.vue']
    },
    build: {
        emptyOutDir: true,
        minify: process.env.NODE_ENV === 'production',
        // minify: false,
        // lib: {
        //     entry: resolve(__dirname, 'src/main.js'),
        //     name: '@vue-mf/styleguide',
        //     formats: ['es']
        // },
        rollupOptions: {
            // make sure to externalize deps that shouldn't be bundled
            // into your library
            external: ['vue'],
            input: resolve(__dirname, 'src/main.js'),
            output: {
                // name: '@vue-mf/styleguide',
                dir: null,
                // dir: '../../../storage/public/vue-mf/styleguide',
                file: '../../../storage/public/vue-mf/styleguide/vue-mf-styleguide.min.js',
                manualChunks: undefined,
                format: 'system',
                assetFileNames: 'assets/[name].[ext]'
            },
            preserveEntrySignatures: true
        }
    },
    plugins: [
        vue(
            {
                // template: {
                //     transformAssetUrls: {
                //         base: resolve(__dirname, '/storage/public/vue-mf/kanban/assets'),
                //     }
                // },
            }
        )
    ]
})
