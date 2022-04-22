import { ref, computed } from 'vue'
import store from '../../store'
import {
    saveToLocalStorage,
    parseAttributesDefaults,
    createBlock,
    cloneBlockDeep,
    getParentBlockId,
    getDescendantIds,
    swapIndices
} from '../../store/helpers'

export function useState(blockId) {
    const mode = ref()
    const selected = ref()
    const highlighted = ref()
    const currentBlock = ref(store.getters.getBlock(blockId))

    const isEdit = computed(() => {
        mode.value = store.getters.getMode
        return mode.value === 'edit'
    })

    const isSelected = computed(() => {
        selected.value = store.getters.getSelected
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

    const insertBlock = (name) => {
        store.dispatch('insertBlock', {
            name: name,
            targetId: currentBlock.value.id
        })
    }

    const insertBlockAfter = (name) => {
        store.dispatch('insertBlockAfter', {
            name: name,
            targetId: currentBlock.value.id
        })
    }

    const cloneBlock = (id) => cloneBlockDeep(store.getters.getBlocks, id)

    return {
        selected,
        highlighted,
        isEdit,
        isSelected,
        isHighlighted,
        setAttributes,
        setStyles,
        insertBlock,
        insertBlockAfter,
        cloneBlock
    }
}
