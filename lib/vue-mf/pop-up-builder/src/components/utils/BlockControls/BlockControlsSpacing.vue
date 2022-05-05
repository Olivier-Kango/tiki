<script>
export default {
    name: 'BlockControlsSpacing'
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
    paddingLeft: props.block.styles?.paddingLeft,
    paddingRight: props.block.styles?.paddingRight,
    paddingTop: props.block.styles?.paddingTop,
    paddingBottom: props.block.styles?.paddingBottom,
})

const handleUpdate = (field, value) => {
    styles.value[field] = value + 'px'
    setStyles(styles.value)
}

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
        <div>
            <div class="mb-3 w-25">
                <h6>paddingLeft</h6>
                <VueSlider :value="splitUnit(styles.paddingLeft).value" @dragging="(value) => handleUpdate('paddingLeft', value)"/>
            </div>
            <div class="mb-3 w-25">
                <h6>paddingRight</h6>
                <VueSlider :value="splitUnit(styles.paddingRight).value" @dragging="(value) => handleUpdate('paddingRight', value)"/>
            </div>
            <div class="mb-3 w-25">
                <h6>paddingTop</h6>
                <VueSlider :value="splitUnit(styles.paddingTop).value" @dragging="(value) => handleUpdate('paddingTop', value)"/>
            </div>
            <div class="mb-3 w-25">
                <h6>paddingBottom</h6>
                <VueSlider :value="splitUnit(styles.paddingBottom).value" @dragging="(value) => handleUpdate('paddingBottom', value)"/>
            </div>
        </div>
    </div>
</template>
