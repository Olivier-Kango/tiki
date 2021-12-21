<script>
export default {
    name: 'KanbanColumn'
}
</script>
<script setup>
import { Dropdown } from '@vue-mf/styleguide'
import ButtonAddCard from './Buttons/ButtonAddCard.vue'
import store from '../store'

const props = defineProps({
    title: {
        type: String,
        default: ''
    },
    limit: {
        type: Number
    },
    total: {
        type: Number
    },
    columnId: {
        type: Number
    }
});

const handleTitleInput = event => {
    store.dispatch('editColumnField', {
        id: props.columnId,
        field: 'title',
        data: event.target.textContent
    })
}
</script>

<template>
    <div class="kanban-column">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <h6 class="drag-handle-column flex-grow-1 mb-0" v-if="title">
                <span class="mr-2" @input="handleTitleInput" contenteditable="true">{{title}}</span>
                <span>{{total}}/{{limit}}</span>
            </h6>
            <Dropdown class="d-inline-block ml-2" variant="default" sm>
                <template v-slot:dropdown-button>
                    <i class="fas fa-ellipsis-h"></i>
                </template>
                <template v-slot:dropdown-menu>
                    <span class="dropdown-item-text">List actions</span>
                    <div class="dropdown-divider"></div>
                </template>
            </Dropdown>
        </div>
        <slot/>
        <ButtonAddCard :columnId="columnId"></ButtonAddCard>
    </div>
</template>

<style lang="scss" scoped>
    .kanban-column {
        display: inline-block;
        min-width: 18rem;
        width: 18rem;
        padding: 10px;
        margin: 0 5px;
        background-color: rgba(243, 244, 250, 1);
        border-radius: 6px;
    }
</style>
