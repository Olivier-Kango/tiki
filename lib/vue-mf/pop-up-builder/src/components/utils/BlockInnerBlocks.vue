<script>
export default {
    name: 'BlockInnerBlocks'
}
</script>

<script setup>
import { computed } from 'vue'
import Block from './Block.vue'
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
    <Block
        v-for="id in ids"
        :key="'block-' + id"
        :block="blocks[id]"
        :blocks="blocks"
        :context="context"
    />
</template>
