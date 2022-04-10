<script>
export default {
    name: 'Paragraph'
}
</script>

<script setup>
import { ref, watch, computed } from 'vue'
import VueRichText from '../../utils/vue-rich-text/VueRichText.vue'
import VueRichTextToolbar from '../../utils/vue-rich-text/VueRichTextToolbar.vue'
import BlockAlignTextToolbar from '../../utils/BlockAlignTextToolbar.vue'
import BlockControls from '../../utils/BlockControls.vue'
import BlockInspector from '../../utils/BlockInspectorControls.vue'
import BlockInspectorControls from '../../utils/BlockInspectorControls.vue'
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

const { isSelected, isHighlighted, setAttributes, setStyles } = useState(props.block)
const textContent = ref(props.block.attributes.textContent)
const textAction = ref()
const showInspector = ref(false)

const classes = computed(() => 
    props.block.classes.concat([
        {'is-highlighted': isHighlighted.value}
    ])
)

watch([props.block.attributes.textContent], (val, oldVal) => {
    textContent.value = val
})

const handlePopupDestroy = () => store.dispatch('setSelected', null)
const handleAction = (action) => textAction.value = action

const handleInput = (content) => {
    textContent.value = content
    setAttributes({ textContent: content })
}

const handleBlur = (content) => {
    textContent.value = content
    setAttributes({ textContent: content })
}

const handleAlignText = (styles) => setStyles(styles)

const handleEdit = (e) => showInspector.value = true
</script>

<template>
    <div :class="classes" :style="block.styles">
        <VueRichText
            v-if="isSelected"
            tag="p"
            :content="textContent"
            :action="textAction"
            @onInput="handleInput"
            @onBlur="handleBlur"
        />
        <CustomPopup v-if="isSelected" :target="'block-' + block.id" @destroy="handlePopupDestroy">
            <div class="d-flex shadow bg-white">
                <BlockControls :block="block" @edit="handleEdit"></BlockControls>
                <BlockAlignTextToolbar @action="handleAlignText"></BlockAlignTextToolbar>
                <VueRichTextToolbar @action="handleAction"></VueRichTextToolbar>
            </div>
        </CustomPopup>
        <CustomModal v-if="showInspector" :show="showInspector" @close="showInspector = false">
            <BlockInspector>
                <BlockInspectorControls>

                </BlockInspectorControls>
            </BlockInspector>
        </CustomModal>

        <p v-if="!isSelected" v-html="textContent"></p>
    </div>
</template>
