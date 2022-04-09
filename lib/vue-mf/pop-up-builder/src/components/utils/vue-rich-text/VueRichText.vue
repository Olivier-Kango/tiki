<script>
export default {
    name: 'VueRichText'
}
</script>

<script setup>
import { watch } from 'vue'
import { useRichText } from './useRichText'

const props = defineProps({
    tag: {
        type: String,
        default: 'div'
    },
    content: {
        type: String,
        default: ''
    },
    action: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['onBlur'])
const { html, setInnerHTML, setAction } = useRichText(props.content)

watch(() => props.action, (val, oldVal) => {
    setAction(val)
})

const handleInput = (e) => {
    setInnerHTML(e.target.innerHTML)
}
const handleBlur = (e) => {
    emit('onBlur', e.target.innerHTML)
}
</script>

<template>
    <component class="rich-text-container" :is="tag" contenteditable="true" @input="handleInput" @blur="handleBlur" v-html="html"></component>
</template>

<style lang="scss" scoped>
    .rich-text-container {
        &:focus-visible {
            outline: none;
        }
    }
</style>
