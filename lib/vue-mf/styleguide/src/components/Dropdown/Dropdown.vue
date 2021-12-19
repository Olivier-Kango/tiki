<template>
    <div class="dropdown">
        <button
            type="button"
            :class="['btn', `btn-${variant}`, { 'btn-sm': sm }]"
            @click="handleToggleMenu"
        >
            <slot name="dropdown-button" />
        </button>
        <div
            v-if="showMenu"
            v-click-outside="onClickOutside"
            class="dropdown-menu d-block"
            aria-labelledby="dropdownMenuButton"
        >
            <slot name="dropdown-menu" />
        </div>
    </div>
</template>

<script>
import vClickOutside from 'click-outside-vue3'
export default {
    name: 'Dropdown',
    directives: {
        clickOutside: vClickOutside.directive
    },
    props: {
        variant: {
            type: String,
            required: false,
            default: 'primary',
        },
        sm: {
            type: Boolean
        }
    },
    data: function () {
        return {
            showMenu: false
        }
    },
    methods: {
        handleToggleMenu: function () {
            this.showMenu = !this.showMenu
        },
        onClickOutside(event) {
            this.showMenu = false
        }
    },
}
</script>

<style scoped>
</style>
