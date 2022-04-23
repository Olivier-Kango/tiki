<script>
export default {
    name: 'Block'
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

const getBlock = computed(() => (id) => props.blocks[id])
const eventType = computed(() => props.edit ? 'click' : null)

const handleEvent = (e, id) => store.dispatch('setSelected', id)
</script>

<template>
    <component
        :is="block.name"
        class="block-item"
        :id="block.id"
        :data-id="block.id"
        :block="block"
        :context="context"
        :contextFields="contextFields"
        :edit="edit"
        @[eventType].stop="handleEvent($event, block.id)"
    >
        <TransitionGroup name="list" :css="edit">
            <Block
                v-for="_id in block.innerBlocks"
                :key="'block-' + _id"
                :block="getBlock(_id)"
                :edit="edit"
                :blocks="blocks"
                :context="context"
                :contextFields="contextFields"
            />
        </TransitionGroup>
    </component>
</template>
