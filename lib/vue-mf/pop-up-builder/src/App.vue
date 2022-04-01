<script>
export default {
    name: 'App'
}
</script>
<script setup>
import { onBeforeMount } from 'vue'
import BlockEditor from './components/utils/BlockEditor.vue'
import { v4 as uuidv4 } from 'uuid';
import store from './store'

const props = defineProps({
    customProps: {
        type: Object
    }
})

onBeforeMount(() => {
    let jsonBlocks = localStorage.getItem('tiki-pop-up-blocks')
    let blocks = []
    if (jsonBlocks) blocks = JSON.parse(jsonBlocks)
    if (blocks.length === 0) {
        store.dispatch('addBlock', {
            id: uuidv4(),
            parent_id: null,
            name: 'Container',
            classes: [],
            styles: {}
        })
    } else {
        store.dispatch('setBlocks', blocks)
    }
})
</script>

<template>
    <BlockEditor></BlockEditor>
</template>
