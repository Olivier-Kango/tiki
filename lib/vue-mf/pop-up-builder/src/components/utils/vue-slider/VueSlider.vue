<script>
export default {
    name: 'VueSlider'
}
</script>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
    label: {
        type: Object,
        default: () => {}
    },
    value: [String, Number],
})

const emit = defineEmits(['dragging'])
const inputValue = ref(props.value)

const handleInput = (e) => {
    emit('dragging', parseInt(e.target.value))
}

</script>

<template>
    <div>
        <label v-if="label" for="customRange1" class="form-label">{{ label }}</label>
        <div class="d-flex">
            <input v-model="inputValue" type="range" class="form-range" id="customRange1" min="0" max="100" step="1" @input="handleInput">
            <span>{{ inputValue }}</span>
        </div>
    </div>
</template>
