<script>
export default {
    name: 'Paragraph'
}
</script>

<script setup>
import { ref, watch, computed, nextTick } from 'vue'
import BlockRichText from '../../utils/block-rich-text/BlockRichText.vue'
// import BlockRichTextToolbar from '../../utils/block-rich-text/BlockRichTextToolbar.vue'
import BlockAlignTextToolbar from '../../utils/BlockAlignTextToolbar.vue'
import BlockToolbarColors from '../../utils/BlockToolbarColors.vue'
import BlockControls from '../../utils/BlockControls.vue'
// import BlockControlsSpacing from '../../utils/BlockControls/BlockControlsSpacing.vue'
// import BlockControlsSizes from '../../utils/BlockControls/BlockControlsSizes.vue'

import BlockInspector from '../../utils/BlockInspector.vue'
// import BlockInspectorControls from '../../utils/BlockInspectorControls.vue'
import BlockPopup from '../../utils/BlockPopup.vue'
import CustomModal from '../../utils/CustomModal.vue'
import { useState } from '../../hooks/useState'
// import store from '../../../store'
// import BlockBuilder from '../../utils/BlockBuilder.vue'
import _get from 'lodash/get'

const props = defineProps({
    block: {
        type: Object,
        default() {
            return {}
        }
    },
    context: [Object, Array]
});

const { isSelected, isHighlighted, setAttributes, setStyles } = useState(props.block.id)
const content = ref(props.block.attributes.content)
const showPopup = ref(false)
const textAction = ref()
const showInspector = ref(false)

const getContext = computed(() => _get(props.context, props.block.attributes.field))

watch(() => isSelected.value, (val, oldVal) => {
    showPopup.value = val
})

const handleAction = (action) => textAction.value = action

const handleInput = (data) => {
    content.value = data
    setAttributes({ content: data })
}

const handleAlignText = (styles) => setStyles(styles)
const handleEdit = (e) => showInspector.value = true
</script>

<template>
    <BlockRichText
        v-if="!getContext"
        tag="p"
        :content="content"
        @onInput="handleInput"
    />
    <div v-if="getContext">{{ getContext }}</div>

    <BlockPopup v-if="isSelected">
        <BlockControls :block="block" @edit="handleEdit"></BlockControls>
        <BlockAlignTextToolbar @action="handleAlignText"></BlockAlignTextToolbar>
        <!-- <BlockToolbarColors :block="block"></BlockToolbarColors> -->
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
            <!-- <BlockControlsSpacing :block="block" />
            <BlockControlsSizes :block="block" /> -->
        </BlockInspector>
    </CustomModal>
</template>
