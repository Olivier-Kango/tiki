<script>
export default {
    name: 'Modal'
}
</script>

<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from "vue"

const props = defineProps({
    title: {
        type: String,
        default: "Title"
    },
    show: Boolean
})

const emit = defineEmits('hidden')

const modalRef = ref(null)
const modal = ref(null)

onMounted(() => {
    modal.value = new bootstrap.Modal(modalRef.value)
    modalRef.value.addEventListener('hidden.bs.modal', handleHiddenBsModal)
    // modal.value.show()
})

onBeforeUnmount(() => {
    console.log('onBeforeUnmount')
    modalRef.value.removeEventListener('hidden.bs.modal', handleHiddenBsModal)
    // modal.value.dispose()
})

watch(() => props.show, (val, oldVal) => {
    if (val) {
        if (!modal.value) {
            modal.value = new bootstrap.Modal(modalRef.value)
            modalRef.value.addEventListener('hidden.bs.modal', handleHiddenBsModal)
            
        }
        if (modal.value) modal.value.show()
    } else {
        if (modal.value) modal.value.hide()
    }
})

function handleHiddenBsModal() {
    emit('hidden')
    // if (modal.value) modal.value.dispose()
}
</script>

<template>
    <Teleport to="body">
        <div
            class="modal fade"
            id="exampleModal"
            tabindex="-1"
            aria-labelledby=""
            aria-hidden="true"
            ref="modalRef"
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">{{ title }}</h5>
                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <slot name="body" />
                    </div>
                    <div class="modal-footer">
                        <slot name="footer"></slot>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
