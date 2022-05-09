<script>
export default {
    name: 'BlockControlsSpacing'
}
</script>

<script setup>
import { ref, watch } from 'vue'
import { Button } from '@vue-mf/styleguide'
import VueSlider from '../vue-slider/VueSlider.vue'
import { useState } from '../../hooks/useState.js'

const props = defineProps({
    block: {
        type: Object,
        default: () => {}
    },
    type: String
})

const { setStyles, removeStyles } = useState(props.block.id)

const styles = ref({
    [`${props.type}Left`]: props.block.styles && props.block.styles[`${props.type}Left`],
    [`${props.type}Right`]: props.block.styles && props.block.styles[`${props.type}Right`],
    [`${props.type}Top`]: props.block.styles && props.block.styles[`${props.type}Top`],
    [`${props.type}Bottom`]: props.block.styles && props.block.styles[`${props.type}Bottom`],
})

watch(() => props.block.styles, (val, oldVal) => {
    let newStyles = { 
        [`${props.type}Left`]: null,
        [`${props.type}Right`]: null,
        [`${props.type}Top`]: null,
        [`${props.type}Bottom`]: null,
    }
    Object.getOwnPropertyNames(val).forEach(name => {
        if (name === `${props.type}Left` || name === `${props.type}Right` || name === `${props.type}Top` || name === `${props.type}Bottom`) {
            newStyles[name] = val[name]
        }
    })
    styles.value = newStyles
})

const handleUpdate = (field, value) => {
    styles.value[field] = value + 'px'
    let newStyles = { ...styles.value }
    Object.getOwnPropertyNames(newStyles).forEach(key => {
        if (!newStyles[key]) delete newStyles[key]
    })
    setStyles(newStyles)
}

const handleRemoveStyles = ($event, styles) => removeStyles(styles)

function splitUnit(v) {
    if (typeof v === 'string' && v !== '') {
        let split = v.match(/^([-.\d]+(?:\.\d+)?)(.*)$/)
        return { 'value': split[1].trim(), 'unit': split[2].trim() }
    } else {
        return { 'value': v, 'unit': '' }
    }
}

</script>

<template>
    <div class="block-editor-controls-spacing">
        <div v-for="(style, key) in styles" :key="key" class="mb-3">
            <h6>{{ key }}</h6>
            <div class="d-flex">
                <VueSlider class="flex-grow-1" :value="splitUnit(style).value" @dragging="(value) => handleUpdate(key, value)"/>
                <Button variant="default" sm @click="handleRemoveStyles($event, [key])">
                    <i class="fas fa-eraser"></i>
                </Button>
            </div>
        </div>
    </div>
</template>
