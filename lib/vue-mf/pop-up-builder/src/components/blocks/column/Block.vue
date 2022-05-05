<script>
export default {
    name: 'Column'
}
</script>
<script setup>
import { ref, computed } from 'vue'
import { useState } from '../../hooks/useState'

const props = defineProps({
    block: {
        type: Object,
        default() {
            return {}
        }
    }
});

const blockData = ref({
    name: 'core/column',
    title: 'Column',
    description: 'A single column within a columns block.',
    parent: ['core/columns'],
    attributes: {
        allowedBlocks: []
    }
})
const { isSelected, isHighlighted } = useState(props.block)

const classes = computed(() => 
    props.block.classes && [props.block.classes].concat([
        {'is-highlighted': isHighlighted.value}
    ])
)
</script>

<template>
    <div :class="classes" class="col d-flex" :style="block.styles">
        <slot />
    </div>
</template>
