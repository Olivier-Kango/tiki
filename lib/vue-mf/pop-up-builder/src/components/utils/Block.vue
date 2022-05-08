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
    <div
        :id="'block-' + block.id"
        :class="block.classes"
        :style="block.styles"
        :data-block="block.id"
    >
        <component
            :is="block.name"
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
    </div>
</template>
