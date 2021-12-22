<template>
    <div class="dropdown">
        <button
            ref="button"
            type="button"
            :class="['btn', `btn-${variant}`, { 'btn-sm': sm }]"
            @click="handleToggleMenu"
        >
            <slot name="dropdown-button" />
        </button>
        <div
            ref="dropdown"
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
import { createPopper } from '@popperjs/core';

export default {
    name: 'Dropdown',
    directives: {
        clickOutside: vClickOutside.directive
    },
    beforeUnmount() {
        if (this.popper) this.destroyPopper()
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
            showMenu: false,
            popper: null
        }
    },
    methods: {
        initPopper: function () {
            this.popper = createPopper(this.$refs.button, this.$refs.dropdown, {
                placement: 'bottom-start',
                modifiers: [
                    {
                        name: 'offset',
                        options: {
                            offset: [0, 8],
                        },
                    },
                ],
            })
        },
        destroyPopper: function () {
            this.popper.destroy()
        },
        handleToggleMenu: function () {
            this.showMenu = !this.showMenu
            if (this.showMenu) {
                this.$nextTick(() => {
                    this.initPopper()
                })
            } else if (this.popper) {
                this.destroyPopper()
            }
        },
        onClickOutside(event) {
            this.showMenu = false
            if (this.popper) this.destroyPopper()
        }
    },
}
</script>

<style scoped>
</style>
