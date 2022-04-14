import { ref, computed } from 'vue'
import store from '../../store'

export function useState(blockId) {
    const selected = ref()
    const highlighted = ref()
    const currentBlock = ref(store.getters.getBlock(blockId))

    const isSelected = computed(() => {
        selected.value = store.getters.getSelected
        console.log(selected.value)
        if (!selected.value) return false
        if (currentBlock.value.id === selected.value.id) return true
    })

    const isHighlighted = computed(() => {
        highlighted.value = store.getters.getHighlighted
        if (!highlighted.value) return false
        if (currentBlock.value.id === highlighted.value.id) return true
    })

    const setAttributes = (attributes) => {
        store.dispatch('updateBlock', {
            block: currentBlock.value,
            field: 'attributes',
            value: {...currentBlock.value.attributes, ...attributes}
        })
    }

    const setStyles = (styles) => {
        store.dispatch('updateBlock', {
            block: currentBlock.value,
            field: 'styles',
            value: {...currentBlock.value.attributes, ...styles}
        })
    }

    const insertBlock = (block) => {
        store.dispatch('insertBlock', {
            name: block.name,
            parentId: currentBlock.value.id
        })
    }

    const insertBlockAfter = (block) => {
        store.dispatch('addBlockAfter', {
            block: block,
            inBlock: currentBlock.value
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
