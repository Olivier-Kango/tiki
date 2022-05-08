<script>
export default {
    name: 'Column'
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
})
</script>

<template>
    <slot />
</template>
