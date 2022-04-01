<script>
export default {
    name: 'BlockEditor'
}
</script>

<script setup>
import BlockBuilder from './BlockBuilder.vue'
import BlockTree from './BlockTree.vue'
import store from '../../store'

</script>

<template>
    <div class="block-editor d-flex">
        <BlockTree :rootNode="store.getters.getBlocksTree" edit />
        <BlockBuilder class="flex-grow-1" :rootNode="store.getters.getBlocksTree" edit />
    </div>
</template>

<style lang="scss">

</style>
