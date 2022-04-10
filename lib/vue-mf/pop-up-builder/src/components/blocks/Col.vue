<script>
export default {
    name: 'Col'
}
</script>
<script setup>
import { computed } from 'vue'
import { useState } from '../hooks/useState'

const props = defineProps({
    block: {
        type: Object,
        default() {
            return {}
        }
    }
});

const { isSelected, isHighlighted } = useState(props.block)

const classes = computed(() => 
    props.block.classes.concat([
        {'is-highlighted': isHighlighted.value}
    ])
)
</script>

<template>
    <div :class="classes" class="col" :style="block.styles">
        <slot/>
    </div>
</template>

<style lang="scss">
</style>
