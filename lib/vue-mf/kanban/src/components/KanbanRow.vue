<script>
export default {
    name: 'KanbanRow'
}
</script>
<script setup>
import store from '../store'

const props = defineProps({
    title: {
        type: String,
        default: ''
    },
    rowId: {
        type: Number
    }
});

const handleTitleInput = event => {
    store.dispatch('editRowField', {
        id: props.rowId,
        field: 'title',
        data: event.target.textContent
    })
}
</script>

<template>
    <div class="kanban-row">
        <div v-if="title" class="kanban-row-title">
            <span @input="handleTitleInput" contenteditable="true">{{title}}</span>
        </div>
        <PerfectScrollbar>
            <div class="d-flex">
                <slot/>
            </div>
        </PerfectScrollbar>
    </div>
</template>

<style lang="scss" scoped>
.kanban-row {
    display: flex;
    align-items: flex-start;
    flex-wrap: wrap;
    margin-bottom: 10px;
    .kanban-row-title {
        width: 100%;
        margin: 5px;
        background-color: #f3f4fa;
        border-radius: 6px;
        text-align: center;
    }
}

</style>
