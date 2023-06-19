import singleSpaVue from "single-spa-vue";
import singleSpaCss from "single-spa-css";
import {createApp, h} from 'vue'
import App from './App.vue'

const vueLifecycles = singleSpaVue({
    createApp,
    appOptions: {
        render()
        {
            return h(App, {
                customProps: {
                    // single-spa props are available on the "this" object. Forward them to your component as needed.
                    // https://single-spa.js.org/docs/building-applications#lifecyle-props
                    name: this.name,
                    toolbarObject: this.toolbarObject,
                    settings: this.settings,
                },
            });
        },
        el: ".emoji-picker"
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
            href: "storage/public/vue-mf/emoji-picker/assets/vue-mf-emoji-picker.min.css",
        },
    ],
});
export const bootstrap = [cssLifecycles.bootstrap, vueLifecycles.bootstrap];
export const mount = [cssLifecycles.mount, vueLifecycles.mount];
export const unmount = [vueLifecycles.unmount, cssLifecycles.unmount];
export const EmojiPicker = App;
