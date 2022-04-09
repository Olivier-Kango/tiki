// fetch.js
import { ref, computed } from 'vue'
import store from '../../store'

export function useState(block) {
    const selected = ref()
    const highlighted = ref()

    const isSelected = computed(() => {
        selected.value = store.getters.getSelected
        if (!selected.value) return false
        if (block.id === selected.value.data.id) return true
    })

    const isHighlighted = computed(() => {
        highlighted.value = store.getters.getHighlighted
        if (!highlighted.value) return false
        if (block.id === highlighted.value.data.id) return true
    })

    return {
        selected,
        highlighted,
        isSelected,
        isHighlighted
    }
}
