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
    cellIds: {
        type: Array,
        default() {
            return []
        }
    },
    rowId: {
        type: Number
    },
    rowValue: [Number, String],
    rowIndex: {
        type: Number
    }
});

const dragging = ref(false)
const showModal = ref(false)
const card = ref(false)
const date = ref(false)

const getCells = computed(() => store.getters.getCells(props.cellIds))
const getCols = computed(() => store.getters.getCols)

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
        store.dispatch('addCell', {
            newIndex: event.added.newIndex,
            element: event.added.element,
            rowId: props.rowId
        })
    } else if (event.removed) {
        store.dispatch('removeCell', {
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
        :list="getCells"
        group="cells"
        item-key="id"
        class="container-cells"
        chosenClass="chosen-cell"
        ghostClass="ghost-cell"
        dragClass="dragging-cell"
        handle=".drag-handle-cell"
        @change="handleChange"
        @start="startDragging"
        @end="endDragging"
        :forceFallback="true"
        :animation="200"
    >
        <template #item="{ element, index }">
            <KanbanColumn :rowIndex="rowIndex" :colIndex="index" :cellId="element.id" :columnValue="getCols[index].value" :rowValue="rowValue" :colId="getCols[index].id" :title="getCols[index].title" :limit="getCols[index].wip" :total="store.getters.getCardsByCol(index).length">
                <PerfectScrollbar class="d-flex h-100">
                    <KanbanCards :cellId="element.id" :rowId="rowId" :columnValue="getCols[index].value" :rowValue="rowValue" :cardIds="element.cards" @editCard="handleEditCard"></KanbanCards>
                </PerfectScrollbar>
            </KanbanColumn>
        </template>
    </draggable>
    <vue-final-modal
        v-model="showModal"
        classes="f-modal-container"
        content-class="f-modal-content"
        :drag="false"
        :resize="false"
        :fit-parent="false"
        @click-outside="handleClickOutside"
        @closed="handleModalClosed"
    >
        <div v-if="showModal" class="d-flex">
            <Button class="f-modal-close" variant="default" @click="showModal = false">
                <i class="fas fa-times"></i>
            </Button>
            <div class="w-100">
                <FormEditCard :id="card.id" :title="card.title" :desc="card.description" :reference="`tiki-view_tracker_item.php?itemId=${card.id}`"></FormEditCard>
            </div>
            <div v-if="false" class="w-25">
                <div>
                    <Dropdown class="d-block ml-2" variant="default" sm>
                        <template v-slot:dropdown-button>
                            <i class="far fa-calendar-alt mr-2"></i>Date picker
                        </template>
                        <template v-slot:dropdown-menu>
                            <div class="px-2" style="min-width: 270px">
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
.container-cells {
    display: flex;
    // align-items: start;
    margin-bottom: 20px;
}
.dragging-cell {
    cursor: pointer;
    transform: rotate(4deg);
    opacity: 1 !important;
}

.ghost-cell {
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
    // display: flex;
    // justify-content: center;
    // align-items: center;
    width: 100%;
    overflow-x: hidden;
    overflow-y: auto;

    .f-modal-close {
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
    }
}
:deep(.f-modal-content) {
    position: relative;
    display: flex;
    flex-direction: column;
    max-width: 960px;
    width: 100%;
    min-height: 75%;
    margin: 48px auto 80px auto;
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
