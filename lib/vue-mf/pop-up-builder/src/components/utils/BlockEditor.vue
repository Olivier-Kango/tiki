<script>
export default {
    name: 'BlockEditor'
}
</script>

<script setup>
import { computed } from 'vue'
import BlockNavbar from './BlocksNavbar.vue'
import BlockBuilder from './BlockBuilder.vue'
import BlockInspectorTree from './BlockInspectorTree.vue'
import store from '../../store'

const getRootBlock = computed(() => store.getters.getRootBlock)
const isEditMode = computed(() => store.getters.getMode === 'edit')

const handleClick = (e) => {
    store.dispatch('setSelected', null)
}
</script>

<template>
    <div class="block-editor">
        <BlockNavbar></BlockNavbar>
        <div class="d-flex mt-1">
            <BlockInspectorTree v-if="isEditMode" class="p-2" style="min-width: 300px;"></BlockInspectorTree>
            <div class="block-builder flex-grow-1" @click="handleClick">
                <BlockBuilder :id="getRootBlock.id" />
            </div>
        </div>
    </div>
</template>
