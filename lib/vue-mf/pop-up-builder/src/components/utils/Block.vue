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

const handleEvent = (e, node) => store.dispatch('setSelected', node)
</script>

<template>
    <component
        :id="'block-' + node.data.id"
        :is="node.data.name"
        class="block-item"
        :block="node.data"
        @[eventType].stop="handleEvent($event, node)"
    >
        <TransitionGroup name="list">
            <Block
                v-for="node in node.children"
                :id="'block-' + node.data.id"
                :key="'block-' + node.data.id"
                :node="node"
                :edit="edit"
            />
        </TransitionGroup>
    </component>
</template>
