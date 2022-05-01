<script>
export default {
    name: 'Title'
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
    <div>
        <h1 :class="block.classes" :style="block.styles" v-html="block.attributes.textContent"></h1>
    </div>
</template>
