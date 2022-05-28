<script>
export default {
    name: 'Button'
}
</script>
<script setup>
import { ref, inject } from 'vue'
import { Button } from '@vue-mf/styleguide'

const props = defineProps({
    block: {
        type: Object,
        default() {
            return {}
        }
    }
})

const context = inject('context')

const handleClick = () => {
    const blockContextActions = props.block.attributes.context?.actions
    if (blockContextActions) {
        blockContextActions.forEach(action => context.actions[action.name]() )
    }
}
</script>

<template>
    <Button v-html="block.attributes.content" @click="handleClick"></Button>
</template>
