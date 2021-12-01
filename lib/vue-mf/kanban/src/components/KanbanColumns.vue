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
        class="container-columns"
        chosenClass="chosen-column"
        ghostClass="ghost-column"
        dragClass="dragging-column"
        @change="handleChange"
        @start="startDragging"
        @end="endDragging"
        :forceFallback="true"
    >
        <template #item="{ element }">
            <KanbanColumn :columnId="element.id" :title="element.title">
                <KanbanCards :columnId="element.id" :cardIds="element.cards"></KanbanCards>
            </KanbanColumn>
        </template>
    </draggable>
</template>

<style lang="scss" scoped>
.container-columns {
    display: flex;
    align-items: start;
}
.dragging-column {
    cursor: pointer;
    transform: rotate(4deg);
    opacity: 1 !important;
}

.ghost-column {
    .container-cards::after {
        content: "";
        position: absolute;
        width: 100%;
        height: 100%;
        top: 0;
        background-color: #e8e9f3;
        border-radius: 8px;
    }
}
</style>
