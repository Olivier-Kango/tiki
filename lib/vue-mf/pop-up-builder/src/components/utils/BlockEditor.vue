<script>
export default {
    name: 'BlockEditor'
}
</script>

<script setup>
import BlockBuilder from './BlockBuilder.vue'
import store from '../../store'

</script>

<template>
    <div class="block-editor">
        <BlockBuilder :blocks="store.getters.getBlocksTree" edit />
    </div>
</template>

<style lang="scss">

</style>
