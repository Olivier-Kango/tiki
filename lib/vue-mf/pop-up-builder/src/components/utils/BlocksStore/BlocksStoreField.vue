<script>
export default {
    name: 'BlocksStoreField'
}
</script>

<script setup>
import { ref, unref, watch } from 'vue'
import { Button } from '@vue-mf/styleguide'

const props = defineProps({
    option: {
        type: Object,
        default: () => ({ url: '', field: '' })
    },
    hideRemove: Boolean
})
const emit = defineEmits(['onSave', 'onRemove', 'onInput'])

const localOption = ref({ ...props.option })

watch(() => props.option, (val, oldVal) => {
    localOption.value = { ...val }
})

const handleSave = () => emit('onSave', unref(localOption.value))
const handleRemove = () => emit('onRemove', unref(localOption.value))
const handleInput = ($event, field) => {
    localOption.value[field] = $event.target.value
    emit('onInput', unref(localOption.value))
}
</script>

<template>
    <div class="input-group mb-3">
        <div class="input-group-prepend">
            <span class="input-group-text" id="inputGroup-sizing-default">url</span>
        </div>
        <input type="url" name="url" :value="localOption.url" @input="handleInput($event, 'url')" class="form-control" placeholder="Enter api url..." aria-label="Default" aria-describedby="inputGroup-sizing-default">
        <input type="text" name="field" :value="localOption.field" @input="handleInput($event, 'field')" class="form-control" placeholder="Enter field name...">
        <Button v-if="!hideRemove" variant="default" @click="handleRemove">
            <i class="fas fa-trash"></i>
        </Button>
        <Button v-if="localOption.url && localOption.field" variant="default" @click="handleSave">
            <i class="fas fa-save"></i>
        </Button>
    </div>
</template>
