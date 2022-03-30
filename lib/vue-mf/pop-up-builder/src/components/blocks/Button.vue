<script>
export default {
    name: 'Button'
}
</script>
<script setup>
import { Button } from '@vue-mf/styleguide'

const props = defineProps({
    styles: {
        type: Object,
        default() {
            return {}
        }
    },
    classes: {
        type: Array,
        default() {
            return []
        }
    },
    text: {
        type: String
    }
});

</script>

<template>
    <Button :class="classes" :style="styles">
        {{text}}
        <slot/>
    </Button>
</template>

<style lang="scss">

</style>
