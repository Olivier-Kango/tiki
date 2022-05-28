<script>
export default {
    name: 'Pop-up-builder'
}
</script>
<script setup>
import { ref, provide, onMounted } from 'vue'

const props = defineProps({
    block: {
        type: Object,
        default() {
            return {}
        }
    },
    context: Object
})

provide('context', {
    actions: {
        closePopup: closePopup
    }
})
const showPopup = ref(false)

onMounted(() => {
    setTimeout(() => {
        showPopup.value = true
    }, 3000)
})

function closePopup() {
    showPopup.value = false
}
</script>

<template>
    <VDropdown
        :shown="showPopup"
        :autoHide="true"
        :triggers="[]"
        :positioning-disabled="true"
    >
        <template #popper>
            <slot />
        </template>
    </VDropdown>
</template>

<style lang="scss">
    .v-popper__popper {
        position: fixed;
        width: 50%;
        margin-left: 25%;
        margin-top: 4rem;

        .v-popper__inner {
            border: none;
        }
    }
</style>
