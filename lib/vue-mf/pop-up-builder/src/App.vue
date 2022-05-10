<script>
export default {
    name: 'App'
}
</script>
<script setup>
import { onBeforeMount, computed } from 'vue'
import BlockEditor from './components/utils/BlockEditor.vue'
import store from './store'
import { registerStoreModule } from './store/helpers'
import defaultTemplate from './components/templates/default.json'

const props = defineProps({
    customProps: {
        type: Object
    }
})

onBeforeMount(() => {
    let blocks = getBlocksFromLocalStorage()
    if (blocks) {
        store.dispatch('setBlocks', blocks)
        store.dispatch('setHistory', blocks)
    }

    let storeOptions = getStoreOptionsFromLocalStorage()
    if (storeOptions) {
        registerStoreModule(storeOptions, store)
        store.dispatch('setAPIoptions', storeOptions)
        storeOptions.forEach(option => store.dispatch(`module_${option.field}/add_${option.field}`))
    }

    store.dispatch('setMode', 'edit')
})

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
    if (!blocks) blocks = defaultTemplate
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
</script>

<template>
    <BlockEditor></BlockEditor>
</template>
