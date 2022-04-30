<script>
export default {
    name: 'BlockEdit'
}
</script>

<script setup>
import Block from './Block.vue'
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

// <Block @[eventType].stop="handleEvent($event, block.id)"></Block>
// const eventType = computed(() => props.edit ? 'click' : null)

const handleEvent = (e) => store.dispatch('setSelected', props.block.id)
</script>

<template>
    <Block
        :tag="block.name + '-edit'"
        :block="block"
        :context="context"
        @click.stop="handleEvent"
    />
</template>
