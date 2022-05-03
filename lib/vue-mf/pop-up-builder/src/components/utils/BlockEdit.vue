<script>
export default {
    name: 'BlockEdit'
}
</script>

<script setup>
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

const handleEvent = (e, id) => store.dispatch('setSelected', id)
</script>

<template>
    <component
        :is="block.name + '-edit'"
        :id="block.id"
        class="block-editor-block"
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
