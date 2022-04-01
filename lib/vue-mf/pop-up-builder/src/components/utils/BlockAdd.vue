<script>
export default {
    name: 'BlockAdd'
}
</script>

<script setup>
import { Button } from '@vue-mf/styleguide'
import { v4 as uuidv4 } from 'uuid';
import store from '../../store'

const props = defineProps({
    blockName: String,
    block: {
        type: Object,
        default: () => {}
    }
});

const addBlock = () => {
    store.dispatch('addBlock', {
        id: uuidv4(),
        parent_id: props.block.id,
        name: props.blockName,
        classes: [],
        styles: {}
    })
    localStorage.setItem('tiki-pop-up-blocks', JSON.stringify(store.getters.getBlocks))
}
</script>

<template>
    <Button variant="default" sm @click="addBlock">
        {{blockName}}
    </Button>
</template>
