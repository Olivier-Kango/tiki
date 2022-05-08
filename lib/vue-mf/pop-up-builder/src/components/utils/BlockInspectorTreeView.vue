<script>
export default {
    name: 'BlockInspectorTreeView'
}
</script>

<script setup>
import { computed } from 'vue'
import store from '../../store'

defineProps({
    block: Object
});

const getBlock = computed(() => (id) => store.getters.getBlock(id))
</script>

<template>
    <div>
        <slot name="default" :block="block"></slot>
        <TransitionGroup name="list">
            <BlockInspectorTreeView
                v-for="blockId in block.innerBlocks"
                :key="'block-tree-' + blockId"
                class="ms-4 block-tree"
                :id="'block-tree-' + blockId"
                :block="getBlock(blockId)"
            >
                <template #default="{ block }">
                    <slot name="default" :block="block"></slot>
                </template>
            </BlockInspectorTreeView>
        </TransitionGroup>
    </div>
</template>
