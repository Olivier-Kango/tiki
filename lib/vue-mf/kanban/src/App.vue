<script>
export default {
    name: 'App'
}
</script>
<script setup>
import { ref, onMounted } from 'vue'
import { Sidebar } from '@vue-mf/styleguide'
import KanbanBoard from './components/KanbanBoard.vue'
import Sidemenu from './components/Sidemenu/Sidemenu.vue'
import store from './store'

const props = defineProps({
    customProps: {
        type: Object
    }
})

onMounted(() => {
    // store.dispatch('initRows', props.customProps.kanbanData)
    store.dispatch('initCells', props.customProps.kanbanData)
    console.log(props.customProps)
})

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
