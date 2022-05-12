<script>
export default {
    name: 'BlockEdit'
}
</script>
<script setup>
import { ref, watch } from 'vue'
import BlockAlignTextToolbar from '../../utils/BlockAlignTextToolbar.vue'
import BlockControls from '../../utils/BlockControls.vue'
import BlockInspector from '../../utils/BlockInspector.vue'
import BlockPopup from '../../utils/BlockPopup.vue'
import CustomModal from '../../utils/CustomModal.vue'
import CustomTabs from '../../utils/CustomTabs.vue'
import { useState } from '../../hooks/useState'

const props = defineProps({
    block: {
        type: Object,
        default() {
            return {}
        }
    }
});

const { isSelected, setAttributes, setStyles } = useState(props.block.id)

const textContent = ref(props.block.attributes.textContent)
const showPopup = ref(false)
const showInspector = ref(false)

watch(() => isSelected.value, (val, oldVal) => showPopup.value = val)

watch(() => props.block.attributes.textContent, (val, oldVal) => {
    if (val !== oldVal) textContent.value = val
})

const handleEdit = (e) => showInspector.value = true
const handleAlignText = (styles) => setStyles(styles)
const handleBlur = (e) => setAttributes({ textContent: e.target.textContent })
</script>

<template>
    <h1 contenteditable="true" placeholder="Type your text" @blur="handleBlur" v-html="textContent"></h1>

    <BlockPopup v-if="isSelected">
        <BlockControls :block="block" @edit="handleEdit"></BlockControls>
        <BlockAlignTextToolbar @action="handleAlignText"></BlockAlignTextToolbar>
    </BlockPopup>

    <CustomModal
        v-if="showInspector"
        :show="showInspector"
        @close="showInspector = false"
        :drag="true"
        :resize="true"
        :drag-selector="'.modal-drag'"
        :title="block.title"
        :description="block.description"
    >
        <CustomTabs>
            <template #styles>

            </template>
            <template #settings>
            
            </template>
            <template #advanced>
                <BlockInspector :block="block" :context="context"></BlockInspector>
            </template>
        </CustomTabs>
    </CustomModal>
</template>
