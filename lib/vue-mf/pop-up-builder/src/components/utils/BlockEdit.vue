<script>
export default {
    name: 'BlockEdit'
}
</script>

<script setup>
import { ref, computed, watch } from 'vue'
import draggable from 'vuedraggable/src/vuedraggable'
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

//@[eventType].stop="handleEvent($event, block.id)"
// const eventType = computed(() => props.edit ? 'click' : null)

const dragging = ref(false)

const handleEvent = (e, id) => store.dispatch('setSelected', id)
// const startDragging = () => dragging.value = true
// const endDragging = () => dragging.value = false

// const handleChange = (event) => {
//     if (event.moved) {
//         store.dispatch('moveBlock', {
//             id: event.moved.element,
//             oldIndex: event.moved.oldIndex,
//             newIndex: event.moved.newIndex
//         })
//     } else if (event.added) {
//         store.dispatch('addBlock', {
//             id: event.added.element,
//             dropTargetId: props.block.id,
//             newIndex: event.added.newIndex
//         })
//     } else if (event.removed) {
//         store.dispatch('removeBlock', {
//             id: event.removed.element,
//             oldIndex: event.removed.oldIndex
//         })
//     }

//     console.log(event)
// }
</script>

<template>
    <component
        :is="block.name + '-edit'"
        :id="block.id"
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
