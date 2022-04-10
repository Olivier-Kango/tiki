<script>
export default {
    name: 'BlockTreeView'
}
</script>

<script setup>
defineProps({
    node: Object
});
</script>

<template>
    <slot name="default" :node="node"></slot>
    <div>
        <div
            v-for="childNode in node.children"
            class="ml-4"
            :id="'block-tree-' + childNode.id"
            :key="'block-tree-' + childNode.id"
        >
            <BlockTreeView :node="childNode">
                <template #default="{ node }">
                    <slot name="default" :node="node"></slot>
                </template>
            </BlockTreeView>
        </div>
    </div>
</template>
