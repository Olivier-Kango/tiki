<script>
export default {
    name: 'KanbanColumn'
}
</script>
<script setup>
import { Button } from '@vue-mf/styleguide'
import ButtonAddCard from './Buttons/ButtonAddCard.vue'

defineProps({
    title: {
        type: String,
        default: ''
    },
    columnId: {
        type: Number
    }
});
</script>

<template>
    <div class="kanban-column">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <h6 class="drag-handle-column flex-grow-1 mb-0" v-if="title">{{title}}</h6>
            <Button class="d-inline-block ml-2" variant="default" sm>
                <i class="fas fa-ellipsis-h"></i>
            </Button>
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
