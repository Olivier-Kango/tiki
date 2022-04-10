<script>
export default {
    name: 'BlockBuilder'
}
</script>

<script setup>
import { computed } from 'vue'
import Block from './Block.vue'
import store from '../../store'

defineProps({
    edit: Boolean
});

const root = computed(() => store.getters.getTree)
const rootBlock = computed(() => store.getters.getRootBlock)
</script>

<template>
    <div class="block-builder" :id="rootBlock.id">
        <Block
            v-for="node in root.children"
            :node="node"
            :id="'block-' + node.data.id"
            :key="'block-' + node.data.id"
            :edit="edit"
        />
    </div>
</template>
