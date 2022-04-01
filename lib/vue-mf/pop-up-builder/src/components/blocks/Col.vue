<script>
export default {
    name: 'Col'
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
    <div :class="block.classes" class="col" :style="block.styles">
        <slot/>
    </div>
</template>

<style lang="scss">
</style>
