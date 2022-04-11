<script>
export default {
    name: 'BlockControls'
}
</script>

<script setup>
import { Button, ButtonGroup, ButtonToolbar } from '@vue-mf/styleguide'
import BlockList from './BlockList.vue'
import store from '../../store';

const props = defineProps({
    block: {
        type: Object,
        default: () => {}
    }
});

const emit = defineEmits(['edit'])

const removeBlock = (e) => {
    store.dispatch('removeBlock', props.block)
}

const showBlockSettings = (e) => {
    emit('edit')
}
</script>

<template>
    <ButtonToolbar>
        <BlockList :block="block"></BlockList>
        <ButtonGroup>
            <Button variant="default" sm><i class="fas fa-grip-vertical"></i></Button>
            <Button variant="default" sm @click="showBlockSettings"><i class="fas fa-edit"></i></Button>
            <Button variant="default" sm @click="removeBlock"><i class="fas fa-trash"></i></Button>
            <Button variant="default" sm @click="moveBack"><i class="fas fa-chevron-left"></i></Button>
            <Button variant="default" sm @click="moveForth"><i class="fas fa-chevron-right"></i></Button>
        </ButtonGroup>
    </ButtonToolbar>
</template>
