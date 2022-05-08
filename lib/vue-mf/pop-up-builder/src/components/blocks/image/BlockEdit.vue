<script>
export default {
    name: 'Image'
}
</script>
<script setup>
import { ref, computed, watch } from 'vue'
import BlockAlignTextToolbar from '../../utils/BlockAlignTextToolbar.vue'
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
    },
    context: Object
})

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
    <img class="img-fluid" :src="props.context?.image || block.attributes.url || 'https://upload.wikimedia.org/wikipedia/commons/6/65/No-Image-Placeholder.svg'" alt="">

    <BlockPopup v-if="isSelected">
        <div class="d-flex shadow bg-white">
            <BlockControls :block="block" @edit="handleEdit"></BlockControls>
            <BlockAlignTextToolbar @action="handleAlignText"></BlockAlignTextToolbar>
            <!-- <BlockRichTextToolbar @action="handleAction"></BlockRichTextToolbar> -->
        </div>
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
