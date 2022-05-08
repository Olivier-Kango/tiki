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
    <Button v-html="block.attributes.content"></Button>
</template>
