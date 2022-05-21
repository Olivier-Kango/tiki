<script>
export default {
    name: 'BlockBuilder'
}
</script>

<script setup>
import { computed } from 'vue'
import _isEmpty from 'lodash/isEmpty'
import BlockInnerBlocks from './BlockInnerBlocks.vue'
import BlockInnerBlocksEdit from './BlockInnerBlocksEdit.vue'
import store from '../../store'

const props = defineProps({
    id: String,
    context: {
        type: Object,
        default: () => null
    },
    edit: Boolean
})

const getInnerBlocks = computed(() => store.getters.getBlock(props.id).innerBlocks)
</script>

<template>
    <BlockInnerBlocksEdit v-if="edit" :ids="getInnerBlocks" :context="context" />
    <BlockInnerBlocks v-if="!edit" :ids="getInnerBlocks" :context="context" />
</template>
