<script>
export default {
    name: 'BlocksStoreSettings'
}
</script>

<script setup>
import { ref, computed, onMounted } from 'vue' 
import { Button, ToggleButton } from '@vue-mf/styleguide'
import BlockAdd from './BlockAdd.vue'
import CustomModal from './CustomModal.vue'
import store from '../../store'
import { registerStoreModule } from '../../store/helpers'

const props = defineProps({
    node: {
        type: Object,
        default: () => {}
    }
});

const storeOptions = computed(() => store.getters.getAPIoptions)

const handleSaveOptions = (index) => {
    let field = storeOptions.value[index].field

    registerStoreModule([storeOptions.value[index]], store)
    store.dispatch(`module_${field}/add_${field}`)
    localStorage.setItem('tiki-pop-up-blocks-store', JSON.stringify(storeOptions.value))
}

const handleAddAPIoption = () => {
    store.dispatch('addAPIoption', {
        url: '',
        field: ''
    })
}

const handleUpdateAPIoption = ({ value, field, index }) => {
    store.dispatch('updateAPIoption', { value, field, index })
}

const handleRemoveAPIoption = (index) => {
    store.dispatch('removeAPIoption', index)
}

const handleInput = (event, { field, index }) => {
    handleUpdateAPIoption({ value: event.target.value, field, index })
}
</script>

<template>
    <div>
        <div v-for="(storeOption, index) in storeOptions">
            <div class="input-group mb-3">
                <div class="input-group-prepend">
                    <span class="input-group-text" id="inputGroup-sizing-default">url</span>
                </div>
                <input type="url" name="url" :value="storeOption.url" @input="handleInput($event, {field: 'url', index: index})" class="form-control" placeholder="Enter api url..." aria-label="Default" aria-describedby="inputGroup-sizing-default">
                <input type="text" name="field" :value="storeOption.field" @input="handleInput($event, {field: 'field', index: index})" class="form-control" placeholder="Enter field name...">
                <Button variant="default" @click="handleRemoveAPIoption(index)">
                    <i class="fas fa-trash"></i>
                </Button>
                <Button variant="default" @click="handleSaveOptions(index)">
                    <i class="fas fa-save"></i>
                </Button>
            </div>
        </div>
        <Button class="mr-2" variant="primary" @click="handleAddAPIoption">
            <i class="fas fa-plus"></i>
        </Button>
        <div v-for="field in store.getters.getAPIfields">
            <b><u>{{ field }}</u></b>
            <pre>{{ JSON.stringify(store.getters[`module_${field}/get_${field}`][0], undefined, 4) }}</pre>
        </div>
    </div>
</template>