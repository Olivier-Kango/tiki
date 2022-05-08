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
    <h1 v-html="block.attributes.textContent"></h1>
</template>
