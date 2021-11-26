<script>
export default {
    name: 'KanbanCards'
}
</script>
<script setup>
import { ref, computed } from 'vue'
import KanbanCard from './KanbanCard.vue'
import { Card } from '@vue-mf/styleguide'
import draggable from 'vuedraggable/src/vuedraggable'
import store from '../store'

const props = defineProps({
    cardIds: {
        type: Array,
        default() {
            return []
        }
    },
    columnId: {
        type: Number
    }
});

const dragging = ref(false)

const getCards = computed(() => store.getters.getCards(props.cardIds))

const startDragging = () => dragging.value = true
const endDragging = () => dragging.value = false

const handleChange = (event) => {
    if (event.moved) {
        store.dispatch('moveCard', {
            oldIndex: event.moved.oldIndex,
            newIndex: event.moved.newIndex,
            element: event.moved.element,
            columnId: props.columnId
        })
    } else if (event.added) {
        store.dispatch('addCard', {
            newIndex: event.added.newIndex,
            element: event.added.element,
            columnId: props.columnId
        })
    } else if (event.removed) {
        store.dispatch('removeCard', {
            oldIndex: event.removed.oldIndex,
            element: event.removed.element,
            columnId: props.columnId
        })
    }
}
</script>

<template>
    <draggable
        :list="getCards"
        group="cards"
        item-key="id"
        class="list-group"
        ghost-class="ghost"
        @change="handleChange"
        @start="startDragging"
        @end="endDragging"
    >
        <template #item="{ element }">
            <KanbanCard>
                <Card>{{ element.title }}</Card>
            </KanbanCard>
        </template>
    </draggable>
</template>

<style lang="scss" scoped>
</style>
