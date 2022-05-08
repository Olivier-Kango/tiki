<script>
export default {
    name: 'Paragraph'
}
</script>

<script setup>
import { computed } from 'vue'
import _get from 'lodash/get'

const props = defineProps({
    block: {
        type: Object,
        default() {
            return {}
        }
    },
    context: [Object, Array]
})

const getContext = computed(() => _get(props.context, props.block.attributes.field))
</script>

<template>
    <div v-html="getContext || props.block.attributes?.content"></div>
</template>
