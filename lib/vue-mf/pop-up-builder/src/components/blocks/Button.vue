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

const { classes, styles } = ref(props.block)
const { textContent } = ref(props.block.attributes)

</script>

<template>
    <Button :class="classes" :style="styles">
        {{textContent}}
        <slot/>
    </Button>
</template>

<style lang="scss">

</style>
