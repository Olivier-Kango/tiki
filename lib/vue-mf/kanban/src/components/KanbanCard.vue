<script>
export default {
    name: 'KanbanCard'
}
</script>
<script setup>

</script>

<template>
    <div class="kanban-card">
        <slot/>
    </div>
</template>

<style lang="scss">
    .kanban-card {
        margin-bottom: 10px;

        .card {
            cursor: pointer;
            border: none;
            border-radius: 8px;
            box-shadow: 0px 3px 20px -12px #b5b6ba;
        }
    }
</style>
