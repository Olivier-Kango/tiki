<script>
export default {
    name: 'BlockTree'
}
</script>

<script setup>
import { computed } from 'vue'
import TreeView from './TreeView.vue'
import BlockControls from './BlockControls.vue'
import store from '../../store'

defineProps({
    rootNode: {
        type: Object,
        default: () => {}
    },
    edit: Boolean
});

const isHighlighted = computed(() => (node) => {
    if (!store.getters.getHighlighted) return false
    if (node.data.id === store.getters.getHighlighted.data.id) return true
})

const isSelected = computed(() => (node) => {
    if (!store.getters.getSelected) return false
    if (node.data.id === store.getters.getSelected.data.id) return true
})

const handleMouseOver = (e, node) => {
    store.dispatch('setHighlighted', node)
}

const handleMouseOut = (e, node) => {
    store.dispatch('setHighlighted', null)
}

const handleClick = (e, node) => {
    store.dispatch('setSelected', node)
}
</script>

<template>
    <div>
        <div v-for="node in rootNode.children" :id="'block-tree-' + rootNode.id" class="block-tree">
            <TreeView :node="node">
                <template #default="{ node }">
                    <div class="block-tree-item" :class="{'is-selected': isSelected(node), 'is-highlighted': isHighlighted(node)}">
                        <BlockControls :node="node" @click="handleClick($event, node)" @mouseenter="handleMouseOver($event, node)" @mouseleave="handleMouseOut($event, node)"></BlockControls>
                    </div>
                </template>
            </TreeView>
        </div>
    </div>
</template>

<style lang="scss" scoped>
    .block-tree-item {
        cursor: pointer;
        display: inline-block;
        padding: 0 10px;
        border-radius: 2px;
    }
</style>
