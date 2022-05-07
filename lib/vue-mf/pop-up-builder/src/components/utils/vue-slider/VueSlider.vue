<script>
export default {
    name: 'VueSlider'
}
</script>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
    label: {
        type: Object,
        default: () => {}
    },
    value: [String, Number],
    min: Number,
    max: Number
})

const emit = defineEmits(['dragging'])
const inputValue = ref(props.value)

watch(() => props.value, (val, oldVal) => {
    inputValue.value = val
})

const handleInput = (e) => {
    emit('dragging', parseInt(e.target.value))
}

</script>

<template>
    <div>
        <label v-if="label" for="customRange1" class="form-label">{{ label }}</label>
        <div class="d-flex">
            <input v-model="inputValue" type="range" class="form-range" id="customRange1" :min="min || 0" :max="max || 100" step="1" @input="handleInput">
            <span class="ms-2">{{ inputValue }}</span>
        </div>
    </div>
</template>
