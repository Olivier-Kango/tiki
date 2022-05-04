<script>
export default {
    name: 'BlockControls'
}
</script>

<script setup>
import { computed } from 'vue'
import { Button, ButtonGroup, ButtonToolbar } from '@vue-mf/styleguide'
import BlockDraggable from './BlockDraggable.vue'
import BlockList from './BlockList.vue'
import store from '../../store';

const props = defineProps({
    block: {
        type: Object,
        default: () => {}
    }
})

const emit = defineEmits(['edit'])

const getBlockType = computed(() => (name) => store.getters.getBlockType(name))

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
        <!-- <span class="me-2">{{ getBlockType(block.name).title }}</span> -->
        <BlockList :block="block"></BlockList>
        <ButtonGroup>
            <BlockDraggable :block="block">
                <Button class="drag-handle-block" variant="default" sm><i class="fas fa-grip-vertical"></i></Button>
                <template #draggable>
                    <div class="draggable-template">
                        <span class="me-2">{{ block.name }}</span>
                        <span>
                            <i class="fas fa-grip-vertical"></i>
                        </span>
                    </div>
                </template>
            </BlockDraggable>
            <Button variant="default" sm @click="moveBackward"><i class="fas fa-chevron-left"></i></Button>
            <Button variant="default" sm @click="moveForward"><i class="fas fa-chevron-right"></i></Button>
            <Button variant="default" sm @click="showBlockSettings"><i class="fas fa-edit"></i></Button>
            <Button variant="default" sm @click="deleteBlock"><i class="fas fa-trash"></i></Button>
        </ButtonGroup>
    </ButtonToolbar>
</template>

<style lang="scss" scoped>
    .draggable-template {
        display: flex;
        align-items: center;
        padding: 5px 10px;
        background-color: lightgrey;
        border-radius: 4px;
    }
</style>
