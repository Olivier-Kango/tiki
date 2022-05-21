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
    context: {
        type: Object,
        default: () => null
    }
})

const getBlockClasses = computed(() =>
    ({
        'block-builder-block': true,
        'is-dropzone-active': store.getters.getDropzonedBlock?.id === props.block.id,
        'is-dragged': store.getters.getDraggedBlock?.id === props.block.id,
        'is-selected': store.getters.getSelected?.id === props.block.id,
        'is-highlighted': store.getters.getHighlighted?.id === props.block.id,
    })
)
const getBlock = computed(() => (id) => store.getters.getBlock(id))

const handleEvent = () => store.dispatch('setSelected', props.block.id) 
</script>

<template>
    <BlockDraggable
        :id="'block-' + block.id"
        :class="[...block.classes, getBlockClasses]"
        class="block-editor-block"
        :style="block.styles"
        :data-block="block.id"
        :block="block"
        drop
        dropArea="block-builder"
        @click.stop="handleEvent"
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
                    :block="getBlock(_id)"
                    :context="context"
                />
            </TransitionGroup>
        </component>
    </BlockDraggable>
</template>

<style lang="scss">
    .block-builder-block {
        &.is-dragged,  &.is-selected {
            position: relative;
        }

        min-height: 15px;
    }
</style>
