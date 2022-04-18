<script>
export default {
    name: 'BlocksNavbar'
}
</script>

<script setup>
import { computed } from 'vue' 
import { Button, ToggleButton } from '@vue-mf/styleguide'
import BlockAdd from './BlockAdd.vue'
import store from '../../store';

const props = defineProps({
    node: {
        type: Object,
        default: () => {}
    }
});

const rootBlock = computed(() => store.getters.getRootBlock)

const clearLocalStorage = () => {
    let response = confirm('Clear builder data?')
    if (response) localStorage.removeItem('tiki-pop-up-blocks')
}

const handleSwitchMode = (checked) => {
    checked ? store.dispatch('setMode', 'prod') : store.dispatch('setMode', 'edit')
}
</script>

<template>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="#">Tiki Builder</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
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
            <div class="d-flex align-items-center ml-auto">
                <div style="width: 50px"><ToggleButton label="Preview" @update:checked="handleSwitchMode" /></div>
                <Button variant="default" sm @click="clearLocalStorage">
                    Clear localStorage builder data?
                </Button>
            </div>
        </div>
    </nav>
</template>