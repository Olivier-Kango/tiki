<script>
export default {
    name: 'BlockAdd'
}
</script>

<script setup>
import { Button } from '@vue-mf/styleguide'
import store from '../../store'

const props = defineProps({
    blockName: String,
    block: {
        type: Object,
        default: () => {}
    }
});

const insertNewBlockAfter = () => {
    store.dispatch('insertNewBlockAfter', {
        name: props.blockName,
        targetId: props.block.id
    })
}
</script>

<template>
    <Button variant="default" sm @click="insertNewBlockAfter">
        {{blockName}}
    </Button>
</template>
