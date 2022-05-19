<script>
export default {
    name: 'App'
}
</script>
<script setup>
import { onBeforeMount } from 'vue'
import BlockEditor from './components/utils/BlockEditor.vue'
import store from './store'
import { registerStoreModule } from './store/helpers'
import defaultBlocks from './components/data/blocks.json'
import defaultPatterns from './components/data/patterns.json'
import defaultApis from './components/data/apis.json'

const props = defineProps({
    customProps: {
        type: Object
    }
})

onBeforeMount(() => {
    let patterns = getPatternsFromLocalStorage()
    if (!patterns) patterns = defaultPatterns
    if (patterns) {
        store.dispatch('setPatterns', patterns)
    }

    let blocks = getBlocksFromLocalStorage()
    if (!blocks) blocks = defaultBlocks
    store.dispatch('setBlocks', blocks)
    // store.dispatch('setRootBlock', 'root')
    store.dispatch('setHistory')

    let storeOptions = getStoreOptionsFromLocalStorage()
    if (!storeOptions) storeOptions = defaultApis
    if (storeOptions) {
        registerStoreModule(storeOptions, store)
        store.dispatch('setAPIoptions', storeOptions)
        storeOptions.forEach(option => store.dispatch(`module_${option.field}/add_${option.field}`))
    }

    store.dispatch('setMode', 'edit')
})

function getBlocksFromLocalStorage() {
    return getDataFromLocalStorage('tiki-pop-up-blocks')
}

function getStoreOptionsFromLocalStorage() {
    return getDataFromLocalStorage('tiki-pop-up-blocks-store')
}

function getPatternsFromLocalStorage() {
    return getDataFromLocalStorage('tiki-pop-up-patterns')
}

function getDataFromLocalStorage(name) {
    let data = null
    let jsonData = localStorage.getItem(name)
    if (jsonData) {
        try {
            data = JSON.parse(jsonData)
        } catch (err) {
            console.log(err)
        }
    }
    return data
}
</script>

<template>
    <BlockEditor></BlockEditor>
</template>
