<script>
export default {
    name: 'Container'
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
    }
});

</script>

<template>
    <div :class="classes" class="container shadow rounded-4 p-4" :style="styles">
        <slot />
    </div>
</template>

<style lang="scss">

</style>
