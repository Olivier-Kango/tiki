import singleSpaVue from "single-spa-vue";
import singleSpaCss from "single-spa-css";
import { createApp, h } from "vue";
import App from "./App.vue";

import "../custom.css";

const vueLifecycles = singleSpaVue({
    createApp,
    appOptions: {
        render() {
            return h(App, {
                // single-spa props are available on the "this" object. Forward them to your component as needed.
                // https://single-spa.js.org/docs/building-applications#lifecyle-props
                // name: this.name,
                // mountParcel: this.mountParcel,
                // singleSpa: this.singleSpa,
                trackerData: this.trackerData,
                userPrefs: this.userPrefs,
                dataSync: this.dataSync,
            });
        },
    },
    handleInstance: (app) => {
        if (import.meta.env.MODE === "development") {
            console.log(import.meta.env);
        }
        // global custom v-select2 directive trigerring change events when select2 events occur on dropdowns
        app.directive("select2", {
            mounted(el) {
                $(el).on("select2:select", () => {
                    const event = new Event("change", { bubbles: true, cancelable: true });
                    el.dispatchEvent(event);
                });
                $(el).on("select2:unselect", () => {
                    const event = new Event("change", { bubbles: true, cancelable: true });
                    el.dispatchEvent(event);
                });
            },
        });
    },
});

const cssLifecycle = singleSpaCss({
    cssUrls: [
        {
            href: "public/generated/js/tiki-offline.css",
        },
    ],
});

export const bootstrap = [cssLifecycle.bootstrap, vueLifecycles.bootstrap];
export const mount = [cssLifecycle.mount, vueLifecycles.mount];
export const unmount = [cssLifecycle.unmount, vueLifecycles.unmount];
export const TikiOffline = App;
