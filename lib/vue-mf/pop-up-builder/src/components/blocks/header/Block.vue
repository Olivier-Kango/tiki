<script>
export default {
    name: 'Block'
}
</script>
<script setup>
const props = defineProps({
    block: {
        type: Object,
        default() {
            return {}
        }
    }
})
</script>

<template>
    <div :class="block.classes" :style="block.styles">
        <h1 v-html="block.attributes.textContent"></h1>
    </div>
</template>
