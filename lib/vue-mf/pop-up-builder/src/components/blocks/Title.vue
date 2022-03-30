<script>
export default {
    name: 'Title'
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
    <h1 :class="classes" :style="styles">
        {{text}}
        <slot/>
    </h1>
</template>

<style lang="scss">

</style>
