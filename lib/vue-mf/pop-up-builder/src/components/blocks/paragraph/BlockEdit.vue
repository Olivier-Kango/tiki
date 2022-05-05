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
import BlockControls from '../../utils/BlockControls.vue'
import BlockControlsSpacing from '../../utils/BlockControls/BlockControlsSpacing.vue'
import BlockControlsSizes from '../../utils/BlockControls/BlockControlsSizes.vue'

import BlockInspector from '../../utils/BlockInspector.vue'
// import BlockInspectorControls from '../../utils/BlockInspectorControls.vue'
import CustomPopup from '../../utils/CustomPopup.vue'
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
const popupTarget = ref(null)
const textAction = ref()
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
    <div ref="popupTarget" :id="'block-' + block.id" :class="classes" :style="block.styles">
        <BlockRichText
            v-if="!getContext"
            tag="p"
            :content="content"
            @onInput="handleInput"
        />
        <div v-if="getContext">{{ getContext }}</div>

        <CustomPopup v-if="showPopup" :target="popupTarget">
            <div class="d-flex shadow bg-white">
                <BlockControls :block="block" @edit="handleEdit"></BlockControls>
                <BlockAlignTextToolbar @action="handleAlignText"></BlockAlignTextToolbar>
                <!-- <BlockRichTextToolbar @action="handleAction"></BlockRichTextToolbar> -->
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
