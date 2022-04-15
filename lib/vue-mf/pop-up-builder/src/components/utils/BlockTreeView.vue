<script>
export default {
    name: 'BlockTreeView'
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
    <slot name="default" :block="block"></slot>
    <div>
        <TransitionGroup name="list">
            <div
                v-for="blockId in block.innerBlocks"
                class="ml-4 block-tree"
                :id="'block-tree-' + blockId"
                :key="'block-tree-' + blockId"
            >
                <BlockTreeView :block="getBlock(blockId)">
                    <template #default="{ block }">
                        <slot name="default" :block="block"></slot>
                    </template>
                </BlockTreeView>
            </div>
        </TransitionGroup>
    </div>
</template>
