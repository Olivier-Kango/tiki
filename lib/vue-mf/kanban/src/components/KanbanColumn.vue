<script>
export default {
    name: 'KanbanColumn'
}
</script>
<script setup>
import { ref } from 'vue'

defineProps({
    title: {
        type: String,
        default: ''
    }
});
</script>

<template>
    <div class="kanban-column">
        <h6 v-if="title">{{title}}</h6>
        <slot/>
    </div>
</template>

<style lang="scss" scoped>
    .kanban-column {
        display: inline-block;
        width: 20rem;
        padding: 10px;
        margin: 0 5px;
        background-color: #f3f4fa;
        border-radius: 6px;
    }
</style>
