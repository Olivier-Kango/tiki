<script setup>
import { computed } from 'vue'
import KanbanRow from './KanbanRow.vue'
import KanbanColumns from './KanbanColumns.vue'
import store from '../store'

const getAllRows = computed(() => store.getters.getAllRows)
const getColumns = computed(() => ids => store.getters.getColumns(ids))
</script>

<template>
    <KanbanRow v-for="row in getAllRows" :title="row.title">
        <KanbanColumns :columns="getColumns(row.columns)"></KanbanColumns>
    </KanbanRow>
</template>

<style lang="scss" scoped>
</style>
