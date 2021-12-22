<script>
export default {
    name: 'KanbanColumns'
}
</script>
<script setup>
import { ref, computed } from 'vue'
import { Dropdown, Button } from '@vue-mf/styleguide'
import KanbanColumn from './KanbanColumn.vue'
import KanbanCards from './KanbanCards.vue'
import FormEditCard from './Forms/FormEditCard.vue'
import draggable from 'vuedraggable/src/vuedraggable'
import { VueFinalModal } from 'vue-final-modal'
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
const showModal = ref(false)
const card = ref(false)
const date = ref(false)

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

const handleEditCard = (element) => {
    card.value = element
    showModal.value = true
}
const handleClickOutside = () => {
    showModal.value = false
}
const handleModalClosed = () => {
    showModal.value = false
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
        handle=".drag-handle-column"
        @change="handleChange"
        @start="startDragging"
        @end="endDragging"
        :forceFallback="true"
    >
        <template #item="{ element }">
            <KanbanColumn :columnId="element.id" :title="element.title" :limit="element.limit" :total="element.cards.length">
                <KanbanCards :columnId="element.id" :cardIds="element.cards" @editCard="handleEditCard"></KanbanCards>
            </KanbanColumn>
        </template>
    </draggable>
    <vue-final-modal
        v-model="showModal"
        classes="f-modal-container"
        content-class="f-modal-content"
        @click-outside="handleClickOutside"
        @closed="handleModalClosed"
    >
        <div v-if="showModal" class="d-flex">
            <div class="w-75">
                <FormEditCard :id="card.id" :title="card.title" :desc="card.desc"></FormEditCard>
            </div>
            <div class="w-25">
                <div>
                    <Dropdown class="d-block ml-2" variant="default" sm>
                        <template v-slot:dropdown-button>
                            <i class="far fa-calendar-alt mr-2"></i>Date picker
                        </template>
                        <template v-slot:dropdown-menu>
                            <div class="p-2">
                                <DatePicker class="mb-2" v-model="date" />
                                <Button sm>Save</Button>
                                <Button variant="default" sm>Cancel</Button>
                            </div>
                        </template>
                    </Dropdown>
                </div>
            </div>
        </div>
    </vue-final-modal>
</template>

<style lang="scss" scoped>
.container-columns {
    display: flex;
    align-items: start;
    margin-bottom: 20px;
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

:deep(.f-modal-container) {
  display: flex;
  justify-content: center;
  align-items: center;
}
:deep(.f-modal-content) {
  display: flex;
  flex-direction: column;
  max-width: 960px;
  width: 100%;
  margin: 0 1rem;
  padding: 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.25rem;
  background: #fff;
}
.modal__title {
  font-size: 1.5rem;
  font-weight: 700;
}
</style>
