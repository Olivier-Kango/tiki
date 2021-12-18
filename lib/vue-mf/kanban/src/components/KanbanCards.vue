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
})

const emit = defineEmits(['editCard'])

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
        class="container-cards"
        chosenClass="chosen-card"
        ghostClass="ghost-card"
        dragClass="dragging-card"
        @change="handleChange"
        @start="startDragging"
        @end="endDragging"
        :forceFallback="true"
    >
        <template #item="{ element }">
            <KanbanCard>
                <Card @click="emit('editCard', element)">{{ element.title }}</Card>
            </KanbanCard>
        </template>
    </draggable>
</template>

<style lang="scss" scoped>
.container-cards {
    position: relative;
}

.dragging-card {
    opacity: 1 !important;

    .card {
        cursor: pointer;
        transform: rotate(4deg);
    }
}

.ghost-card {
    position: relative;

    .card {
        &::after {
            content: "";
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            background-color: #e8e9f3;
            border-radius: 8px;
        }
    }
}
</style>
