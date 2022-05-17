<script>
export default {
    name: 'BlockControls'
}
</script>

<script setup>
import { ref, computed } from 'vue'
import { Button, ButtonGroup, ButtonToolbar, Modal } from '@vue-mf/styleguide'
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

const showModal = ref(false)
const patternName = ref('')

const moveBackward = (e) => {
    store.dispatch('moveBlockBackward', props.block.id)
}

const moveForward = (e) => {
    store.dispatch('moveBlockForward', props.block.id)
}

const deleteBlock = (e) => {
    store.dispatch('deleteBlock', props.block.id)
}

const handleSave = (e) => {
    showModal.value = false
    store.dispatch('addPattern', { blockId: props.block.id, patternName: patternName.value })
}

const handleShowModal = (e) => showModal.value = true
const handleHideModal = (e) => showModal.value = false

const showBlockSettings = (e) => {
    emit('edit')
}
</script>

<template>
    <ButtonToolbar>
        <!-- <span class="me-2">{{ getBlockType(block.name).title }}</span> -->
        <BlockList :block="block"></BlockList>
        <ButtonGroup>
            <BlockDraggable :block="block" drag>
                <Button :id="'block-drag-button-' + block.id" class="drag-handle-block" variant="default" sm><i class="fas fa-grip-vertical"></i></Button>

                <template #draggable>
                    <div class="draggable-template shadow-sm">
                        <span class="me-2">{{ block.name }}</span>
                        <span>
                            <i class="fas fa-cubes"></i>
                        </span>
                    </div>
                </template>
            </BlockDraggable>
            <Button variant="default" sm @click="moveBackward"><i class="fas fa-chevron-left"></i></Button>
            <Button variant="default" sm @click="moveForward"><i class="fas fa-chevron-right"></i></Button>
            <Button variant="default" sm @click="showBlockSettings"><i class="fas fa-cog"></i></Button>
            <Button variant="default" sm @click="deleteBlock"><i class="fas fa-trash"></i></Button>
            <Button variant="default" sm @click="handleShowModal"><i class="fas fa-shapes"></i></Button>
        </ButtonGroup>

        <Modal v-show="showModal" :show="showModal" title="Create Reusable block" @hidden="handleHideModal">
            <template #body>
                <input v-model="patternName" type="text" class="form-control" placeholder="Name" aria-label="Username" aria-describedby="basic-addon1">
            </template>
            <template #footer>
                <Button variant="default" sm @click="handleHideModal">Cancel</Button>
                <Button variant="primary" sm @click="handleSave">Save</Button>
            </template>
        </Modal>
    </ButtonToolbar>
</template>

<style lang="scss" scoped>
    .draggable-template {
        display: flex;
        align-items: center;
        padding: 10px 15px;
        background-color: #000000;
        color: white;
        border-radius: 2px;
    }
</style>
