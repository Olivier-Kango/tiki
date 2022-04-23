<script>
export default {
    name: 'BlockBuilder'
}
</script>

<script setup>
import { computed } from 'vue'
import Block from './Block.vue'
import store from '../../store'

const props = defineProps({
    edit: Boolean,
    blocks: {
        type: Array,
        default: () => store.getters.getBlocks || []
    },
    blockIds: {
        type: Array,
        default: () => []
    },
    context: {
        type: Object,
        default: () => null
    },
    contextFields: {
        type: Array,
        default: () => []
    }
});

const getBlock = computed(() => (id) => props.blocks[id])
</script>

<template>
    <TransitionGroup name="list" :css="edit">
        <Block
            v-for="id in blockIds"
            :key="'block-' + id"
            :block="getBlock(id)"
            :edit="edit"
            :blocks="blocks"
            :context="context"
            :contextFields="contextFields"
        />
    </TransitionGroup>
</template>
