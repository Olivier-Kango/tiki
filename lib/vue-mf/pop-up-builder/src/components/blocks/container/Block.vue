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
    <div :class="block.classes" :style="block.styles">
        <slot />
    </div>
</template>
