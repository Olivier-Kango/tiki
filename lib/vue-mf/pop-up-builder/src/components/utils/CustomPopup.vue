<template>
    <Teleport to="body">
        <div
            v-click-outside="onClickOutsideConfig"
            v-if="show"
            ref="reference"
            class="popup-container shadow rounded p-1"
        >
            <div class="popup-inner" ref="inner">
                <slot />
            </div>
        </div>
    </Teleport>
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
                if (val) {
                    this.$nextTick(() => {
                        this.getTargetEl(val).addEventListener('click', this.handleShow, false)
                    })
                }
            }
        }
    },
    methods: {
        initPopper: function () {
            if (this.target) {
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
            this.getTargetEl(this.target).removeEventListener('click', this.handleShow, false)
        },
        handleShow: function (e) {
            e.stopPropagation()
            console.log(this.target, this.getTargetEl(this.target))
            this.show = true
            if (!this.popper) {
                this.$nextTick(() => {
                    this.initPopper()
                })
            }
        },
        handleHide: function () {
            this.show = false
            if (this.popper) this.destroyPopper()
        },
        onClickOutside: function (event) {
            this.handleHide()
        },
        middlewareClickOutside: function (event) {
            return event.target.id !== this.getTargetEl(this.target).id
        },
        getTargetEl: function (target) {
            if (typeof target === 'object') {
                return target
            } else if (typeof target === 'string') {
                return document.getElementById(target)
            }
        }
    },
}
</script>

<style scoped>
.popup-container {
    background-color: white;
    z-index: 1;
}
</style>
