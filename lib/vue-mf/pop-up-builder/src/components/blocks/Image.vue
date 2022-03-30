<script>
export default {
    name: 'Image'
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
    <div :class="classes" :style="styles">
        <img class="img-fluid" :src="text" alt="">
    </div>
</template>

<style lang="scss">

</style>
