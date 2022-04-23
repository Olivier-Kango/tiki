<script>
export default {
    name: 'App'
}
</script>
<script setup>
import { onBeforeMount, computed } from 'vue'
import BlockEditor from './components/utils/BlockEditor.vue'
import store from './store'
import defaultTemplate from './components/templates/default.json'
import axios from 'axios'

const props = defineProps({
    customProps: {
        type: Object
    }
})

onBeforeMount(() => {
    let blocks = getBlocksFromLocalStorage()
    if (blocks) store.dispatch('setBlocks', blocks)

    let storeOptions = getStoreOptionsFromLocalStorage()
    if (storeOptions) {
        createStoreModule(storeOptions)
        storeOptions.forEach(option => store.dispatch('store-module/' + 'add_' + option.field))
    }

    store.dispatch('setMode', 'edit')
    console.log(store)
})

const edit = computed(() => store.getters.getMode === 'edit')

function getBlocksFromLocalStorage() {
    let blocks = null
    let jsonBlocks = localStorage.getItem('tiki-pop-up-blocks')

    if (jsonBlocks) {
        try {
            blocks = JSON.parse(jsonBlocks)
        } catch (err) {
            console.log(err)
        }
    }
    return blocks
}

function getStoreOptionsFromLocalStorage() {
    let storeOptions = null
    let jsonBlocksStore = localStorage.getItem('tiki-pop-up-blocks-store')

    if (jsonBlocksStore) {
        try {
            storeOptions = JSON.parse(jsonBlocksStore)
        } catch (err) {
            console.log(err)
        }
    }
    return storeOptions
}

function createStoreModule(storeOptions) {
    let options = {
        namespaced: true,
        state: {},
        getters: {},
        actions: {},
        mutations: {}
    }

    // Set dynamic store options with APIs and fields
    storeOptions.forEach(option => {
        options.state[option.field] = null
        options.getters['get_' + option.field] = (state) => state[option.field]
        options.actions['add_' + option.field] = ({ commit }) => {
            return axios(option.url)
                .then(res => {
                    commit('set_' + option.field, res.data)
                })
        }
        options.mutations['set_' + option.field] = (state, data) => {
            state[option.field] = data
        }
    })
    options.state['fields'] = storeOptions.map(option => option.field)
    options.getters['get_fields'] = (state) => state.fields

    // Register store module and dispatch actions
    store.registerModule('store-module', options)
}
</script>

<template>
    <BlockEditor :edit="edit"></BlockEditor>
</template>
