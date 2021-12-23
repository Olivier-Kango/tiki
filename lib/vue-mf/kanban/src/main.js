import singleSpaVue from "single-spa-vue";
import { createApp, h } from 'vue';
import PerfectScrollbar from 'vue3-perfect-scrollbar';
import { SetupCalendar, DatePicker } from 'v-calendar';
import Toast from "vue-toastification"
import { defineRule } from 'vee-validate'
import App from './App.vue';
import store from './store';

import 'vue3-perfect-scrollbar/dist/vue3-perfect-scrollbar.css';
import "vue-toastification/dist/index.css";
import '../custom.scss';

const vueLifecycles = singleSpaVue({
    createApp,
    appOptions: {
        render() {
            return h(App,
                {
                    // props: {
                    //     // single-spa props are available on the "this" object. Forward them to your component as needed.
                    //     // https://single-spa.js.org/docs/building-applications#lifecyle-props
                    //     name: this.name,
                    //     mountParcel: this.mountParcel,
                    //     singleSpa: this.singleSpa,
                    // },
                }
            );
        }
    },
    handleInstance: (app) => {
        // Register a global custom directive called `v-focus`
        app.directive('focus', {
            // When the bound element is mounted into the DOM...
            mounted(el) {
                // Focus the element
                el.focus()
            }
        })
        app.use(store);
        app.use(PerfectScrollbar);
        app.use(SetupCalendar, {})
            .component('DatePicker', DatePicker);
        app.use(Toast, {
            timeout: 4000,
            hideProgressBar: true,
            showCloseButtonOnHover: true,
            icon: false,
        });
        if (import.meta.env.MODE === 'development') {
            console.log(import.meta.env);
        }
    }
});

defineRule('minLength', (value, [limit]) => {
    if (value.length < parseInt(limit)) {
        return `This field must be at least ${limit} character`;
    }
    return true;
});

export const bootstrap = vueLifecycles.bootstrap;
export const mount = vueLifecycles.mount;
export const unmount = vueLifecycles.unmount;
