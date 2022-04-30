<script>
export default {
    name: 'BlockInnerBlocksEdit'
}
</script>

<script setup>
import { computed } from 'vue'
import BlockEdit from './BlockEdit.vue'
import store from '../../store'

const props = defineProps({
    ids: {
        type: Array,
        default: () => []
    },
    blocks: {
        type: Object,
        default: () => {}
    },
    context: {
        type: Object,
        default: () => null
    },
})
</script>

<template>
    <TransitionGroup name="list">
        <BlockEdit
            v-for="id in ids"
            :key="'block-' + id"
            :block="blocks[id]"
            :blocks="blocks"
            :context="context"
        />
    </TransitionGroup>
</template>
