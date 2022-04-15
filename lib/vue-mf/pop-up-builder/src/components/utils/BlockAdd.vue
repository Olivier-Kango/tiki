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

const addBlock = () => {
    store.dispatch('insertBlock', {
        name: props.blockName,
        targetId: props.block.id
    })
}
</script>

<template>
    <Button variant="default" sm @click.stop="addBlock">
        {{blockName}}
    </Button>
</template>
