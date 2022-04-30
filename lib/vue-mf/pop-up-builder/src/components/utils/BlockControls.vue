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

const moveBackward = (e) => {
    store.dispatch('moveBlockBackward', props.block.id)
}

const moveForward = (e) => {
    store.dispatch('moveBlockForward', props.block.id)
}

const deleteBlock = (e) => {
    store.dispatch('deleteBlock', props.block.id)
}

const showBlockSettings = (e) => {
    emit('edit')
}
</script>

<template>
    <ButtonToolbar>
        <BlockList :block="block"></BlockList>
        <ButtonGroup>
            <Button class="drag-handle-block" variant="default" sm><i class="fas fa-grip-vertical"></i></Button>
            <Button variant="default" sm @click="showBlockSettings"><i class="fas fa-edit"></i></Button>
            <Button variant="default" sm @click="deleteBlock"><i class="fas fa-trash"></i></Button>
            <Button variant="default" sm @click="moveBackward"><i class="fas fa-chevron-left"></i></Button>
            <Button variant="default" sm @click="moveForward"><i class="fas fa-chevron-right"></i></Button>
        </ButtonGroup>
    </ButtonToolbar>
</template>
