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

const getAllRows = computed(() => store.getters.getAllRows)
</script>

<template>
    <KanbanRow v-for="row in getAllRows" :title="row.title" :rowId="row.id">
        <KanbanColumns :rowId="row.id" :columnIds="row.columns"></KanbanColumns>
        <ButtonAddColumn :rowId="row.id"></ButtonAddColumn>
    </KanbanRow>
</template>

<style lang="scss" scoped>
</style>
