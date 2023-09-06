import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";
import { resolve } from "path";
import { visualizer } from "rollup-plugin-visualizer";
import { viteStaticCopy } from "vite-plugin-static-copy";
/*

Overarching principles:  

-  Don't break npm run watch from root.  It doesn't mean that HMR style no-reload works everywhere, but no code should ever have to be rebuild manually when changing a source file in an IDE.  An most reloading the page is sufficient.

-  Don't tie ourselves to tooling or a stack too deeply.  A lot of this is in flux.  npm workspaces are new.  Vite is maturing very quickly, but watching multiple codebases has no internal support.  So while we strive to have all dependencies synchronised, it must remain possible to run completely independent js codebases with single-spa (https://single-spa.js.org/docs/getting-started-overview) to orchestrate individual independent frontends. 

- It's not workable to have entirely independent modules all with their own vite.config.  Aside from the fact some developers would run out of RAM locally, I did a prototype and while possible, it's EXTREMELY painfull to share common configs, and real global HMR would never be a reality - benoitg - 2023-09-19

TODO Explain how to instanciate apps (see wikiplugin_kanban.tpl and importmap)

Stack choices:

CHOSEN:

- single-spa, DONE
- single-spa-vue, DONE
- single-spa-css  DONE, temporary.  We may have to write our own abstraction, it was designed for webpack.  It's a stopgap after 

NOT CHOSEN
- vite-plugin-single-spa (https://github.com/WJSoftware/vite-plugin-single-spa) Pretty new (4 months), excellent overall,and we are kind of already commited to single-spa.  It allows transparently managing CSS for js, in effect replacing single-spa-css.  Unfortunately it doesn't handle multiple entry points properly, and after EXTENSIVE testing, it seems unlikely we can ever use it as is.  benoitg - 2023-09-19
- https://github.com/single-spa/self-hosted-shared-dependencies , we have that exact need, but it's better to do it manually, since we also use raw file from php.

CONSIDERED 

Module federation and https://github.com/originjs/vite-plugin-federation , it's essentially an alternative to importmaps, maybe it doesn't buy us much.  Claims to work with vite build --watch, but not with vite dev, so not great for the development experience considering the effort we put in having a single vite.config.mjs for most things.  But depending on where single-spa goes in the future, we may have to condider it again.  Especially since it can be used as a performance optimization:  https://single-spa.js.org/docs/recommended-setup/#module-federation

POSTPONED: 

- Use https://vitejs.dev/config/shared-options.html#resolve-dedupe once it's fixed for ESM


Maybe obsolete: https://dev.to/hontas/using-vite-with-linked-dependencies-37n7  Use the Vite config option optimizeDeps.exclude when working with linked local dependencies 


DONE:

-  Decide turborepo https://github.com/gajus/turbowatch#readme vs concurrently https://github.com/open-cli-tools/concurrently:  DONE:  Concurrently for now

IN PROGRESS:

- Migrate to workspaces IN PROGRESS

TODO: 

- Migrate moment from composer first, as it's actually used by the microfrontend, AND was already used in tiki.

- Migrate twbs from composer second, it's references by CSS (themes) and JS (BootstrapModal.vue) AND PHP (multiple places), so it's a good complete test.

-  Manage versions https://www.npmjs.com/package/check-dependency-version-consistency, this is not optional, having varying versions increase bundle sizes AND can cause serious problems.

- Get index.php and htaccess generated

- Integrate with setup.sh

- GET HMR and vite dev working, especially for scss files.  
 * There are Here is a drupal example https://www.drupal.org/project/vite.  Might be simpler to just rewrite base for vite dev server:  https://single-spa.js.org/docs/ecosystem-vite/, but that doesn't touch html and the like. 
 * Other solution:  proxy:  https://vitejs.dev/config/server-options.html#server-proxy  
 * Other solution:  see how vite-plugin-single-spa did it https://github.com/WJSoftware/vite-plugin-single-spa/commit/ed31833a7a9b7368c3227e6becbd02ac9585aab2

- Vite assumes everything is build by a single vite pass. a single manifest.json is build.  We could read the latter with https://packagist.org/packages/gin0115/vite-manifest-parser and try to generate an importmap


- Use generated manifest from PHP https://vitejs.dev/guide/backend-integration.html

- Use import maps to be able to use vite dev

- Figure out hoe to manage CSS with multiple entry points in a single vite project.

vite-plugin-single-spa is not compiling it's ex extension properly if more than one module use it.  It may get better in the future, but it also causes a lot of problems because of all it sets in vite config.

- Manual: 
 https://webjose.hashnode.dev/injecting-micro-frontend-css-in-single-spa is not working or use https://github.com/single-spa/single-spa-css and eventually wrap it?
*/

