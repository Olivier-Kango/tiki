<script>
export default {
    name: 'BlockList'
}
</script>

<script setup>
import { Button } from '@vue-mf/styleguide'
import BlockAdd from './BlockAdd.vue'
import BlockAddAfter from './BlockAddAfter.vue'
import store from '../../store'

const props = defineProps({
    block: {
        type: Object,
        default: () => {}
    }
});
</script>

<template>
    <VDropdown>
        <Button variant="default" sm>
            <i class="fas fa-sign-in-alt"></i>
        </Button>
        <template #popper>
            <div v-for="name in store.getters.getBlockList">
                <BlockAdd :blockName="name" :block="block"></BlockAdd>
            </div>
        </template>
    </VDropdown>
    <VDropdown v-if="block.parent_id">
        <Button variant="default" sm>
            <i class="fas fa-sign-out-alt"></i>
        </Button>
        <template #popper>
            <div v-for="name in store.getters.getBlockList">
                <BlockAddAfter :blockName="name" :block="block"></BlockAddAfter>
            </div>
        </template>
    </VDropdown>
</template>

<style lang="scss">
.v-popper {
    display: inline-block;
}
</style>
