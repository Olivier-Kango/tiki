import { ref, computed } from 'vue'
import store from '../../store'

export function useState(currentBlock) {
    const selected = ref()
    const highlighted = ref()

    const isSelected = computed(() => {
        selected.value = store.getters.getSelected
        if (!selected.value) return false
        if (currentBlock.id === selected.value.data.id) return true
    })

    const isHighlighted = computed(() => {
        highlighted.value = store.getters.getHighlighted
        if (!highlighted.value) return false
        if (currentBlock.id === highlighted.value.data.id) return true
    })

    const setAttributes = (attributes) => {
        store.dispatch('updateBlock', {
            block: currentBlock,
            field: 'attributes',
            value: {...currentBlock.attributes, ...attributes}
        })
    }

    const setStyles = (styles) => {
        store.dispatch('updateBlock', {
            block: currentBlock,
            field: 'styles',
            value: {...currentBlock.attributes, ...styles}
        })
    }

    const insertBlock = (block) => {
        store.dispatch('addBlock', {
            block: block,
            inBlock: currentBlock
        })
    }

    const insertBlockAfter = (block) => {
        store.dispatch('addBlockAfter', {
            block: block,
            inBlock: currentBlock
        })
    }

    return {
        selected,
        highlighted,
        isSelected,
        isHighlighted,
        setAttributes,
        setStyles,
        insertBlock,
        insertBlockAfter
    }
}
