<script>
export default {
    name: 'Container'
}
</script>
<script setup>
import { ref, computed, watch } from 'vue'
import BlockAlignTextToolbar from '../../utils/BlockAlignTextToolbar.vue'
import BlockToolbarImage from '../../utils/BlockToolbarImage.vue'
import BlockControls from '../../utils/BlockControls.vue'
import BlockControlsSpacing from '../../utils/BlockControls/BlockControlsSpacing.vue'
import BlockControlsSizes from '../../utils/BlockControls/BlockControlsSizes.vue'
import BlockInspector from '../../utils/BlockInspector.vue'
import BlockPopup from '../../utils/BlockPopup.vue'
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

const showPopup = ref(false)
const showInspector = ref(false)

watch(() => isSelected.value, (val, oldVal) => showPopup.value = val)

const handleEdit = (e) => showInspector.value = true

const handleAlignText = (styles) => {
    setStyles(styles)
}
</script>

<template>
    <slot />

    <BlockPopup v-if="isSelected">
        <BlockControls :block="block" @edit="handleEdit"></BlockControls>
        <BlockAlignTextToolbar @action="handleAlignText"></BlockAlignTextToolbar>
        <BlockToolbarImage :block="block"></BlockToolbarImage>
        <!-- <BlockRichTextToolbar @action="handleAction"></BlockRichTextToolbar> -->
    </BlockPopup>

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
</template>
