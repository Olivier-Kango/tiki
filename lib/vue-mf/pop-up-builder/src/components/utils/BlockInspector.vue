<script>
export default {
    name: 'BlockInspector'
}
</script>

<script setup>
import BlockInspectorContext from './BlockInspectorContext.vue'

const props = defineProps({
    block: {
        type: Object,
        default() {
            return {}
        }
    },
    context: {
        type: Object,
        default() {
            return {}
        }
    }
})

</script>

<template>
    <div class="block-inspector">
        <BlockInspectorContext :block="block" :context="context"/>
    </div>
</template>
