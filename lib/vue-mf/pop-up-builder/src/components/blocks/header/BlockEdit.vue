<script>
export default {
    name: 'BlockEdit'
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
import store from '../../../store'

const props = defineProps({
    block: {
        type: Object,
        default() {
            return {}
        }
    }
});

const { isSelected, isHighlighted, setAttributes, setClasses, setStyles } = useState(props.block.id)

const textContent = ref(props.block.attributes.textContent)
const showPopup = ref(false)
const showInspector = ref(false)

watch(() => isSelected.value, (val, oldVal) => showPopup.value = val)

watch(props.block.attributes.textContent, (val, oldVal) => {
    textContent.value = val
})

const handleEdit = (e) => showInspector.value = true
const handleAlignText = (styles) => setStyles(styles)

const handleBlur = (e) => {
    store.dispatch('updateBlock', {
        block: props.block,
        field: 'attributes',
        value: {...props.block.attributes, textContent: e.target.textContent}
    })
}
</script>

<template>
    <h1 contenteditable="true" @blur="handleBlur" v-html="textContent"></h1>

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
