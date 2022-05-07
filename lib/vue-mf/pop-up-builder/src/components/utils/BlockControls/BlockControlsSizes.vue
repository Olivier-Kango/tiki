<script>
export default {
    name: 'BlockControlsSizing'
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
    }
})

const { setStyles, removeStyles } = useState(props.block.id)

const styles = ref({
    width: props.block.styles?.width,
    height: props.block.styles?.height
})

watch(() => props.block.styles, (val, oldVal) => {
    let { width, height } = val
    styles.value = { width, height }
})

const handleUpdate = (field, value) => {
    let unit = '%'
    if (field === 'height') unit = 'px'
    styles.value[field] = value + unit
    setStyles(styles.value)
}

const handleRemoveStyles = ($event, styles) => removeStyles(styles)

function splitUnit(v) {
    if (typeof v === 'string' && v !== '') {
        var split = v.match(/^([-.\d]+(?:\.\d+)?)(.*)$/)
        return { 'value': split[1].trim(),  'unit': split[2].trim() }
    } else {
        return { 'value': v, 'unit': '' }
    }
}

</script>

<template>
    <div class="block-editor-controls-spacing">
        <div>
            <div class="mb-3">
                <h6>width</h6>
                <div class="d-flex">
                    <VueSlider class="flex-grow-1" :value="splitUnit(styles.width).value" @dragging="(value) => handleUpdate('width', value)"/>
                    <Button variant="default" sm @click="handleRemoveStyles($event, ['width'])">
                        <i class="fas fa-eraser"></i>
                    </Button>
                </div>
            </div>
            <div class="mb-3">
                <h6>height</h6>
                <div class="d-flex">
                    <VueSlider class="flex-grow-1" :value="splitUnit(styles.height).value" :min="0" :max="1000" @dragging="(value) => handleUpdate('height', value)"/>
                    <Button variant="default" sm @click="handleRemoveStyles($event, ['height'])">
                        <i class="fas fa-eraser"></i>
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
