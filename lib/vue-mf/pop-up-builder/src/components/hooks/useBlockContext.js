import { ref, computed, inject, onMounted } from 'vue'
import { useState } from '../hooks/useState'

export function useBlockContext(block) {
    const context = inject('context')
    const { removeAttributes } = useState(block.id)

    onMounted(() => {
        if (context?.providerBlockId !== block.attributes.context?.providerBlockId) {
            // Cleans previous context data if current context is from different block
            removeAttributes(['context'])
        }
    })

    return {
        context
    }
}
