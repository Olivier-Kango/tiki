<script>
export default {
    name: 'Column'
}
</script>
<script setup>
import { ref, computed, watch } from 'vue'
import BlockAlignToolbar from '../../utils/BlockAlignToolbar.vue'
import BlockToolbarColors from '../../utils/BlockToolbarColors.vue'
import BlockControls from '../../utils/BlockControls.vue'
import BlockControlsSpacing from '../../utils/BlockControls/BlockControlsSpacing.vue'
import BlockControlsSizes from '../../utils/BlockControls/BlockControlsSizes.vue'
import BlockInspector from '../../utils/BlockInspector.vue'
import CustomPopup from '../../utils/CustomPopup.vue'
import CustomModal from '../../utils/CustomModal.vue'
import { useState } from '../../hooks/useState'

const props = defineProps({
    block: {
        type: Object,
        default() {
            return {}
        }
    }
});

const { isSelected, isHighlighted, setAttributes, setClasses, setStyles } = useState(props.block.id)

const popupTarget = ref(null)
const showPopup = ref(false)
const showInspector = ref(false)

const classes = computed(() => {
    if (props.block.classes) {
        return [props.block.classes].concat([
            {'is-highlighted': isHighlighted.value}
        ])
    } else {
        return [{'is-highlighted': isHighlighted.value}]
    }
})

watch(() => isSelected.value, (val, oldVal) => showPopup.value = val)

const handleEdit = (e) => showInspector.value = true

const handleAlignText = (styles) => {
    setStyles(styles)
}
</script>

<template>
    <div ref="popupTarget" class="col d-flex" :class="classes" :style="block.styles">
        <slot />

        <CustomPopup v-if="showPopup" :target="popupTarget">
            <div class="d-flex shadow bg-white">
                <BlockControls :block="block" @edit="handleEdit"></BlockControls>
                <BlockAlignToolbar :block="block"></BlockAlignToolbar>
                <BlockToolbarColors :block="block"></BlockToolbarColors>
            </div>
        </CustomPopup>
        <CustomModal
            v-if="showInspector"
            :show="showInspector"
            @close="showInspector = false"
            :drag="true"
            :resize="true"
            :drag-selector="'.modal-drag'"
            title="Settings"
        >
            <BlockInspector :block="block" :context="context">
                <BlockControlsSpacing :block="block" />
                <BlockControlsSizes :block="block" />
            </BlockInspector>
        </CustomModal>
    </div>
</template>
