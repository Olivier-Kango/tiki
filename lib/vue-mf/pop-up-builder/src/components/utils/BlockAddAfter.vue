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
    store.dispatch('addBlockAfter', {
        block: store.getters.getBlockType(props.blockName),
        inBlock: props.block
    })
}
</script>

<template>
    <Button variant="default" sm @click="addBlock">
        {{blockName}}
    </Button>
</template>
