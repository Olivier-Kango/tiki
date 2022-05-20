<script>
export default {
    name: 'BlockInspectorTree'
}
</script>

<script setup>
import { computed } from 'vue'
import BlockDraggable from './BlockDraggable.vue'
import BlockInspectorTreeView from './BlockInspectorTreeView.vue'
import store from '../../store'

const root = computed(() => store.getters.getRootBlock)

const isSelected = computed(() => (block) => {
    if (!store.getters.getSelected) return false
    if (block.id === store.getters.getSelected.id) return true
})

const getBlockType = computed(() => (name) => store.getters.getBlockType(name))
const getBlock = computed(() => (id) => {
    return store.getters.getBlock(id)
})

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
            <BlockInspectorTreeView
                v-for="blockId in root.innerBlocks"
                :key="'block-tree-' + root.id"
                :id="'block-tree-' + root.id"
                class="block-tree"
                :block="getBlock(blockId)"
            >
                <template #default="{ block }">
                    <BlockDraggable :block="block" drag drop dropArea="block-inspector">
                        <div
                            class="block-tree-item"
                            :id="'block-tree-' + block.id"
                            :data-block-tree="block.id"
                            :class="{'is-selected': isSelected(block)}"
                            @click="handleClick($event, block.id)"
                            @mouseenter="handleMouseOver($event, block.id)"
                            @mouseleave="handleMouseOut($event, block.id)"
                        >
                            <small><b>{{ getBlockType(block.name).title }}</b></small>
                            <!-- <BlockControls class="d-inline-block" :block="block"></BlockControls> -->
                        </div>
                        <template #draggable>
                            <div class="draggable-template shadow-sm">
                                <span class="me-2">{{ block.name }}</span>
                                <span>
                                    <i class="fas fa-cubes"></i>
                                </span>
                            </div>
                        </template>
                    </BlockDraggable>
                </template>
            </BlockInspectorTreeView>
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
        user-select: none;
    }
    .draggable-template {
        display: flex;
        align-items: center;
        padding: 10px 15px;
        background-color: #000000;
        color: white;
        border-radius: 2px;
    }
</style>
