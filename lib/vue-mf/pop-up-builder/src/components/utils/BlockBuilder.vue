<script>
export default {
    name: 'BlockBuilder'
}
</script>

<script setup>
import Block from './Block.vue'

defineProps({
    rootNode: {
        type: Object,
        default: () => {}
    },
    edit: Boolean
});
</script>

<template>
    <div class="block-builder">
        <Block
            :node="rootNode"
            :id="'block-' + rootNode.data.id"
            :key="'block-' + rootNode.data.id"
            :edit="edit"
        />
    </div>
</template>

<style lang="scss">

</style>
