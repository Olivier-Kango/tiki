<script>
export default {
    name: 'Block'
}
</script>

<script setup>
import { computed } from 'vue'
import store from '../../store'

defineProps({
    block: {
        type: Object,
        default: () => {}
    },
    context: {
        type: Object,
        default: () => null
    }
})

const getBlock = computed(() => (id) => store.getters.getBlock(id))
</script>

<template>
    <div
        :id="'block-' + block.id"
        :class="block.classes"
        class="block-editor-block"
        :style="block.styles"
        :data-block="block.id"
    >
        <component
            :is="block.name"
            :block="block"
            :context="context"
        >
            <Block
                v-for="_id in block.innerBlocks"
                :key="'block-' + _id"
                :block="getBlock(_id)"
                :context="context"
            />
        </component>
    </div>
</template>
