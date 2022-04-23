<script>
export default {
    name: 'BlockInspector'
}
</script>

<script setup>
import { computed, watch } from 'vue'
import store from '../../store'

const props = defineProps({
    block: {
        type: Object,
        default() {
            return {}
        }
    },
    fields: {
        type: Array,
        default() {
            return []
        }
    }
})

// const getFields = computed(() => 
//     store.getters['store-module/get_fields']
// )

const handleSelected = (selected) => {
    store.dispatch('updateBlock', {
        block: props.block,
        field: 'attributes',
        value: {...props.block.attributes, field: selected }
    })
}
</script>

<template>
    <div class="block-inspector">
        <slot/>
        <VueSelect :options="fields" @selected="handleSelected"></VueSelect>
    </div>
</template>
