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
    id: String,
    block: {
        type: Object,
        default() {
            return {}
        }
    }
});

const { isSelected, isHighlighted, setAttributes, setStyles } = useState(props.id)
const content = ref(props.block.attributes && props.block.attributes.content)
const textAction = ref()
const showInspector = ref(false)

const classes = computed(() => 
    props.block.classes && props.block.classes.concat([
        {'is-highlighted': isHighlighted.value}
    ])
)

watch([props.block.attributes], (val, oldVal) => {
    content.value = val
})

const handlePopupDestroy = () => store.dispatch('setSelected', null)
const handleAction = (action) => textAction.value = action

const handleInput = (data) => {
    content.value = data
    setAttributes({ content: data })
}

const handleBlur = (data) => {
    content.value = data
    setAttributes({ content: data })
}

const handleAlignText = (styles) => setStyles(styles)

const handleEdit = (e) => showInspector.value = true
</script>

<template>
    <div :id="'block-' + id" :class="classes" :style="block.styles">
        <VueRichText
            v-if="isSelected"
            tag="p"
            :content="content"
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

        <p v-if="!isSelected" v-html="content"></p>
    </div>
</template>
