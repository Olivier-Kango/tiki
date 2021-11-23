<script setup>
import { ref, computed } from 'vue'
import KanbanColumn from './KanbanColumn.vue'
import KanbanCards from './KanbanCards.vue'
import draggable from 'vuedraggable/src/vuedraggable'
import store from '../store'

const props = defineProps({
    columns: {
        type: Array,
        default() {
            return []
        }
    }
});

const draggableColumns = ref(props.columns)
const dragging = ref(false)

const startDragging = () => dragging.value = true
const endDragging = () => dragging.value = false

const getCards = computed(() => ids => store.getters.getCards(ids))
</script>

<template>
    <draggable
        v-model="draggableColumns"
        group="columns"
        item-key="id"
        class="d-flex align-items-start"
        ghost-class="ghost"
        @start="startDragging"
        @end="endDragging"
    >
        <template #item="{ element }">
            <KanbanColumn :title="element.title">
                <KanbanCards :cards="getCards(element.cards)"></KanbanCards>
            </KanbanColumn>
        </template>
    </draggable>
</template>

<style lang="scss" scoped>
</style>
