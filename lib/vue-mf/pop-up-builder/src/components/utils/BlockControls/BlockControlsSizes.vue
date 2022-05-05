<script>
export default {
    name: 'BlockControlsSizing'
}
</script>

<script setup>
import { ref, computed } from 'vue'
import VueSlider from '../vue-slider/VueSlider.vue'
import { useState } from '../../hooks/useState.js'

const props = defineProps({
    block: {
        type: Object,
        default: () => {}
    }
})

const { setStyles } = useState(props.block.id)

// const getValue = computed(() => (value) => splitUnit(value).value)
// const getUnit = computed(() => (value) => splitUnit(value).unit)

const styles = ref({
    width: props.block.styles?.width,
    height: props.block.styles?.height
})

const handleUpdate = (field, value) => {
    styles.value[field] = value + '%'
    setStyles(styles.value)
}

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
            <div class="mb-3 w-25">
                <h6>width</h6>
                <VueSlider :value="splitUnit(styles.width).value" @dragging="(value) => handleUpdate('width', value)"/>
            </div>
            <div class="mb-3 w-25">
                <h6>height</h6>
                <VueSlider :value="splitUnit(styles.height).value" @dragging="(value) => handleUpdate('height', value)"/>
            </div>
        </div>
    </div>
</template>
