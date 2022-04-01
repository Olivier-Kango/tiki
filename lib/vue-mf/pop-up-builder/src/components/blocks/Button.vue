<script>
export default {
    name: 'Button'
}
</script>
<script setup>
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
    <Button :class="block.classes" :style="block.styles">
        {{block.textContent}}
        <slot/>
    </Button>
</template>

<style lang="scss">

</style>
