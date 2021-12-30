<script>
export default {
    name: 'KanbanBoard'
}
</script>
<script setup>
import { ref, computed } from 'vue'
import { Field } from 'vee-validate'
import { useToast } from "vue-toastification"
import { Button } from '@vue-mf/styleguide'
import KanbanRow from './KanbanRow.vue'
import KanbanColumns from './KanbanColumns.vue'
import store from '../store'

const props = defineProps({
    id: {
        type: Number
    }
});

const board = computed(() => store.getters.getBoard(props.id))

const showEditField = ref(false)
const toast = useToast()

const handleTitleBlur = event => {
    showEditField.value = false

    if (event.target.value.length < 1) {
        toast.error(`This field must be at least 1 character`)
        return
    }

    store.dispatch('editBoardField', {
        id: props.id,
        field: 'title',
        data: event.target.value
    })
}

const handleEditClick = event => {
    showEditField.value = true
}

const handleAddRow = event => {
    store.dispatch('addRow', {
        boardId: props.id,
        title: 'New swimlane'
    })
}
</script>

<template>
    <div class="kanban-container">
        <nav class="navbar navbar-light bg-light rounded-lg mx-2">
            <a class="navbar-brand" href="#" @click="handleEditClick">
                <span v-if="!showEditField">{{ board.title }}</span>
                <Field
                    v-if="showEditField"
                    v-focus
                    v-autosize
                    as="textarea"
                    rows="1"
                    :value="board.title"
                    @blur="handleTitleBlur"
                    name="rowTitle"
                    type="text"
                    :rules="{ minLength: 1 }"
                />
            </a>
            <Button sm variant="light" @click="handleAddRow">Add swimlane<i class="fas fa-plus ml-1"></i></Button>
        </nav>
        <KanbanRow
            v-for="(row, index) in store.getters.getRows(board.rows)"
            :key="row.title"
            :title="row.title"
            :boardId="id"
            :rowId="row.id"
            :index="index"
        >
            <KanbanColumns :rowId="row.id" :columnIds="row.columns"></KanbanColumns>
        </KanbanRow>
    </div>
</template>

<style lang="scss" scoped>
</style>
