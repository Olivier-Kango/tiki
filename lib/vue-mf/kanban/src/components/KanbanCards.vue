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
import { useToast } from "vue-toastification"
import kanban from '../api/kanban'
import store from '../store'

const props = defineProps({
    cardIds: {
        type: Array,
        default() {
            return []
        }
    },
    rowId: {
        type: Number
    },
    rowValue: [Number, String],
    columnValue: [Number, String],
    columnId: {
        type: Number
    }
})

const emit = defineEmits(['editCard'])

const toast = useToast()
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
            rowId: props.rowId,
            columnId: props.columnId
        })
    } else if (event.added) {
        setItem(event.added.element.id)
        store.dispatch('addCard', {
            newIndex: event.added.newIndex,
            element: event.added.element,
            rowId: props.rowId,
            columnId: props.columnId
        })
    } else if (event.removed) {
        store.dispatch('removeCard', {
            oldIndex: event.removed.oldIndex,
            element: event.removed.element,
            rowId: props.rowId,
            columnId: props.columnId
        })
    }
}

const setItem = (itemId) => {
    kanban.setItem(
        { trackerId: store.getters.getTrackerId, itemId: itemId },
        { fields: {
                [store.getters.getSwimlaneField]: props.rowValue,
                [store.getters.getXaxisField]: props.columnValue
            },
        }
    )
    .then(res => {
        toast.success(`Success! Item moved.`)
    })
    .catch(err => {
        if (!err.response) return
        const { code, errortitle, message } = err.response.data
        const msg = `Code: ${code} - ${message}`
        toast.error(msg)
    })
}
</script>

<template>
    <draggable
        :list="getCards"
        group="cards"
        item-key="id"
        class="container-cards h-100"
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
