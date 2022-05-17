<script>
export default {
    name: 'BlocksHistoryPagination'
}
</script>

<script setup>
import { ref, computed, watch, watchEffect } from 'vue' 
import { Button } from '@vue-mf/styleguide'
import { useStore } from 'vuex'
import _debounce from 'lodash/debounce'
import { saveToLocalStorage } from '../../store/helpers'

const store = useStore()
const index = ref(0)
const setBlocksFromHistory = ref(false)

const getHistory = computed(() => store.getters.getHistory)

watch(() => store.getters.getBlocks, _debounce((val, oldVal) => {
    if (setBlocksFromHistory.value) {
        setBlocksFromHistory.value = false
        return
    }
    store.dispatch('setHistory')
    saveToLocalStorage()
}, 300), { deep: true })

watch(() => getHistory.value.length, (length, oldVal) => {
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
    if (index.value < getHistory.value.length - 1) {
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
    <Button :class="{ 'disabled': index >= getHistory.length - 1 }" variant="default" sm @click="handleNext">
        <i class="fas fa-arrow-right"></i>
    </Button>
</template>
