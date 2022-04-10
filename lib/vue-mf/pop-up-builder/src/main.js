import singleSpaVue from "single-spa-vue"
import { createApp, h } from 'vue'
import Toast from 'vue-toastification'
import FloatingVue from 'floating-vue'
import App from './App.vue'
import store from './store'
import autosize from 'autosize'

import Container from './components/blocks/Container.vue'
import Button from './components/blocks/Button.vue'
import Title from './components/blocks/Title.vue'
import Paragraph from './components/blocks/paragraph/Paragraph.vue'
import Row from './components/blocks/Row.vue'
import Col from './components/blocks/Col.vue'
import Image from './components/blocks/Image.vue'

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

        app.component('Container', Container)
        app.component('Button', Button)
        app.component('Title', Title)
        app.component('Paragraph', Paragraph)
        app.component('Row', Row)
        app.component('Col', Col)
        app.component('Image', Image)
        if (import.meta.env.MODE === 'development') {
            console.log(import.meta.env)
        }
    }
});

export const bootstrap = vueLifecycles.bootstrap
export const mount = vueLifecycles.mount
export const unmount = vueLifecycles.unmount
