<script>
export default {
    name: 'BlocksNavbar'
}
</script>

<script setup>
import { ref, computed } from 'vue' 
import { Button, ToggleButton } from '@vue-mf/styleguide'
import BlockAdd from './BlockAdd.vue'
import CustomModal from './CustomModal.vue'
import BlocksStoreSettings from './BlocksStoreSettings.vue'
import store from '../../store';

const props = defineProps({
    node: {
        type: Object,
        default: () => {}
    }
});

const emit = defineEmits(['showInspector'])
const showModal = ref(null)
const isPreview = ref(null)

const rootBlock = computed(() => store.getters.getRootBlock)

const clearLocalStorage = () => {
    let response = confirm('Clear builder data?')
    if (response) {
        localStorage.removeItem('tiki-pop-up-blocks')
        localStorage.removeItem('tiki-pop-up-blocks-store')
    }
}

const handleSwitchMode = (checked) => {
    isPreview.value = checked
    checked ? store.dispatch('setMode', 'prod') : store.dispatch('setMode', 'edit')
}

const handleSwitchInspector = (checked) => {
    emit('showInspector', checked)
}

const handleOpenModal = () => {
    showModal.value = true
}

const handleCloseModal = () => {
    showModal.value = false
}

const handlePrev = () => {
    
}

const handleNext = () => {
    
}
</script>

<template>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Tiki Builder</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <VDropdown>
                    <Button variant="default" sm>
                        <i class="fas fa-sign-out-alt"></i>
                    </Button>
                    <template #popper>
                        <div v-for="name in store.getters.getBlockList">
                            <BlockAdd :blockName="name" :block="rootBlock"></BlockAdd>
                        </div>
                    </template>
                </VDropdown>
                <Button variant="default" sm @click="handleOpenModal">
                    <i class="fas fa-database"></i>
                </Button>
                <Button variant="default" sm @click="handlePrev">
                    <i class="fas fa-arrow-left"></i>
                </Button>
                <Button variant="default" sm @click="handleNext">
                    <i class="fas fa-arrow-right"></i>
                </Button>
                <span class="me-2">Inspector</span>
                <div style="width: 50px"><ToggleButton checked label="Preview" @update:checked="handleSwitchInspector" /></div>


                <div class="d-flex align-items-center ms-auto">
                    <span class="me-2" :class="{'is-preview': isPreview}">Preview</span>
                    <div style="width: 50px"><ToggleButton label="Preview" @update:checked="handleSwitchMode" /></div>
                    <Button variant="default" sm @click="clearLocalStorage">
                        Clear localStorage builder data?
                    </Button>
                </div>
            </div>
        </div>

        <CustomModal
            :show="showModal"
            @close="handleCloseModal"
            :drag="true"
            :resize="true"
            :drag-selector="'.modal-drag'"
            title="APIs"
            :clickToClose="false"
        >
            <BlocksStoreSettings></BlocksStoreSettings>
        </CustomModal>
    </nav>
</template>

<style lang="scss" scoped>
    .is-preview {
        font-weight: bold;
        color: #4fd1c5;
    }
</style>
