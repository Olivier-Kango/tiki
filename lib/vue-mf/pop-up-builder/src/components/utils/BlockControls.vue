<script>
export default {
    name: 'BlockControls'
}
</script>

<script setup>
import { Button } from '@vue-mf/styleguide'
import BlockList from './BlockList.vue'
import store from '../../store';

const props = defineProps({
    node: {
        type: Object,
        default: () => {}
    }
});

const removeBlock = () => {
    store.dispatch('removeBlock', props.node)
    localStorage.setItem('tiki-pop-up-blocks', JSON.stringify(store.getters.getBlocks))
}
</script>

<template>
    <div class="d-flex align-items-center">
        <small class="mr-2"><b>{{node.data.name}}</b></small>
        <BlockList :block="node.data"></BlockList>
        <Button variant="default" sm><i class="fas fa-grip-vertical"></i></Button>
        <Button variant="default" sm><i class="fas fa-edit"></i></Button>
        <Button variant="default" sm @click="removeBlock"><i class="fas fa-trash"></i></Button>
        <Button variant="default" sm><i class="fas fa-chevron-left"></i></Button>
        <Button variant="default" sm><i class="fas fa-chevron-right"></i></Button>
    </div>
</template>
