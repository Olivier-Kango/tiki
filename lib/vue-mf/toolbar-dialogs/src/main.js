import singleSpaVue from "single-spa-vue";
import singleSpaCss from "single-spa-css";
import { createApp, h } from "vue";
import App from "./App.vue";

//createApp(App).mount('#app')

const vueLifecycles = singleSpaVue({
    createApp,
    appOptions: {
        render() {
            return h(App, {
                customProps: {
                    // single-spa props are available on the "this" object. Forward them to your component as needed.
                    // https://single-spa.js.org/docs/building-applications#lifecyle-props
                    name: this.name,
                    mountParcel: this.mountParcel,
                    singleSpa: this.singleSpa,
                    syntax: this.syntax,
                    toolbarObject: this.toolbarObject,
                },
            });
        },
    },
    handleInstance: (app) => {
        if (import.meta.env.MODE === "development") {
            console.log(import.meta.env);
        }
    },
});
const cssLifecycles = singleSpaCss({
    cssUrls: [
        {
            href: "storage/public/vue-mf/toolbar-dialogs/assets/vue-mf-toolbar-dialogs.min.css",
        },
    ],
});
export const bootstrap = [cssLifecycles.bootstrap, vueLifecycles.bootstrap];
export const mount = [cssLifecycles.mount, vueLifecycles.mount];
export const unmount = [vueLifecycles.unmount, cssLifecycles.mount];
export const ToolbarDialogs = App;
