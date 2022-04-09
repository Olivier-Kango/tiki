<template>
    <div :class="['btn-group', {'btn-group-sm': sm}, {'btn-group-lg': lg}]" role="group" aria-label="Basic example">
        <slot />
    </div>
</template>

<script>
export default {
    name: 'ButtonGroup',
    props: {
        variant: {
            type: String,
            required: false,
            default: 'primary',
        },
        sm: {
            type: Boolean
        },
        lg: {
            type: Boolean
        }
    }
}
</script>

<style scoped>

</style>
