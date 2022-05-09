<template>
    <div
        ref="reference"
        v-click-outside="onClickOutsideConfig"
        class="popup-container"
    >
        <slot />
    </div>
</template>

<script>
import vClickOutside from 'click-outside-vue3'
import { createPopper } from '@popperjs/core';

export default {
    name: 'Popup',
    directives: {
        clickOutside: vClickOutside.directive
    },
    beforeUnmount() {
        if (this.popper) this.destroyPopper()
        this.unsubscribe()
    },
    props: {
        target: {
            type: [String, Object, HTMLElement],
            default: () => { }
        },
    },
    data: function () {
        return {
            show: false,
            popper: null,
            onClickOutsideConfig: {
                handler: this.onClickOutside,
                middleware: this.middlewareClickOutside,
                // events: ['dblclick', 'click'],
                // Note: The default value is true, but in case you want to activate / deactivate
                //       this directive dynamically use this attribute.
                // isActive: true,
                // Note: The default value is true. See "Detecting Iframe Clicks" section
                //       to understand why this behaviour is behind a flag.
                // detectIFrame: true,
                // Note: The default value is false. Sets the capture option for EventTarget addEventListener method.
                //       Could be useful if some event's handler calls stopPropagation method preventing event bubbling.
                capture: true
            }
        }
    },
    watch: {
        target: {
            immediate: true,
            handler(val, oldVal) {
                // if (val) {
                //     this.$nextTick(() => {
                //         this.getTargetEl(val).addEventListener('click', this.handleShow, false)
                //     })
                // }
                if (val) {
                    this.$nextTick(() => {
                        this.initPopper()
                    })
                }
            }
        }
    },
    mounted() {
        this.$nextTick(() => {
            this.initPopper()
        })
    },
    methods: {
        initPopper: function () {
            if (this.target && this.$refs.reference) {
                this.popper = createPopper(this.getTargetEl(this.target), this.$refs.reference, {
                    placement: 'top-start',
                    container: 'body',
                    modifiers: [
                        {
                            name: 'offset',
                            options: {
                                offset: [0, 3],
                            },
                        },
                    ],
                })
            }
        },
        destroyPopper: function () {
            this.popper.destroy()
            this.popper = null
        },
        unsubscribe: function () {
            // this.getTargetEl(this.target).removeEventListener('click', this.handleShow, false)
        },
        handleShow: function (e) {
            e.stopPropagation()
            this.show = true
            if (!this.popper) {
                this.$nextTick(() => {
                    this.initPopper()
                })
            }
        },
        handleHide: function () {
            // this.show = false
            // if (this.popper) this.destroyPopper()
            // this.$emit('destroy')
        },
        middlewareClickOutside: function (event) {
            let isBlockItem = Array.from(event.target.classList).includes('block-item') || Boolean(event.target.closest(".block-item"))
            let isBlockTreeItem = Array.from(event.target.classList).includes('block-tree-item') || Boolean(event.target.closest(".block-tree-item"))

            return !isBlockItem && !isBlockTreeItem
        },
        onClickOutside: function (event) {
            this.handleHide()
        },
        getTargetEl: function (target) {
            if (typeof target === 'object') {
                return target
            } else if (typeof target === 'string') {
                return document.querySelector(target)
            }
        }
    },
}
</script>

<style scoped>
.popup-container {
    padding-left: 0;
    padding-right: 0;
    width: max-content;
    z-index: 1;
}
</style>
