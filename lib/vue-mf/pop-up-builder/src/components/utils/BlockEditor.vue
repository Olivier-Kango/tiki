<script>
export default {
    name: 'BlockEditor'
}
</script>

<script setup>
import { ref, computed } from 'vue'
import BlockNavbar from './BlocksNavbar.vue'
import BlockBuilder from './BlockBuilder.vue'
import BlockInspectorTree from './BlockInspectorTree.vue'
import store from '../../store'

const showInspector = ref(true)

const getRootBlock = computed(() => store.getters.getRootBlock)
const isEditMode = computed(() => store.getters.getMode === 'edit')

const handleClick = (e) => {
    store.dispatch('setSelected', null)
}

const handleShowInspector = (val) => {
    showInspector.value = val
}
</script>

<template>
    <div class="block-editor">
        <BlockNavbar @showInspector="handleShowInspector"></BlockNavbar>
        <div class="d-flex mt-1">
            <BlockInspectorTree v-if="showInspector && isEditMode" class="p-2" style="min-width: 222px; border-right: 1px solid #404040;"></BlockInspectorTree>
            <div class="block-builder flex-grow-1" @click="handleClick">
                <div class="block--container container-fluid">
                    <BlockBuilder :id="getRootBlock.id" />
                </div>
            </div>
        </div>
    </div>
</template>
