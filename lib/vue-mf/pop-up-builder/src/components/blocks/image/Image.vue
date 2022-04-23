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
    },
    context: Object
});
</script>

<template>
    <div :class="block.classes" :style="block.styles" style="width: 300px">
        <img class="img-fluid" :src="props.context?.image || block.attributes.url || 'https://wordpress.org/gutenberg/files/2020/08/plugins-2.gif'" alt="">
    </div>
</template>

<style lang="scss">

</style>
