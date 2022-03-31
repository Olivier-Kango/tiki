<script>
export default {
    name: 'Row'
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
    <div :class="classes" class="row" :style="styles">
        <slot/>
    </div>
</template>

<style lang="scss" scoped>
.row {
    min-height: 50px;
}
</style>
