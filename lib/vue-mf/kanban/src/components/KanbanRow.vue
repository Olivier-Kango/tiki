<script>
export default {
    name: 'KanbanRow'
}
</script>
<script setup>
import { ref } from 'vue'
import { Form, Field } from 'vee-validate'
import { useToast } from "vue-toastification"
import store from '../store'

const props = defineProps({
    title: {
        type: String,
        default: ''
    },
    rowId: {
        type: Number
    }
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
</script>

<template>
    <div class="kanban-row">
        <div class="kanban-row-title">
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
        width: 100%;
        margin: 5px;
        background-color: #f3f4fa;
        border-radius: 6px;
        text-align: center;
    }
}
</style>
