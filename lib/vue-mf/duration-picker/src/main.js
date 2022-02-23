import singleSpaVue from "single-spa-vue";
import { createApp, h } from 'vue';
import App from './App.vue';

import '../custom.scss';

const vueLifecycles = singleSpaVue({
    createApp,
    appOptions: {
        render() {
            return h(App,
                {
                    // single-spa props are available on the "this" object. Forward them to your component as needed.
                    // https://single-spa.js.org/docs/building-applications#lifecyle-props
                    // name: this.name,
                    // mountParcel: this.mountParcel,
                    // singleSpa: this.singleSpa,
                    durationData: this.durationData
                }
            );
        }
    },
    handleInstance: (app) => {
        if (import.meta.env.MODE === 'development') {
            console.log(import.meta.env);
        }
    }
});

export const bootstrap = vueLifecycles.bootstrap;
export const mount = vueLifecycles.mount;
export const unmount = vueLifecycles.unmount;
export const DurationPicker = App;
