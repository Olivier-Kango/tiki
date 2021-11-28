<script>
export default {
    name: 'KanbanColumns'
}
</script>
<script setup>
import { ref, computed } from 'vue'
import KanbanColumn from './KanbanColumn.vue'
import KanbanCards from './KanbanCards.vue'
import draggable from 'vuedraggable/src/vuedraggable'
import store from '../store'

const props = defineProps({
    columnIds: {
        type: Array,
        default() {
            return []
        }
    },
    rowId: {
        type: Number
    }
});

const dragging = ref(false)

const getColumns = computed(() => store.getters.getColumns(props.columnIds))

const startDragging = () => dragging.value = true
const endDragging = () => dragging.value = false

const handleChange = (event) => {
    if (event.moved) {
        store.dispatch('moveColumn', {
            oldIndex: event.moved.oldIndex,
            newIndex: event.moved.newIndex,
            element: event.moved.element,
            rowId: props.rowId
        })
    } else if (event.added) {
        store.dispatch('addColumn', {
            newIndex: event.added.newIndex,
            element: event.added.element,
            rowId: props.rowId
        })
    } else if (event.removed) {
        store.dispatch('removeColumn', {
            oldIndex: event.removed.oldIndex,
            element: event.removed.element,
            rowId: props.rowId
        })
    }
}
</script>

<template>
    <draggable
        :list="getColumns"
        group="columns"
        item-key="id"
        class="d-flex align-items-start"
        ghost-class="ghost"
        @change="handleChange"
        @start="startDragging"
        @end="endDragging"
    >
        <template #item="{ element }">
            <KanbanColumn :columnId="element.id" :title="element.title">
                <KanbanCards :columnId="element.id" :cardIds="element.cards" ></KanbanCards>
            </KanbanColumn>
        </template>
    </draggable>
</template>

<style lang="scss" scoped>
</style>
