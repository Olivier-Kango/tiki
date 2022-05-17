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
    }
})

const getBlocks = computed(() => store.getters.getBlocks)
const getInnerBlocks = computed(() => store.getters.getBlocks[props.id].innerBlocks)
const isEditMode = computed(() => store.getters.getMode === 'edit')
</script>

<template>
    <BlockInnerBlocksEdit v-if="isEditMode" :ids="getInnerBlocks" :blocks="getBlocks" :context="context" />
    <BlockInnerBlocks v-if="!isEditMode" :ids="getInnerBlocks" :blocks="getBlocks" :context="context" />
</template>
