<script>
export default {
    name: 'BlockBuilder'
}
</script>

<script setup>
import { computed } from 'vue'
import Block from './Block.vue'
import BlockControls from './BlockControls.vue'
import store from '../../store'

defineProps({
    rootNode: {
        type: Object,
        default: () => {}
    },
    edit: Boolean
});

const selected = computed(() => store.getters.getSelected)
const rootBlock = computed(() => store.getters.getRootBlock)
</script>

<template>
    <div class="block-builder" :id="rootBlock.id">
        <Block
            v-for="node in rootNode.children"
            :node="node"
            :id="'block-' + node.data.id"
            :key="'block-' + node.data.id"
            :edit="edit"
        />
    </div>
</template>

<style lang="scss">

</style>
