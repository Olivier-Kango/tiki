<script>
export default {
    name: 'BlockEdit'
}
</script>

<script setup>
import { computed } from 'vue'
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
    let classes = {
        'block-editor-block': true,
        'is-dropzone-active': store.getters.getDropzonedBlock?.id === id,
        'is-dragged': store.getters.getDraggedBlock?.id === id,
    }
    return classes
})
const handleEvent = (e, id) => store.dispatch('setSelected', id)
</script>

<template>
    <component
        :is="block.name + '-edit'"
        :id="block.id"
        :class="getBlockClasses(block.id)"
        :data-id="block.id"
        :block="block"
        :context="context"
        @click.stop="handleEvent($event, block.id)"
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
</template>

<style lang="scss" scoped>
    .block-editor-block {
        &.is-dragged {
            position: relative;
        }

        min-height: 10px;
    }
</style>
