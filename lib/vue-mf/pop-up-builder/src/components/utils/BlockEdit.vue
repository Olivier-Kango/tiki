<script>
export default {
    name: 'BlockEdit'
}
</script>

<script setup>
import { computed } from 'vue'
import BlockDraggable from './BlockDraggable.vue'
import store from '../../store'

const props = defineProps({
    block: {
        type: Object,
        default: () => {}
    },
    blocks: {
        type: Array,
        default: () => []
    },
    context: {
        type: Object,
        default: () => null
    }
})

const getBlockClasses = computed(() => (id) => {
    let classes = [{
        'block-editor-block': true,
        'is-dropzone-active': store.getters.getDropzonedBlock?.id === id,
        'is-dragged': store.getters.getDraggedBlock?.id === id,
        'is-selected': store.getters.getSelected?.id === id,
        'is-highlighted': store.getters.getHighlighted?.id === id,
    }]
    return Array.isArray(props.block.classes) ? props.block.classes.concat(classes) : classes
})
const handleEvent = (e, id) => store.dispatch('setSelected', id)
</script>

<template>
    <BlockDraggable
        :id="'block-' + block.id"
        :class="getBlockClasses(block.id)"
        :style="block.styles"
        :data-block="block.id"
        :block="block"
        drop
        @click.stop="handleEvent($event, block.id)"
    >
        <component
            :is="block.name + '-edit'"
            :block="block"
            :context="context"
        >
            <TransitionGroup name="list">
                <BlockEdit
                    v-for="_id in block.innerBlocks"
                    :key="'block-' + _id"
                    :block="blocks[_id]"
                    :blocks="blocks"
                    :context="context"
                />
            </TransitionGroup>
        </component>
    </BlockDraggable>
</template>

<style lang="scss" scoped>
    .block-editor-block {
        &.is-dragged,  &.is-selected {
            position: relative;
        }

        min-height: 10px;
    }
</style>
