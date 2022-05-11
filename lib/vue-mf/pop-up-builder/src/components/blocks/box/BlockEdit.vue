<script>
export default {
    name: 'Container'
}
</script>
<script setup>
import { ref, watch } from 'vue'
import BlockAlignToolbar from '../../utils/BlockAlignToolbar.vue'
import BlockToolbarImage from '../../utils/BlockToolbarImage.vue'
import BlockToolbarColors from '../../utils/BlockToolbarColors.vue'
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

const { isSelected, setStyles } = useState(props.block.id)

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
        <BlockAlignToolbar :block="block" cssFlexDirection cssJustifyContent cssAlignItems></BlockAlignToolbar>
        <BlockToolbarImage :block="block"></BlockToolbarImage>
        <BlockToolbarColors :block="block"></BlockToolbarColors>
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
            <BlockControlsSizes :block="block" />
            <BlockControlsSpacing :block="block" type="padding" />
            <BlockControlsSpacing :block="block" type="margin" />
        </BlockInspector>
    </CustomModal>
</template>
