<template>
    <button type="button" :class="`btn btn-${variant}`">
        <span><slot /></span>
    </button>
</template>

<script>
export default {
    name: 'Button',
    props: {
        variant: {
            type: String,
            required: false,
            default: 'primary',
        },
    }
}
</script>

<style scoped>

</style>
