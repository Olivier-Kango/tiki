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
    <figure class="figure">
        <img class="figure-img img-fluid" :src="props.context?.image || block.attributes.url || 'https://upload.wikimedia.org/wikipedia/commons/6/65/No-Image-Placeholder.svg'" alt="">
        <figcaption class="figure-caption">A caption for the above image.</figcaption>
    </figure>
</template>
