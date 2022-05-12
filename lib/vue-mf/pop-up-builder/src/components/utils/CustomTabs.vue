<script>
export default {
    name: 'CustomTabs'
}
</script>

<script setup>
defineProps({
    edit: Boolean
})
</script>

<template>
    <Tabs
        nav-class="nav nav-pills nav-fill"
        nav-item-class="nav-item"
        nav-item-link-class="nav-link"
        nav-item-link-active-class="active"
    >
        <tab name="Styles">
            <slot name="styles"/>
        </tab>
        <tab name="Settings">
            <slot name="settings"/>
        </tab>
        <tab name="Advanced">
            <slot name="advanced"/>
        </tab>
    </Tabs>
</template>

<style style="scss" scoped>
    :deep(.nav) {
        margin-bottom: 1rem;
    }
    :deep(.nav-pills .nav-link.active) {
        color: #fff;
        background-color: #000;
    }
    :deep(.nav-link) {
        color: black;
    }
</style>