<script>
export default {
    name: 'Block'
}
</script>

<script setup>
import { computed } from 'vue'
import store from '../../store'

const props = defineProps({
    tag: String,
    block: {
        type: Object,
        default: () => {}
    },
    context: {
        type: Object,
        default: () => null
    }
})
</script>

<template>
    <component
        :is="tag"
        class="block-item"
        :id="block.id"
        :data-id="block.id"
        :block="block"
        :context="context"
    />
</template>
