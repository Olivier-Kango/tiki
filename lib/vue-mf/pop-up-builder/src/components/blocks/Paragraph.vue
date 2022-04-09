<script>
export default {
    name: 'Paragraph'
}
</script>

<script setup>
import { ref, watch, computed } from 'vue'
import VueRichText from '../utils/vue-rich-text/VueRichText.vue'
import VueRichTextToolbar from '../utils/vue-rich-text/VueRichTextToolbar.vue'
import BlockAlignTextToolbar from '../utils/BlockAlignTextToolbar.vue'
import BlockControls from '../utils/BlockControls.vue'
import CustomPopup from '../utils/CustomPopup.vue'
import { useState } from '../hooks/useState'
import store from '../../store'

const props = defineProps({
    block: {
        type: Object,
        default() {
            return {}
        }
    },
    edit: {
        type: Boolean,
        default: false
    }
});

const { isSelected, isHighlighted } = useState(props.block)
const textContent = ref(props.block.attributes.textContent)
const textAction = ref()

const classes = computed(() => {
    let newClasses = []
    if (isHighlighted.value) newClasses.push('is-highlighted')
    return newClasses.concat(props.block.classes)
})

watch([props.block.attributes.textContent], (val, oldVal) => {
    textContent.value = val
})

const handlePopupDestroy = () => store.dispatch('setSelected', null)
const handleAction = (action) => textAction.value = action

const handleBlur = (content) => {
    store.dispatch('updateBlock', {
        block: props.block,
        field: 'attributes',
        value: {...props.block.attributes, textContent: content}
    })
}

const handleAlignText = (style) => {
    store.dispatch('updateBlock', {
        block: props.block,
        field: 'styles',
        value: {...props.block.styles, ...style}
    })
}
</script>

<template>
    <div v-if="edit" :class="classes" :style="block.styles">
        <VueRichText
            tag="p"
            :content="textContent"
            :action="textAction"
            @onBlur="handleBlur"
        />
        <CustomPopup v-if="isSelected && edit" :target="'block-' + block.id" @destroy="handlePopupDestroy">
            <div class="d-flex bg-white">
                <BlockAlignTextToolbar @action="handleAlignText"></BlockAlignTextToolbar>
                <VueRichTextToolbar @action="handleAction"></VueRichTextToolbar>
                <!-- <BlockControls :node="block"></BlockControls> -->
            </div>
        </CustomPopup>
        <p v-if="!edit" :class="classes" :style="block.styles" v-html="textContent"></p>
    </div>
</template>
