<script>
export default {
    name: 'Paragraph'
}
</script>

<script setup>
import { ref, watch, computed, nextTick } from 'vue'
import BlockRichText from '../../utils/block-rich-text/BlockRichText.vue'
import BlockAlignTextToolbar from '../../utils/BlockAlignTextToolbar.vue'
import BlockControls from '../../utils/BlockControls.vue'
import BlockInspector from '../../utils/BlockInspector.vue'
import BlockPopup from '../../utils/BlockPopup.vue'
import CustomModal from '../../utils/CustomModal.vue'
import CustomTabs from '../../utils/CustomTabs.vue'
import { useState } from '../../hooks/useState'
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

const { isSelected, setAttributes, setStyles } = useState(props.block.id)
const content = ref(props.block.attributes.content)
const showPopup = ref(false)
const textAction = ref()
const showInspector = ref(false)

const getContext = computed(() => _get(props.context, props.block.attributes.field))

watch(() => isSelected.value, (val, oldVal) => {
    showPopup.value = val
})

watch(() => props.block.attributes.content, (val, oldVal) => {
    if (val !== oldVal) content.value = val
})

const handleBlur = (data) => {
    if (content.value !== data) setAttributes({ content: data })
}

const handleAlignText = (styles) => setStyles(styles)
const handleEdit = (e) => showInspector.value = true
</script>

<template>
    <BlockRichText
        v-if="!getContext"
        tag="p"
        :content="content"
        @onBlur="handleBlur"
    />
    <p v-if="getContext">{{ getContext }}</p>

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
