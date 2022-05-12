import singleSpaVue from 'single-spa-vue'
import { createApp, h } from 'vue'
import App from './App.vue'
import BlockEdit from './components/utils/BlockEdit.vue'
import store from './store'

import Toast from 'vue-toastification'
import FloatingVue from 'floating-vue'
import autosize from 'autosize'
import VueNextSelect from 'vue-next-select'
import Vue3ColorPicker from "vue3-colorpicker"
import { Tabs, Tab } from 'vue3-tabs-component'

import 'vue-toastification/dist/index.css'
import 'floating-vue/dist/style.css'
import 'vue-next-select/dist/index.min.css'
import "vue3-colorpicker/style.css"

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
        app.use(Vue3ColorPicker)
        app.use(store)

        app.component('VueSelect', VueNextSelect)
        app.component('Tabs', Tabs)
        app.component('Tab', Tab)
        
        coreBlocks.forEach(block => {
            const { name, component, componentEdit, metadata } = block.default
            app.component(name, component)
            app.component(name + '-edit', componentEdit)
            store.dispatch('addBlockType', { [name]: metadata })
        })
        app.component('BlockEdit', BlockEdit)

        if (import.meta.env.MODE === 'development') {
            console.log(import.meta.env)
        }
    }
});

export const bootstrap = vueLifecycles.bootstrap
export const mount = vueLifecycles.mount
export const unmount = vueLifecycles.unmount
