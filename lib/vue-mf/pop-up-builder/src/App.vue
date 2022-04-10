<script>
export default {
    name: 'App'
}
</script>
<script setup>
import { onBeforeMount } from 'vue'
import BlockEditor from './components/utils/BlockEditor.vue'
import store from './store'
import initialTemplate from './components/templates/initial.json'

const props = defineProps({
    customProps: {
        type: Object
    }
})

onBeforeMount(() => {
    let blocks = []
    let jsonBlocks = localStorage.getItem('tiki-pop-up-blocks')

    if (jsonBlocks) {
        try {
            blocks = JSON.parse(jsonBlocks)
        } catch (err) {
            console.log(err)
        }
    }

    store.dispatch('setBlocks', blocks.length ? blocks : initialTemplate)
})
</script>

<template>
    <BlockEditor edit></BlockEditor>
</template>
