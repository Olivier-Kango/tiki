<script>
export default {
    name: 'Block'
}
</script>

<script setup>
import { computed } from 'vue'
import store from '../../store'

const props = defineProps({
    id: String,
    node: Object,
    edit: Boolean
});

const eventType = computed(() => props.edit ? 'click' : null)

const isHighlighted = computed(() => (node) => {
    if (!store.getters.getHighlighted) return false
    if (node.data.id === store.getters.getHighlighted.data.id) return true
})

const isSelected = computed(() => (node) => {
    if (!store.getters.getSelected) return false
    if (node.data.id === store.getters.getSelected.data.id) return true
})

const handleEvent = (e, node) => store.dispatch('setSelected', node)
</script>

<template>
    <component
        :id="'block-' + node.data.id"
        :is="node.data.name"
        class="block-item"
        :block="node.data"
        :class="{'is-highlighted': isHighlighted(node)}"
        @[eventType].stop="handleEvent($event, node)"
    >
        <Block
            v-for="node in node.children"
            :class="{'is-highlighted': isHighlighted(node)}"
            :node="node"
            :id="'block-' + node.data.id"
            :key="'block-' + node.data.id"
            :edit="edit"
        />
    </component>
</template>
