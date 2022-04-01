<script>
export default {
    name: 'BlockTree'
}
</script>

<script setup>
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

const handleMouseOver = (e, node) => {
    let classes = Array.from(node.data.classes)
    classes.push('is-selected')
    store.dispatch('updateBlock', {
        block: node.data,
        field: 'classes',
        value: classes
    })
}

const handleMouseOut = (e, node) => {
    let classes = Array.from(node.data.classes)
    store.dispatch('updateBlock', {
        block: node.data,
        field: 'classes',
        value: classes.filter(el => el !== 'is-selected')
    })
}

const handleClick = (e, node) => {
    // console.log(e, node)
    // node.data
}
</script>

<template>
    <div :id="'block-tree-' + rootNode.id" class="block-tree">
        <TreeView :node="rootNode">
            <template #default="{ node }">
                <BlockControls :node="node" @click="handleClick($event, node)" @mouseenter="handleMouseOver($event, node)" @mouseleave="handleMouseOut($event, node)"></BlockControls>
            </template>
        </TreeView>
    </div>
</template>

<style lang="scss">

</style>
