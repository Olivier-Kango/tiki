<script>
export default {
    name: 'KanbanBoard'
}
</script>
<script setup>
import { computed } from 'vue'
import KanbanRow from './KanbanRow.vue'
import KanbanColumns from './KanbanColumns.vue'
import ButtonAddColumn from './Buttons/ButtonAddColumn.vue'
import store from '../store'

const props = defineProps({
    id: {
        type: Number
    }
});

const board = computed(() => store.getters.getBoard(props.id))
</script>

<template>
    <div class="kanban-container">
        <nav class="navbar navbar-light bg-light rounded-lg mx-2">
            <a class="navbar-brand" href="#">{{ board.title }}</a>
        </nav>
        <KanbanRow v-for="row in store.getters.getRows(board.rows)" :title="row.title" :rowId="row.id">
            <KanbanColumns :rowId="row.id" :columnIds="row.columns"></KanbanColumns>
            <ButtonAddColumn :rowId="row.id"></ButtonAddColumn>
        </KanbanRow>
    </div>
</template>

<style lang="scss" scoped>
</style>
