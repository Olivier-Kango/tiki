import singleSpaVue from "single-spa-vue";
import { createApp, h } from 'vue';
import PerfectScrollbar from 'vue3-perfect-scrollbar';
import { SetupCalendar, DatePicker } from 'v-calendar';
import App from './App.vue';
import store from './store';

import 'vue3-perfect-scrollbar/dist/vue3-perfect-scrollbar.css';
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
        app.use(store);
        app.use(PerfectScrollbar);
        app.use(SetupCalendar, {})
            .component('DatePicker', DatePicker);
        console.log(import.meta.env);
    }
});

export const bootstrap = vueLifecycles.bootstrap;
export const mount = vueLifecycles.mount;
export const unmount = vueLifecycles.unmount;
