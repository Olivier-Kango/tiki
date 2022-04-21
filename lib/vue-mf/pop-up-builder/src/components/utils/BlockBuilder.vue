<script>
export default {
    name: 'BlockBuilder'
}
</script>

<script setup>
import { computed } from 'vue'
import Block from './Block.vue'

const props = defineProps({
    edit: Boolean,
    blocks: {
        type: Array,
        default: () => []
    },
    blockIds: {
        type: Array,
        default: () => []
    }
});

const getBlock = computed(() => (id) => props.blocks[id])
</script>

<template>
    <TransitionGroup name="list">
        <Block
            v-for="id in blockIds"
            :key="'block-' + id"
            :block="getBlock(id)"
            :edit="edit"
            :blocks="blocks"
        />
    </TransitionGroup>
</template>
