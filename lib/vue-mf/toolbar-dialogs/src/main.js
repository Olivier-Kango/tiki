import singleSpaVue from "single-spa-vue";
import { createApp } from 'vue'
import App from './App.vue'

//createApp(App).mount('#app')

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
                    syntax: this.syntax
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
export const ToolbarDialogs = App;
