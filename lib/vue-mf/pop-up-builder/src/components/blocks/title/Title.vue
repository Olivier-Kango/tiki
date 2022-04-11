<script>
export default {
    name: 'Title'
}
</script>
<script setup>
import { ref, watch } from 'vue'
import store from '../../../store'

const props = defineProps({
    block: {
        type: Object,
        default() {
            return {}
        }
    }
});

const textContent = ref(props.block.attributes.textContent)

watch(props.block.attributes.textContent, (val, oldVal) => {
    textContent.value = val
})

const handleBlur = (e) => {
    store.dispatch('updateBlock', {
        block: props.block,
        field: 'attributes',
        value: {...props.block.attributes, textContent: e.target.textContent}
    })
}
</script>

<template>
    <h1 :class="block.classes" :style="block.styles" contenteditable="true" @blur="handleBlur" v-html="textContent">
    </h1>
</template>

<style lang="scss">

</style>
