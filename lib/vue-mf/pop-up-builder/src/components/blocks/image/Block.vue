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
    <div :class="block.classes" :style="block.styles">
        <img class="img-fluid" :src="props.context?.image || block.attributes.url || 'https://upload.wikimedia.org/wikipedia/commons/6/65/No-Image-Placeholder.svg'" alt="">
    </div>
</template>
