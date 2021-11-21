<script setup>
import { computed } from 'vue'
import KanbanRow from './KanbanRow.vue'
import KanbanColumn from './KanbanColumn.vue'
import KanbanCards from './KanbanCards.vue'
import { Card } from '@vue-mf/styleguide'

const props = defineProps({
    rows: {
        type: Object,
        default() {
            return {}
        }
    },
    columns: {
        type: Object,
        default() {
            return {}
        }
    },
    cards: {
        type: Object,
        default() {
            return {}
        }
    }
});

const getAllRows = computed(() => props.rows.allIds.map(id => props.rows.byId[id]))
const getColumns = computed(() => ids => ids.map(id => props.columns.byId[id]))
const getCards = computed(() => ids => ids.map(id => props.cards.byId[id]))
</script>

<template>
    <KanbanRow v-for="row in getAllRows" :title="row.title">
        <KanbanColumn v-for="column in getColumns(row.columns)" :title="column.title">
            <KanbanCards :cards="getCards(column.cards)"></KanbanCards>
        </KanbanColumn>
    </KanbanRow>
</template>

<style lang="scss" scoped>
</style>
