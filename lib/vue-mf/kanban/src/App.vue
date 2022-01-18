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
    store.dispatch('initBoard', props.customProps.kanbanData)
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
