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
import ButtonAddColumn from './Buttons/ButtonAddColumn.vue'
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
    <div class="kanban-container" :style="{backgroundImage: board.imageUrl ? `url(${board.imageUrl})` : 'none'}">
        <nav class="navbar navbar-light rounded-lg" :class="{'bg-color-transparent': board.imageUrl, 'bg-light': !board.imageUrl}">
            <div @click="handleEditClick">
                <span v-if="!showEditField" style="font-size: 1.15rem">{{ board.title }}</span>
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
            </div>
            <Button sm variant="light" @click="handleAddRow">Add swimlane<i class="fas fa-plus ml-1"></i></Button>
        </nav>
        <KanbanRow
            v-for="(row, index) in store.getters.getRows(board.rows)"
            :key="row.title"
            :title="row.title"
            :transparentTitleBg="board.imageUrl ? true : false"
            :boardId="id"
            :rowId="row.id"
            :index="index"
        >
            <KanbanColumns :rowId="row.id" :rowIndex="index" :columnIds="row.columns"></KanbanColumns>
            <ButtonAddColumn v-if="index === 0" :rowId="row.id"></ButtonAddColumn>
        </KanbanRow>
    </div>
</template>

<style lang="scss" scoped>
.kanban-container {
    padding-top: 5px;
    background-size: cover;
}
.navbar {
    padding: 0rem 1rem;
    margin: 0 5px;
}
</style>
