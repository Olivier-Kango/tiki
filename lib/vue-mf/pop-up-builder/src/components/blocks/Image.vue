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
        <img class="img-fluid" :src="'https://img.lovepik.com/free-png/20220106/lovepik-popcorn-png-image_401196579_wh860.png'" alt="">
    </div>
</template>

<style lang="scss">

</style>
