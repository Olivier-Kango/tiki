<script>
export default {
    name: 'Button'
}
</script>
<script setup>
import { ref } from 'vue'
import { Button } from '@vue-mf/styleguide'

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
    <div :class="props.block.classes" class="core-button-container" :style="props.block.styles">
        <Button v-html="block.attributes.content"></Button>
    </div>
</template>
