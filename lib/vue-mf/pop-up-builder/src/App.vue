<script>
export default {
    name: 'App'
}
</script>
<script setup>
import { onBeforeMount } from 'vue'
import BlockEditor from './components/utils/BlockEditor.vue'
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
    if (blocks.length === 0) blocks = [
        {
            id: 1,
            parent_id: null,
            name: 'Container',
            classes: '',
            styles: {

            }
        }
    ]
    store.dispatch('setBlocks', blocks)
})
</script>

<template>
    <BlockEditor></BlockEditor>
</template>
