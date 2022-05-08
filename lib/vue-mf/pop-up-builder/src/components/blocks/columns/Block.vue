<script>
export default {
    name: 'Columns'
}
</script>
<script setup>
import BlockBuilder from '../../utils/BlockBuilder.vue'

defineProps({
    block: {
        type: Object,
        default() {
            return {}
        }
    }
});

</script>

<template>
    <slot />
</template>
