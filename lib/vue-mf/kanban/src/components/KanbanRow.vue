<script>
export default {
    name: 'KanbanRow'
}
</script>
<script setup>
import { ref } from 'vue'
import { Button } from '@vue-mf/styleguide'
import { Form, Field } from 'vee-validate'
import { useToast } from "vue-toastification"
import store from '../store'

const props = defineProps({
    title: {
        type: String,
        default: ''
    },
    transparentTitleBg: {
        type: Boolean,
        default: false
    },
    boardId: {
        type: Number
    },
    rowId: {
        type: Number
    },
    index: {
        type: Number
    },
});

const showEditField = ref(false)
const toast = useToast()

const handleTitleChange = event => {
    showEditField.value = false

    if (event.target.value.length < 1) {
        toast.error(`This field must be at least 1 character`)
        return
    }

    store.dispatch('editRowField', {
        id: props.rowId,
        field: 'title',
        data: event.target.value
    })
}

const handleEditClick = event => {
    showEditField.value = true
}

const handleMoveDown = event => {
    store.dispatch('moveRowForth', {
        boardId: props.boardId,
        oldIndex: props.index
    })
}

const handleMoveUp = event => {
    store.dispatch('moveRowBack', {
        boardId: props.boardId,
        oldIndex: props.index
    })
}
</script>

<template>
    <div class="kanban-row">
        <div class="kanban-row-title" :class="{'bg-color-transparent': transparentTitleBg}">
            <span v-if="!showEditField" @click="handleEditClick">{{ title }}</span>
            <Form v-if="showEditField">
                <Field
                    v-focus
                    :value="title"
                    @blur="handleTitleChange"
                    name="rowTitle"
                    type="text"
                    :rules="{ minLength: 1 }"
                />
            </Form>
            <div class="kanban-row-controls">
                <Button class="mr-1 py-0 px-1" sm variant="light" @click="handleMoveDown"><i class="fas fa-chevron-down"></i></Button>
                <Button class="py-0 px-1" sm variant="light" @click="handleMoveUp"><i class="fas fa-chevron-up"></i></Button>
            </div>
        </div>
        <PerfectScrollbar>
            <div class="d-flex">
                <slot />
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
        position: relative;
        width: 100%;
        margin: 5px;
        background-color: #f3f4fa;
        border-radius: 6px;
        font-weight: 500;
        text-align: center;

        &:hover {
            .kanban-row-controls {
                display: block;
            }
        }

        .kanban-row-controls {
            display: none;
            position: absolute;
            top: 0;
            right: 0;

            button {
                vertical-align: baseline;
            }
        }
    }
}
</style>
