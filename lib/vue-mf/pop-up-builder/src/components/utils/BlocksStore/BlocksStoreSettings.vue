<script>
export default {
    name: 'BlocksStoreSettings'
}
</script>

<script setup>
import { ref, computed } from 'vue' 
import { Button } from '@vue-mf/styleguide'
import BlocksStoreField from './BlocksStoreField.vue'
import BlocksStoreView from './BlocksStoreView.vue'
import store from '../../../store'
import { registerStoreModule, unregisterStoreModule } from '../../../store/helpers'

const showAddField = ref(false)

const storeOptions = computed(() => store.getters.getAPIoptions)
const getAPIfields = computed(() => store.getters.getAPIfields)

const handleUpdateOption = (option, index) => {
    let oldOption = storeOptions.value[index]
    let { url, field } = option

    if (oldOption.field !== field) {
        unregisterStoreModule([oldOption.field])
        registerStoreModule([{ url, field }], store)
        if (field) store.dispatch(`module_${field}/add_${field}`).catch(err => console.log(err))
    }
    store.dispatch('updateAPIoption', { option, index })
    saveToLocalStorage()
}

const handleRemoveAPIoption = (index) => {
    let { field } = storeOptions.value[index]
    store.dispatch('removeAPIoption', index)
    saveToLocalStorage()
    unregisterStoreModule([field])
}

const handleSaveOption = (option) => {
    showAddField.value = false
    saveAPIOption(option)
    store.dispatch('addAPIoption', option)
    saveToLocalStorage()
}

function saveAPIOption(option) {
    let { url, field } = option
    if (!field && !url) return
    if (store.hasModule(`module_${field}`)) return

    registerStoreModule([option], store)
    if (field) store.dispatch(`module_${field}/add_${field}`).catch(err => console.log(err))
}

function saveToLocalStorage() {
    if (!Array.isArray(storeOptions.value)) return
    let filteredStoreOptions = storeOptions.value.filter(option => option.url && option.field)
    localStorage.setItem('tiki-pop-up-blocks-store', JSON.stringify(filteredStoreOptions))
}
</script>

<template>
    <div>
        <BlocksStoreField
            v-for="(option, index) in storeOptions" :key="'api-option-' + option"
            :option="option"
            @onSave="(opt) => handleUpdateOption(opt, index)"
            @onRemove="handleRemoveAPIoption(index)"
        />

        <BlocksStoreField
            v-if="showAddField"
            hideRemove
            @onSave="handleSaveOption"
        />

        <Button class="mr-2" variant="primary" @click="() => showAddField = !showAddField">
            <i v-if="!showAddField" class="fas fa-plus"></i>
            <i v-else class="fas fa-minus"></i>
        </Button>

        <BlocksStoreView
            v-for="field in getAPIfields"
            :key="'view-field-' + field"
            :field="field"
        />
    </div>
</template>