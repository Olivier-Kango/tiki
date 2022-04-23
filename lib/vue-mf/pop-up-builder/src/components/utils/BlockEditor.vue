<script>
export default {
    name: 'BlockEditor'
}
</script>

<script setup>
import { computed } from 'vue'
import BlockNavbar from './BlocksNavbar.vue'
import BlockBuilder from './BlockBuilder.vue'
import BlockTree from './BlockTree.vue'
import store from '../../store'

defineProps({
    edit: Boolean
})

const blocks = computed(() => store.getters.getBlocks)
const getRootBlock = computed(() => store.getters.getRootBlock)
</script>

<template>
    <div class="block-editor">
        <BlockNavbar></BlockNavbar>
        <div class="d-flex">
            <BlockTree v-if="edit" class="p-2"></BlockTree>
            <div class="block-builder flex-grow-1" :id="'block-' + getRootBlock.id">
                <BlockBuilder :blockIds="getRootBlock.innerBlocks" :edit="edit" />
            </div>
        </div>
    </div>
</template>
