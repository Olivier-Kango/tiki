<script setup>
import { ref } from 'vue'
import KanbanBoard from './components/KanbanBoard.vue'

// Sample normaliazed data (Github: https://github.com/paularmstrong/normalizr)
const rows = ref({
    byId: {
        1: {id: 1, title: 'UX', columns: [1, 2, 3]},
        2: {id: 2, title: 'UI', columns: [2, 3]},
        3: {id: 3, title: 'Branding', columns: [1, 3]}
    },
    allIds: [1, 2, 3]
})
const columns = ref({
    byId: {
        1: {id: 1, title: 'To do', cards: [1, 2, 3]},
        2: {id: 2, title: 'In progress', cards: [1, 3]},
        3: {id: 3, title: 'Done', cards: [2]}
    },
    allIds: [1, 2, 3]
})
const cards = ref({
    byId: {
        1: {id: 1, title: 'Make start button'},
        2: {id: 2, title: 'Create time tracking'},
        3: {id: 3, title: 'Rich text formatting'}
    },
    allIds: [1, 2, 3]
})
</script>

<template>
    <KanbanBoard
        :rows="rows"
        :columns="columns"
        :cards="cards"
    />
</template>
