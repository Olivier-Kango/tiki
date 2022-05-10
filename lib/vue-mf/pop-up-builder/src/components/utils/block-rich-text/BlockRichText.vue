<script>
export default {
    name: 'BlockRichText'
}
</script>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import MediumEditor from 'medium-editor'
import 'medium-editor/dist/css/medium-editor.min.css'
import 'medium-editor/dist/css/themes/beagle.min.css'

const props = defineProps({
    tag: {
        type: String,
        default: 'div'
    },
    content: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['onInput', 'onBlur'])

const refContainer = ref(null)
const editor = ref(null)

onMounted(() => {
    editor.value = new MediumEditor(refContainer.value, {
        toolbar: {
            buttons: ['bold', 'italic', 'underline', 'subscript', 'superscript', 'anchor']
        }
    })
    editor.value.setContent(props.content)
    editor.value.subscribe('editableInput', handleEditableInput)
})

onUnmounted(() => {
    editor.value.unsubscribe('editableInput', handleEditableInput)
    editor.value.destroy()
})

watch(() => props.content, (val, oldVal) => {
    editor.value.setContent(val)
})

function handleEditableInput(event, editable) {
    emit('onInput', editor.value.getContent())
}

function handleBlur(event, editable) {
    emit('onBlur', editor.value.getContent())
}
</script>

<template>
    <component :is="tag" class="rich-text-container" ref="refContainer" @blur="handleBlur"></component>
</template>

<style lang="scss">
    .rich-text-container {
        &:focus-visible {
            outline: none;
        }
    }
    .medium-editor-element {
        min-height: auto;
    }
</style>
