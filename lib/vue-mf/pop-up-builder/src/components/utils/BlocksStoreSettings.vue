<script>
export default {
    name: 'BlocksStoreSettings'
}
</script>

<script setup>
import { ref, computed } from 'vue' 
import { Button, ToggleButton } from '@vue-mf/styleguide'
import BlockAdd from './BlockAdd.vue'
import CustomModal from './CustomModal.vue'
import store from '../../store';

const props = defineProps({
    node: {
        type: Object,
        default: () => {}
    }
});

const storeOptions = ref([
    {
        url: 'https://fakestoreapi.com/products',
        field: 'products'
    },
    {
        url: 'https://fakestoreapi.com/products/categories',
        field: 'categories'
    }
])

const handleSaveOptions = () => {
    localStorage.setItem('tiki-pop-up-blocks-store', JSON.stringify(storeOptions.value))
}
</script>

<template>
    <div>
        <div v-for="storeOption in storeOptions">
            <div class="input-group mb-3">
                <div class="input-group-prepend">
                    <span class="input-group-text" id="inputGroup-sizing-default">url</span>
                </div>
                <input type="text" :value="storeOption.url" class="form-control" aria-label="Default" aria-describedby="inputGroup-sizing-default">
                <input type="text" :value="storeOption.field" class="form-control">
            </div>
        </div>
        <Button variant="primary" @click="handleSaveOptions">
            Save
        </Button>
    </div>
</template>