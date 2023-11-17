import resolve from "@rollup/plugin-node-resolve";
import { babel } from "@rollup/plugin-babel";
import terser from "@rollup/plugin-terser";
import copy from "rollup-plugin-copy";
// import serve from 'rollup-plugin-serve';

export default {
    input: "src/vue-mf-root-config.js",
    output: {
        file: "../../../../public/generated/js/root-config/root-config.js",
        format: "es",
    },
    plugins: [
        resolve(),
        babel({
            babelHelpers: "bundled",
            exclude: "node_modules/**", // only transpile our source code
        }),
        terser(),
        // serve('dist')
        copy({
            targets: [
                /*{
                    src: "node_modules/es-module-shims/dist/es-module-shims.js",
                    dest: "../../../storage/public/vue-mf/root-config",
                },
                {
                    src: "node_modules/vue/dist/vue.esm-browser.prod.js",
                    dest: "../../../storage/public/vue-mf/root-config",
                },*/
            ],
        }),
    ],
};
