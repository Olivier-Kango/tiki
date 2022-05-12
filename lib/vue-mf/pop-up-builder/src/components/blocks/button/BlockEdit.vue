<script>
export default {
    name: 'Button'
}
</script>
<script setup>
import { ref, computed, watch } from 'vue'
import { Button } from '@vue-mf/styleguide'
import BlockRichText from '../../utils/block-rich-text/BlockRichText.vue'
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

const { isSelected, isHighlighted, setAttributes, setClasses, setStyles } = useState(props.block.id)

const content = ref(props.block.attributes?.content)
const showPopup = ref(false)
const showInspector = ref(false)

watch(() => isSelected.value, (val, oldVal) => showPopup.value = val)

const handleEdit = (e) => showInspector.value = true

const handleAlignText = (styles) => {
    setStyles(styles)
}

const handleInput = (data) => {
    content.value = data
    setAttributes({ content: data })
}
</script>

<template>
    <Button>
        <BlockRichText
            tag="p"
            :content="content"
            @onInput="handleInput"
        />
    </Button>

    <BlockPopup v-if="isSelected">
        <BlockControls :block="block" @edit="handleEdit"></BlockControls>
        <BlockAlignTextToolbar @action="handleAlignText"></BlockAlignTextToolbar>
        <!-- <BlockRichTextToolbar @action="handleAction"></BlockRichTextToolbar> -->
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
