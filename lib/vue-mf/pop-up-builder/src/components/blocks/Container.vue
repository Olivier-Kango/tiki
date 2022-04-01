<script>
export default {
    name: 'Container'
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
    <div :class="block.classes" class="container" :style="block.styles">
        <slot />
    </div>
</template>

<style lang="scss">

</style>
