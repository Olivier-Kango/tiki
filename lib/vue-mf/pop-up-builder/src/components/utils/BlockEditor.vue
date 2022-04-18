<script>
export default {
    name: 'BlockEditor'
}
</script>

<script setup>
import BlockNavbar from './BlocksNavbar.vue'
import BlockBuilder from './BlockBuilder.vue'
import BlockTree from './BlockTree.vue'

defineProps({
    edit: Boolean
})
</script>

<template>
    <div class="block-editor">
        <BlockNavbar></BlockNavbar>
        <div class="d-flex">
            <BlockTree v-if="edit" class="p-2"></BlockTree>
            <BlockBuilder class="flex-grow-1" :edit="edit" />
        </div>
    </div>
</template>
