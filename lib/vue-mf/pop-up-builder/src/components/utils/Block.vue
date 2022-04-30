<script>
export default {
    name: 'Block'
}
</script>

<script setup>
defineProps({
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
</script>

<template>
    <component
        :is="block.name"
        :id="block.id"
        :data-id="block.id"
        :block="block"
        :context="context"
    >
        <Block
            v-for="_id in block.innerBlocks"
            :key="'block-' + _id"
            :block="blocks[_id]"
            :blocks="blocks"
            :context="context"
        />
    </component>
</template>
