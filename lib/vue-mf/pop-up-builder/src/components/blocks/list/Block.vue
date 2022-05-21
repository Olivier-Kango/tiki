<script>
export default {
    name: 'List'
}
</script>

<script setup>
import { computed } from 'vue'
import store from '../../../store'
import BlockBuilder from '../../utils/BlockBuilder.vue'

const props = defineProps({
    block: {
        type: Object,
        default() {
            return {}
        }
    }
})

const getListData = computed(() => {
    let field = props.block.attributes.field
    return store.getters[`module_${field}/get_${field}`]
})
</script>

<template>
    <BlockBuilder
        v-for="(data, index) in getListData"
        :key="'block-list-item-' + index"
        :id="block.id"
        :context="data"
        :edit="false"
    />
</template>
