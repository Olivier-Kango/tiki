<script>
export default {
    name: 'BlockInspectorTree'
}
</script>

<script setup>
import { computed } from 'vue'
import BlockInspectorTreeView from './BlockInspectorTreeView.vue'
import BlockControls from './BlockControls.vue'
import store from '../../store'

const root = computed(() => store.getters.getRootBlock)

const isSelected = computed(() => (block) => {
    if (!store.getters.getSelected) return false
    if (block.id === store.getters.getSelected.id) return true
})

const getBlockType = computed(() => (name) => store.getters.getBlockType(name))
const getBlock = computed(() => (id) => store.getters.getBlock(id))

const handleMouseOver = (e, id) => {
    store.dispatch('setHighlighted', id)
}

const handleMouseOut = (e, id) => {
    store.dispatch('setHighlighted', null)
}

const handleClick = (e, id) => {
    store.dispatch('setSelected', id)
}
</script>

<template>
    <div>
        <TransitionGroup name="list">
            <div
                v-for="blockId in root.innerBlocks"
                :id="'block-tree-' + root.id"
                class="block-tree"
                :key="'block-tree-' + root.id"
            >
                <BlockInspectorTreeView :block="getBlock(blockId)">
                    <template #default="{ block }">
                        <div
                            class="block-tree-item"
                            :class="{'is-selected': isSelected(block)}"
                            @click="handleClick($event, block.id)"
                            @mouseenter="handleMouseOver($event, block.id)"
                            @mouseleave="handleMouseOut($event, block.id)"
                        >
                            <small><b>{{ getBlockType(block.name).title }}</b></small>
                            <BlockControls class="d-inline-block" :block="block"></BlockControls>
                        </div>
                    </template>
                </BlockInspectorTreeView>
            </div>
        </TransitionGroup>
    </div>
</template>

<style lang="scss">
    .block-tree {
        position: relative;
    }
    .block-tree-item {
        cursor: pointer;
        display: inline-block;
        padding: 0 10px;
        margin-bottom: 4px;
        border-radius: 2px;
    }
</style>
