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
    context: {
        type: Object,
        default: () => null
    },
})

const getBlock = computed(() => (id) => store.getters.getBlock(id))
</script>

<template>
    <Block
        v-for="id in ids"
        :key="'block-' + id"
        :block="getBlock(id)"
        :context="context"
    />
</template>
