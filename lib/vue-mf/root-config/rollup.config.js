import resolve from '@rollup/plugin-node-resolve';
import { babel } from '@rollup/plugin-babel';
import { terser } from 'rollup-plugin-terser';
// import serve from 'rollup-plugin-serve';

export default {
    input: 'src/vue-mf-root-config.js',
    output: {
        file: '../../../storage/public/vue/vue-mf-root-config.min.js',
        format: 'system'
    },
    plugins: [
        resolve(),
        babel({
            babelHelpers: 'bundled',
            exclude: 'node_modules/**' // only transpile our source code
        }),
        terser(),
        // serve('dist')
    ]
};