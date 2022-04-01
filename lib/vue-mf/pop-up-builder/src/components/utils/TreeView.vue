<script>
export default {
    name: 'TreeView'
}
</script>

<script setup>
import BlockControls from './BlockControls.vue'

defineProps({
    node: Object
});
</script>

<template>
    <slot name="default" :node="node"></slot>
    <div>
        <div
            v-for="childNode in node.children"
            class="pl-4"
            :id="'block-tree-' + childNode.id"
            :key="'block-tree-' + childNode.id"
        >
            <TreeView :node="childNode">
                <template #default="{ node }">
                    <slot name="default" :node="node"></slot>
                </template>
            </TreeView>
        </div>
    </div>
</template>

<style lang="scss">

</style>
