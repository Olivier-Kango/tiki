<script>
export default {
    name: 'App'
}
</script>
<script setup>
import { ref, onBeforeMount } from 'vue'
import { Sidebar } from '@vue-mf/styleguide'
import KanbanBoard from './components/KanbanBoard.vue'
import Sidemenu from './components/Sidemenu/Sidemenu.vue'
import store from './store'

const props = defineProps({
    customProps: {
        type: Object
    }
})

onBeforeMount(() => {
    // Sample data
    // props.customProps.kanbanData.rows = [
    //     {id: 1, title: 'UX'},
    //     {id: 2, title: 'Google Code-in Tasks'},
    //     {id: 3, title: 'Design'}
    // ]
    // props.customProps.kanbanData.columns = [
    //     {id: 1, title: 'To Do', limit: 15},
    //     {id: 2, title: 'In progress', limit: 10},
    //     {id: 3, title: 'Test', limit: 100},
    //     {id: 4, title: 'Done', limit: 100}
    // ]
    // props.customProps.kanbanData.cards = [
    //     {id: 1, column: 1, row: 1, sortOrder: 1, title: 'Make start button', description: 'Some card description'},
    //     {id: 2, column: 1, row: 1, sortOrder: 3, title: 'Create time tracking', description: 'Some card description'},
    //     {id: 3, column: 1, row: 1, sortOrder: 2, title: 'Rich text formatting', description: 'Some card description'},
    //     {id: 4, column: 1, row: 2, sortOrder: 3, title: 'Add feature to Maps application', description: 'Some card description'},
    //     {id: 5, column: 1, row: 2, sortOrder: 1, title: 'Create a new activity for Sugarizer', description: 'Some card description'},
    //     {id: 6, column: 1, row: 2, sortOrder: 2, title: 'Agora-web Display election detail during voting', description: 'Some card description'}
    // ]
    store.dispatch('initKanban', props.customProps.kanbanData)
    // console.log(props.customProps.kanbanData)
})

const boardId = ref(props.customProps.kanbanData.trackerId)

const setBoardId = (id) => {
    boardId.value = id
}
</script>

<template>
    <Sidebar>
        <template v-slot:sidebar-content>
            <Sidemenu @boardId="setBoardId"/>
        </template>
        <KanbanBoard :id="boardId" />
    </Sidebar>
</template>
