<script setup>
import { ref } from 'vue'
import KanbanCard from './KanbanCard.vue'
import { Card } from '@vue-mf/styleguide'
import draggable from 'vuedraggable/src/vuedraggable'

const props = defineProps({
    cards: {
        type: Array,
        default() {
            return []
        }
    }
});

const draggableCards = ref(props.cards)
const dragging = ref(false)

const startDragging = () => dragging.value = true
const endDragging = () => dragging.value = false
</script>

<template>
    <draggable
        v-model="draggableCards"
        group="people"
        item-key="id"
        class="list-group"
        ghost-class="ghost"
        @start="startDragging"
        @end="endDragging"
    >
        <template #item="{ element }">
            <KanbanCard>
                <Card>{{ element.title }}</Card>
            </KanbanCard>
        </template>
    </draggable>
</template>

<style lang="scss" scoped>
</style>
