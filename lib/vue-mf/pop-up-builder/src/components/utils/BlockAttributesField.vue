<script>
export default {
    name: 'BlockAttributesField'
}
</script>

<script setup>
import { ref } from 'vue'
import { useState } from '../hooks/useState'

const props = defineProps({
    block: {
        type: Object,
        default() {
            return {}
        }
    },
    field: String,
    title: String
})

const fieldValue = ref(props.block.attributes[props.field])

const { setAttributes } = useState(props.block.id)

const handleInput = (e) => {
    fieldValue.value = e.target.value
    setAttributes({ [props.field]: fieldValue.value })
}
</script>

<template>
    <div class="mb-3">
        <label for="exampleFormControlInput1" class="form-label">{{ title }}:</label>
        <input
            :value="fieldValue"
            type="text"
            class="form-control"
            id="exampleFormControlInput1"
            placeholder="http://example.com"
            @input="handleInput"
        >
    </div>
</template>
