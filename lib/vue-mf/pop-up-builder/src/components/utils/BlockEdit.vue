<script>
export default {
    name: 'BlockEdit'
}
</script>

<script setup>
import { computed } from 'vue'
import store from '../../store'

const props = defineProps({
    edit: Boolean,
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
    },
    contextFields: {
        type: Array,
        default: () => []
    }
});

//@[eventType].stop="handleEvent($event, block.id)"
// const eventType = computed(() => props.edit ? 'click' : null)

const handleEvent = (e, id) => store.dispatch('setSelected', id)
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
