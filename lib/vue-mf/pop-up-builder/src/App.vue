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
    let blocks = getBlocksFromLocalStorage()
    if (blocks) {
        store.dispatch('setBlocks', blocks)
        store.dispatch('setHistory', blocks)
    }

    let patterns = getPatternsFromLocalStorage()
    if (patterns) {
        store.dispatch('setPatterns', patterns)
        store.dispatch('setBlocksPattern', '/')
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
    let blocks = getDataFromLocalStorage('tiki-pop-up-blocks')
    if (!blocks) blocks = defaultBlocks
    return blocks
}

function getStoreOptionsFromLocalStorage() {
    let storeOptions = getDataFromLocalStorage('tiki-pop-up-blocks-store')
    if (!storeOptions) storeOptions = defaultApis
    return storeOptions
}

function getPatternsFromLocalStorage() {
    let patterns = getDataFromLocalStorage('tiki-pop-up-patterns')
    if (!patterns) patterns = defaultPatterns
    return patterns
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
