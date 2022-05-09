<script>
export default {
    name: 'BlockList'
}
</script>

<script setup>
import { computed } from 'vue'
import { Button } from '@vue-mf/styleguide'
import BlockAdd from './BlockAdd.vue'
import BlockAddAfter from './BlockAddAfter.vue'
import store from '../../store'

const props = defineProps({
    block: {
        type: Object,
        default: () => {}
    }
})

const getAllowedBlockList = computed(() => {
    let allowedBlocks = props.block.attributes?.allowedBlocks
    if (Array.isArray(allowedBlocks)) {
        // temporarily disabled core/image block
        return allowedBlocks.filter(name => name !== 'core/image')
    } else {
        return store.getters.getBlockList
    }
})

const getParentBlockList = computed(() => {
    let parentBlock = store.getters.getParentBlock(props.block.id)
    let allowedBlocks = parentBlock.attributes?.allowedBlocks
    console.log(parentBlock, allowedBlocks)
    if (Array.isArray(allowedBlocks)) {
        return allowedBlocks
    } else {
        return store.getters.getBlockList
    }
})
</script>

<template>
    <VDropdown v-if="getAllowedBlockList?.length">
        <Button variant="default" sm>
            <i class="fas fa-sign-in-alt"></i>
        </Button>
        <template #popper>
            <div v-for="name in getAllowedBlockList">
                <BlockAdd :blockName="name" :block="block"></BlockAdd>
            </div>
        </template>
    </VDropdown>
    <!-- <VDropdown v-if="getParentBlockList?.length">
        <Button variant="default" sm>
            <i class="fas fa-sign-out-alt"></i>
        </Button>
        <template #popper>
            <div v-for="name in getParentBlockList">
                <BlockAddAfter :blockName="name" :block="block"></BlockAddAfter>
            </div>
        </template>
    </VDropdown> -->
</template>

<style lang="scss">
.v-popper {
    display: inline-block;
}
</style>
