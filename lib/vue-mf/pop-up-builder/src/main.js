import singleSpaVue from "single-spa-vue"
import { createApp, h } from 'vue'
import App from './App.vue'
import store from './store'

import Toast from 'vue-toastification'
import FloatingVue from 'floating-vue'
import autosize from 'autosize'

import "vue-toastification/dist/index.css"
import 'floating-vue/dist/style.css'

import coreBlocks from './components/blocks'
import './components/blocks/styles.scss'

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
        app.use(Toast, {
            timeout: 4000,
            hideProgressBar: true,
            showCloseButtonOnHover: true,
            icon: false,
        });
        app.use(FloatingVue)
        app.use(store)
        
        coreBlocks.forEach(block => {
            const { name, component, metadata } = block.default
            app.component(name, component)
            store.dispatch('addBlockType', { [name]: metadata })
        })

        if (import.meta.env.MODE === 'development') {
            console.log(import.meta.env)
        }
    }
});

export const bootstrap = vueLifecycles.bootstrap
export const mount = vueLifecycles.mount
export const unmount = vueLifecycles.unmount
