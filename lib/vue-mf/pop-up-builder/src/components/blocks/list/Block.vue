<script>
export default {
    name: 'List'
}
</script>

<script setup>
import { computed } from 'vue'
import { useState } from '../../hooks/useState'
import store from '../../../store'
import BlockInnerBlocks from '../../utils/BlockInnerBlocks.vue'

const props = defineProps({
    block: {
        type: Object,
        default() {
            return {}
        }
    }
});

const { cloneBlockBulk } = useState(props.id)

const cloneBlockTemplates = computed(() => {
    let field = props.block.attributes.field
    return store.getters[`module_${field}/get_${field}`]?.map(item => {
        return { ...cloneBlockBulk(props.block.innerBlocks), context: item }
    })
})
</script>

<template>
    <div v-if="!cloneBlockTemplates" class="d-flex justify-content-center align-items-center" :style="{'height': '200px', 'font-size': '2rem'}">
        <i class="fas fa-spinner fa-spin"></i>
    </div>
    <div v-if="cloneBlockTemplates">
        <div
            v-for="({ innerBlocks, blocks, context }, index) in cloneBlockTemplates"
            :key="'block-clone-' + index"
        >
            <BlockInnerBlocks
                :ids="innerBlocks"
                :blocks="blocks"
                :context="context"
            />
        </div>
    </div>
</template>
