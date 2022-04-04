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
    parentId: String
});

const addBlock = () => {
    store.dispatch('addBlock', {
        id: uuidv4(),
        parent_id: props.parentId,
        name: props.blockName
    })
}
</script>

<template>
    <Button variant="default" sm @click.stop="addBlock">
        {{blockName}}
    </Button>
</template>
