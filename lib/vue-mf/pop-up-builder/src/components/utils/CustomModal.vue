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
    title: String
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
        :resize="resize"
        :fit-parent="false"
        :prevent-click="true"
        attach="body"
        @click-outside="() => emit('close')"
    >
        <div v-if="showModal">
            <div style="min-height: 25px;">
                <h2>{{ title }}</h2>
                <Button class="f-modal-close" variant="default" @click="() => emit('close')">
                    <i class="fas fa-times"></i>
                </Button>
            </div>
            <div>
                <slot/>
            </div>
        </div>
    </VueFinalModal>
</template>

<style lang="scss" scoped>
    :deep(.f-modal-container) {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
        overflow-x: hidden;
        overflow-y: auto;

        .f-modal-close {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
        }
    }
    :deep(.f-modal-content) {
        position: relative;
        display: flex;
        flex-direction: column;
        max-width: 960px;
        width: 100%;
        min-height: 75%;
        margin: 48px auto 80px auto;
        padding: 1rem;
        border: 1px solid #e2e8f0;
        border-radius: 0.25rem;
        background: #fff;
    }
    :deep(.vfm--outline-none[data-v-2836fdb5]:focus) {
        pointer-events: all;
    }
    .modal__title {
        font-size: 1.5rem;
        font-weight: 700;
    }
</style>