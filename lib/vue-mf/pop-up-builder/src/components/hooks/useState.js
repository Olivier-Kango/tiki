import { ref, computed } from 'vue'
import store from '../../store'
import {
    cloneBlockDeep,
    cloneBlockDeepBulk
} from '../../store/helpers'

export function useState(blockId) {
    const mode = ref()
    const selected = ref()
    const highlighted = ref()
    const currentBlock = ref(store.getters.getBlock(blockId) || {})

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
            value: { ...currentBlock.value.attributes, ...attributes }
        })
    }

    const setClasses = (classes) => {
        store.dispatch('updateBlock', {
            block: currentBlock.value,
            field: 'classes',
            value: { ...currentBlock.value.classes, ...classes }
        })
    }

    const setStyles = (styles) => {
        store.dispatch('updateBlock', {
            block: currentBlock.value,
            field: 'styles',
            value: { ...currentBlock.value.styles, ...styles }
        })
    }

    const removeStyles = (styleKeys) => {
        let styles = { ...currentBlock.value.styles }
        styleKeys.forEach((key) => {
            delete styles[key]
        })
        store.dispatch('updateBlock', {
            block: currentBlock.value,
            field: 'styles',
            value: { ...styles }
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
    const cloneBlockBulk = (ids) => cloneBlockDeepBulk(store.getters.getBlocks, ids)

    return {
        selected,
        highlighted,
        isSelected,
        isHighlighted,
        setAttributes,
        setClasses,
        setStyles,
        removeStyles,
        insertBlock,
        insertBlockAfter,
        cloneBlock,
        cloneBlockBulk
    }
}
