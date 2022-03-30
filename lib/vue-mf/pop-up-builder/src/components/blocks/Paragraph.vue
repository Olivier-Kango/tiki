<script>
export default {
    name: 'Paragraph'
}
</script>
<script setup>
const props = defineProps({
    styles: {
        type: Object,
        default() {
            return {}
        }
    },
    classes: {
        type: Array,
        default() {
            return []
        }
    },
    text: {
        type: String
    }
});

</script>

<template>
    <p :class="classes" :style="styles">
        {{text}}
        <slot/>
    </p>
</template>

<style lang="scss">

</style>
