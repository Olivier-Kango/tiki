<script>
export default {
    name: 'BlockTree'
}
</script>

<script setup>
import { computed } from 'vue'
import BlockTreeView from './BlockTreeView.vue'
import BlockControls from './BlockControls.vue'
import store from '../../store'

defineProps({
    edit: Boolean
});

const root = computed(() => store.getters.getTree)

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
        <div v-for="node in root.children" :id="'block-tree-' + root.id" class="block-tree">
            <BlockTreeView :node="node">
                <template #default="{ node }">
                    <div
                        class="block-tree-item"
                        :class="{'is-selected': isSelected(node), 'is-highlighted': isHighlighted(node)}"
                        @click="handleClick($event, node)"
                        @mouseenter="handleMouseOver($event, node)"
                        @mouseleave="handleMouseOut($event, node)"
                    >
                        <small><b>{{node.data.name}}</b></small>
                        <BlockControls class="d-inline-block" :block="node.data"></BlockControls>
                    </div>
                </template>
            </BlockTreeView>
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
