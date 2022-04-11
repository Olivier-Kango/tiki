import singleSpaVue from "single-spa-vue"
import { createApp, h } from 'vue'
import Toast from 'vue-toastification'
import FloatingVue from 'floating-vue'
import App from './App.vue'
import store from './store'
import autosize from 'autosize'

import Container from './components/blocks/container/Container.vue'
import Button from './components/blocks/button/Button.vue'
import Title from './components/blocks/title/Title.vue'
import Paragraph from './components/blocks/paragraph/Paragraph.vue'
import Columns from './components/blocks/columns/Columns.vue'
import Column from './components/blocks/column/Column.vue'
import Image from './components/blocks/image/Image.vue'
import './components/blocks/styles.scss'

import "vue-toastification/dist/index.css"
import 'floating-vue/dist/style.css'

import '../custom.scss'

const vueLifecycles = singleSpaVue({
    createApp,
    appOptions: {
        render() {
            return h(App,
                {
                    customProps: {
                        // single-spa props are available on the "this" object. Forward them to your component as needed.
                        // https://single-spa.js.org/docs/building-applications#lifecyle-props
                        // name: this.name,
                        // mountParcel: this.mountParcel,
                        // singleSpa: this.singleSpa,
                        data: this.data,
                    },
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
        });
        app.directive('autosize', {
            // When the bound element is mounted into the DOM...
            mounted(el) {
                // Autosizes the element
                if (el.tagName === 'TEXTAREA') autosize(el)
            }
        });
        app.use(store);
        app.use(Toast, {
            timeout: 4000,
            hideProgressBar: true,
            showCloseButtonOnHover: true,
            icon: false,
        });
        app.use(FloatingVue)

        app.component('core/container', Container)
        app.component('core/button', Button)
        app.component('core/title', Title)
        app.component('core/paragraph', Paragraph)
        app.component('core/columns', Columns)
        app.component('core/column', Column)
        app.component('core/image', Image)
        if (import.meta.env.MODE === 'development') {
            console.log(import.meta.env)
        }
    }
});

export const bootstrap = vueLifecycles.bootstrap
export const mount = vueLifecycles.mount
export const unmount = vueLifecycles.unmount
