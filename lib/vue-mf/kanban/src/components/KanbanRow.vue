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
    <div class="kanban-row">
        <div v-if="title" class="kanban-row-title">{{title}}</div>
        <slot/>
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
