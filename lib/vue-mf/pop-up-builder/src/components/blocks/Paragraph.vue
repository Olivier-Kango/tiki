<script>
export default {
    name: 'Paragraph'
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
});

</script>

<template>
    <p :class="block.classes" :style="block.styles">
        {{block.textContent}}
        <slot/>
    </p>
</template>

<style lang="scss">

</style>
