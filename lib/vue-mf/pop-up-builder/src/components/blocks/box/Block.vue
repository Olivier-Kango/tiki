<script>
export default {
    name: 'Container'
}
</script>
<script setup>
import BlockBuilder from '../../utils/BlockBuilder.vue'

const props = defineProps({
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
