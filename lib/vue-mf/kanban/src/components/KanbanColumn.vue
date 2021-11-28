<script>
export default {
    name: 'KanbanColumn'
}
</script>
<script setup>
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
        <h6 v-if="title">{{title}}</h6>
        <slot/>
        <ButtonAddCard :columnId="columnId"></ButtonAddCard>
    </div>
</template>

<style lang="scss" scoped>
    .kanban-column {
        display: inline-block;
        width: 20rem;
        padding: 10px;
        margin: 0 5px;
        background-color: rgba(243, 244, 250, 1);
        border-radius: 6px;
    }
</style>
