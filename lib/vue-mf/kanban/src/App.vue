<script>
export default {
    name: 'App'
}
</script>
<script setup>
import { ref, computed } from 'vue'
import { Sidebar } from '@vue-mf/styleguide'
import KanbanBoard from './components/KanbanBoard.vue'
import Sidemenu from './components/Sidemenu/Sidemenu.vue'

const boardId = ref(1)

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
