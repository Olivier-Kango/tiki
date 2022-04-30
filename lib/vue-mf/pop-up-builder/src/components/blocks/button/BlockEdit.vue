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
import CustomPopup from '../../utils/CustomPopup.vue'
import CustomModal from '../../utils/CustomModal.vue'
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
    <div ref="popupTarget" :class="classes" class="core-button-container" :style="block.styles">
        <Button>
            <BlockRichText
                tag="p"
                :content="content"
                @onInput="handleInput"
            />
        </Button>

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
