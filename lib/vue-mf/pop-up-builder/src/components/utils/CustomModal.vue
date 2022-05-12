<script>
export default {
    name: 'CustomModal'
}
</script>

<script setup>
import { ref, watch } from 'vue'
import { Button } from '@vue-mf/styleguide'
import { VueFinalModal } from 'vue-final-modal'

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    drag: {
        type: Boolean,
        default: false
    },
    resize: {
        type: Boolean,
        default: false
    },
    clickToClose: {
        type: Boolean,
        default: false
    },
    title: String,
    dragSelector: String,
})
const emit = defineEmits(['close'])

const showModal = ref(false)

watch(() => props.show, (val, oldVal) => {
    showModal.value = val
}, { immediate: true })
</script>

<template>
    <VueFinalModal
        v-model="showModal"
        classes="f-modal-container"
        content-class="f-modal-content"
        :drag="drag"
        :drag-selector="dragSelector"
        :resize="resize"
        :fit-parent="true"
        :prevent-click="true"
        :click-to-close="clickToClose"
        :focus-retain="false"
        :lock-scroll="false"
        attach="body"
        @click-outside="() => {if (clickToClose) emit('close')}"
    >
        <div v-if="showModal">
            <div class="f-modal-title p-3" style="min-height: 25px;">
                <h2 class="modal-drag mb-0">{{ title }}</h2>
                <Button class="f-modal-close" variant="default" @click="() => emit('close')">
                    <i class="fas fa-times"></i>
                </Button>
            </div>
            <div class="p-3">
                <slot/>
            </div>
        </div>
    </VueFinalModal>
</template>

<style lang="scss" scoped>
    .vfm.vfm--inset {
        position: fixed;

        :deep(.vfm--overlay) {
            background-color: rgb(255, 255, 255, 0);
        }
    }

    :deep(.f-modal-container) {
        overflow-x: hidden;
        overflow-y: auto;

        .f-modal-close {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
        }
    }

    :deep(.f-modal-content) {
        position: absolute;
        top: 0;
        right: 0;
        min-width: 500px;
        height: 100%;
        overflow-y: scroll;
        overflow-x: hidden;
        border: 1px solid #e2e8f0;
        border-radius: 0.25rem;
        background: #fffffff2;
    }

    :deep(.vfm--outline-none[data-v-2836fdb5]:focus) {
        pointer-events: all;
    }

    .modal__title {
        font-size: 1.5rem;
        font-weight: 700;
    }
</style>