<script>
export default {
    name: 'Title'
}
</script>
<script setup>
import { ref, computed, watch } from 'vue'
import BlockAlignTextToolbar from '../../utils/BlockAlignTextToolbar.vue'
import BlockControls from '../../utils/BlockControls.vue'
import BlockInspector from '../../utils/BlockInspector.vue'
import CustomPopup from '../../utils/CustomPopup.vue'
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
    <div>
        <h1 ref="popupTarget" :class="classes" :style="block.styles" contenteditable="true" @blur="handleBlur" v-html="textContent"></h1>

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
            <BlockInspector :block="block" :context="context"/>
        </CustomModal>
    </div>
</template>
