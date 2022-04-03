<script>
export default {
    name: 'Row'
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
    <div :class="block.classes" class="row" :style="block.styles">
        <slot/>
    </div>
</template>

<style lang="scss" scoped>
.row {
    min-height: 50px;
    margin-bottom: 10px;
}
</style>
