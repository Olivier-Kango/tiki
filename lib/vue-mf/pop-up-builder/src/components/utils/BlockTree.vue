<script>
export default {
    name: 'BlockTree'
}
</script>

<script setup>
import TreeView from './TreeView.vue'
import BlockControls from './BlockControls.vue'

defineProps({
    rootNode: {
        type: Object,
        default: () => {}
    },
    edit: Boolean
});
</script>

<template>
    <div :id="'block-tree-' + rootNode.id" class="block-tree">
        <TreeView :node="rootNode">
            <template #default="{ node }">
                <BlockControls :node="node"></BlockControls>
            </template>
        </TreeView>
    </div>
</template>

<style lang="scss">

</style>
