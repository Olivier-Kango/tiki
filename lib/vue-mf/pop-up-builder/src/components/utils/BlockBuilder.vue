<script>
export default {
    name: 'BlockBuilder'
}
</script>

<script setup>
import Block from './Block.vue'
import store from '../../store'

const props = defineProps({
    blocks: {
        type: Array,
        default: () => []
    },
    edit: Boolean
});
</script>

<template>
    <div class="block-builder">
        <Block
            v-for="block in blocks"
            :block="block"
            :id="'block-' + block.data.id"
            :key="'block-' + block.data.id"
            :edit="edit"
        />
    </div>
</template>

<style lang="scss">

</style>