export default defineConfig(({ command, mode }) => ({
    base: "/public/generated/js", //This must NOT have a trailing slash
    publicDir: false, //tiki already uses public for other purposes.  If we want to use this feature we can create a src/public folder for it.
    resolve: {
        /*alias: {
            "@vue-mf/styleguide": resolve(__dirname, "vue-mf/styleguide/src/main.js"),
        },*/
    },
    build: {
        outDir: resolve(__dirname, "../../public/generated/js"),
        emptyOutDir: true,
        minify: mode === "production",
        sourcemap: mode === "production",
        cssCodeSplit: true,
        // emit manifest so PHP can find the hashed files
        manifest: true,
        target: "es2022", //https://caniuse.com/?search=es2022 Who cares about IE these days...
        optimizeDeps: {
            disabled: false,
            //If you ever need to debug a dependency and see your changes do this (ref: https://dev.to/hontas/using-vite-with-linked-dependencies-37n7):
            //exclude: ["svelte"],
        },
        rollupOptions: {
            external: ["vue", /^@vue-mf\/.+/],
            //external: [/^@vue-mf\/.+/],
            //external: ["vue"],
            input: {
                //Watch out, __dirname is the path of the config file, no matter how vite is called...
                styleguide: resolve(__dirname, "vue-mf/styleguide/src/styleguide.js"),
                //STILL BREAKS IF WE UNCOMMENT, but different error.  Kanban becomes smaller, so some code is factored out.
                "duration-picker": resolve(__dirname, "vue-mf/duration-picker/src/duration-picker.js"),
                "emoji-picker": resolve(__dirname, "vue-mf/emoji-picker/src/emoji-picker.js"),
                kanban: resolve(__dirname, "vue-mf/kanban/src/kanban.js"),
                "toolbar-dialogs": resolve(__dirname, "vue-mf/toolbar-dialogs/src/toolbar-dialogs.js"),
            },
            output: {
                //dir: "./public/generated/js",
                //file: "../../../storage/public/vue-mf/kanban/vue-mf-kanban.min.js",
                manualChunks: undefined,
                format: "es",
                //And this is super hard to integrate since this bug introduced in vite 4 https://github.com/vitejs/vite/issues/12072

                //It means we can't use hashing, and we need to name the entry point nameofmodule.js so we can have a nameofmodule.css file
                //Can't use the hash untill we have deeper integration of manifest in php anyway
                //assetFileNames: "[name]-assets/[name][extname]",
                assetFileNames: (assetInfo) => {
                    //console.log(assetInfo);
                    return assetInfo.name;
                },
                entryFileNames: "[name].js",
            },
            //preserveModules: true,
            //preserveModulesRoot: 'src/js/',
            preserveEntrySignatures: true,
        },
    },
    plugins: [
        vue({
            template: {
                transformAssetUrls: {
                    base: "/public/generated/js/",
                },
            },
        }),

        //These are re-bundled files that need to be read at runtime
        //Needs to be moved in it's own module
        viteStaticCopy({
            targets: [
                {
                    src: "node_modules/vue/dist/vue.esm-browser.prod.js",
                    dest: "vendor_bundled_js",
                },
            ],
        }),
        /* Uncomment this in development to see which dependencies contribute to bundle size */
        //visualizer({ open: true, gzipSize: true, brotliSize: false }),
    ],
}));
