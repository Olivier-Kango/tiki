<script>
export default {
    name: 'BlocksHistoryPagination'
}
</script>

<script setup>
import { ref, computed, watch } from 'vue' 
import { Button } from '@vue-mf/styleguide'
import { useStore } from 'vuex'
import _debounce from 'lodash/debounce'
import { saveToLocalStorage } from '../../store/helpers'

const store = useStore()
const index = ref(0)
const setBlocksFromHistory = ref(false)

const getHistoryLength = computed(() => store.getters.getHistoryLength)

watch(() => store.getters.getBlocks, _debounce((val, oldVal) => {
    if (setBlocksFromHistory.value) {
        setBlocksFromHistory.value = false
        return
    }
    store.dispatch('setHistory')
    saveToLocalStorage(val)
}, 300), { deep: true })

watch(() => getHistoryLength.value, (length, oldVal) => {
    if (length > 1) index.value = length - 1
})

const handlePrev = () => {
    if (index.value > 0) {
        index.value -= 1
        setBlocksFromHistory.value = true
        store.dispatch('setBlocksFromHistory', index.value)
    }
}

const handleNext = () => {
    if (index.value < getHistoryLength.value - 1) {
        index.value += 1
        setBlocksFromHistory.value = true
        store.dispatch('setBlocksFromHistory', index.value)
    }
}
</script>

<template>
    <Button :class="{ 'disabled': index === 0 }" variant="default" sm @click="handlePrev">
        <i class="fas fa-arrow-left"></i>
    </Button>
    <Button :class="{ 'disabled': index >= getHistoryLength - 1 }" variant="default" sm @click="handleNext">
        <i class="fas fa-arrow-right"></i>
    </Button>
</template>
