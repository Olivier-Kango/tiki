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
    context: {
        type: Object,
        default: () => null
    },
})

const getBlock = computed(() => (id) => store.getters.getBlock(id))
</script>

<template>
    <TransitionGroup name="list">
        <BlockEdit
            v-for="id in ids"
            :key="'block-' + id"
            :block="getBlock(id)"
            :context="context"
        />
    </TransitionGroup>
</template>
