<template>
    <div class="btn-toolbar" role="toolbar" aria-label="Toolbar with button groups">
        <slot />
    </div>
</template>

<script>
export default {
    name: 'ButtonToolbar',
    props: {
        // variant: {
        //     type: String,
        //     required: false,
        //     default: 'primary',
        // },
        // sm: {
        //     type: Boolean
        // },
        // lg: {
        //     type: Boolean
        // }
    }
}
</script>

<style scoped>

</style>
