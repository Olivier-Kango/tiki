<script>
export default {
    name: 'Paragraph'
}
</script>

<script setup>
import { ref, watch, computed } from 'vue'
import BlockRichText from '../../utils/block-rich-text/BlockRichText.vue'
import BlockRichTextToolbar from '../../utils/block-rich-text/BlockRichTextToolbar.vue'
import BlockAlignTextToolbar from '../../utils/BlockAlignTextToolbar.vue'
import BlockControls from '../../utils/BlockControls.vue'
import BlockInspector from '../../utils/BlockInspector.vue'
import BlockInspectorControls from '../../utils/BlockInspectorControls.vue'
import CustomPopup from '../../utils/CustomPopup.vue'
import CustomModal from '../../utils/CustomModal.vue'
import { useState } from '../../hooks/useState'
import store from '../../../store'
import BlockBuilder from '../../utils/BlockBuilder.vue'
import cloneDeep from 'lodash/cloneDeep'

const props = defineProps({
    id: String,
    block: {
        type: Object,
        default() {
            return {}
        }
    },
    edit: Boolean
});

const { isSelected, isHighlighted, isEdit, setAttributes, setStyles } = useState(props.id)
const content = ref(props.block.attributes.content)
const showPopup = ref(false)
const textAction = ref()
const showInspector = ref(false)
const blocks = ref(cloneDeep(store.getters.getBlocks))

const classes = computed(() => 
    props.block.classes && props.block.classes.concat([
        {'is-highlighted': isHighlighted.value}
    ])
)
// const getBlock = computed(() => (id) => store.getters.getBlock(id))

watch(props.block.attributes, (val, oldVal) => {
    content.value = val
})

watch(() => isSelected.value, (val, oldVal) => {
    // if (!val) setAttributes({ content: content.value })
    console.log('isSelected', val, isSelected)
    showPopup.value = val
})

const handlePopupDestroy = () => {
    // showPopup.value = false
    // store.dispatch('setSelected', null)
}
const handleAction = (action) => textAction.value = action

const handleInput = (data) => {
    content.value = data
    setAttributes({ content: data })
}

const handleBlur = (data) => {
    content.value = data
    // setAttributes({ content: data })
}

const handleAlignText = (styles) => setStyles(styles)

const handleEdit = (e) => showInspector.value = true
</script>

<template>
    <div :id="'block-' + id" :class="classes" :style="block.styles">
        <BlockRichText
            v-if="edit"
            tag="p"
            :content="content"
            :action="textAction"
            @onInput="handleInput"
            @onBlur="handleBlur"
        />
        <p v-if="!edit" v-html="content"></p>

        <CustomPopup v-if="showPopup" :target="'block-' + block.id" @destroy="handlePopupDestroy">
            <div class="d-flex shadow bg-white">
                <BlockControls :block="block" @edit="handleEdit"></BlockControls>
                <BlockAlignTextToolbar @action="handleAlignText"></BlockAlignTextToolbar>
                <!-- <BlockRichTextToolbar @action="handleAction"></BlockRichTextToolbar> -->
            </div>
        </CustomPopup>
        <CustomModal v-if="showInspector" :show="showInspector" @close="showInspector = false">
            <BlockInspector>
                <BlockInspectorControls />
                <BlockBuilder :blocks="blocks" :blockIds="['f9860c4f-734f-4cb8-9904-93f2eac73f55']" />
                <BlockBuilder :blocks="blocks" :blockIds="['f9860c4f-734f-4cb8-9904-93f2eac73f55']" />
            </BlockInspector>
        </CustomModal>
    </div>
</template>
