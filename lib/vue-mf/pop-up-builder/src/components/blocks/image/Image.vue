<script>
export default {
    name: 'Image'
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
    <div :class="block.classes" :style="block.styles">
        <img class="img-fluid" :src="block.attributes.url || 'https://wordpress.org/gutenberg/files/2020/08/plugins-2.gif'" alt="">
    </div>
</template>

<style lang="scss">

</style>
