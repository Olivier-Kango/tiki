<script>
export default {
    name: 'BlockInspector'
}
</script>

<script setup>
defineProps({
    edit: Boolean
})
</script>

<template>
    <div class="block-inspector">
        <slot/>
    </div>
</template>
