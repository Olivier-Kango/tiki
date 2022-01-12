<script>
export default {
    name: 'KanbanColumn'
}
</script>
<script setup>
import { ref } from 'vue'
import { Field } from 'vee-validate'
import { useToast } from "vue-toastification"
import { Dropdown } from '@vue-mf/styleguide'
import ButtonAddCard from './Buttons/ButtonAddCard.vue'
import store from '../store'

const props = defineProps({
    title: {
        type: String,
        default: ''
    },
    limit: {
        type: Number
    },
    total: {
        type: Number
    },
    colId: {
        type: Number
    },
    columnId: {
        type: Number
    },
    columnHeader: {
        type: Boolean,
        default: false
    }
});

const showEditField = ref(false)
const toast = useToast()

const handleTitleBlur = event => {
    showEditField.value = false

    if (event.target.value.length < 1) {
        toast.error(`This field must be at least 1 character`)
        return
    }

    store.dispatch('editColumnField', {
        id: props.colId,
        field: 'title',
        data: event.target.value
    })
}

const handleEditClick = event => {
    showEditField.value = true
}
</script>

<template>
    <div class="kanban-column">
        <div v-if="columnHeader" class="d-flex justify-content-between align-items-center mb-1">
            <h6 :class="{'drag-handle-column': !showEditField}" class="d-flex flex-grow-1 mb-0" v-if="title">
                <span v-if="!showEditField" class="mr-2 flex-grow-1" @click="handleEditClick">{{ title }}</span>
                <Field
                    class="flex-grow-1 mr-1"
                    v-if="showEditField"
                    v-focus
                    v-autosize
                    as="textarea"
                    rows="1"
                    :value="title"
                    @blur="handleTitleBlur"
                    name="rowTitle"
                    type="text"
                    :rules="{ minLength: 1 }"
                />
                <span>{{ total }}/{{ limit }}</span>
            </h6>
            <Dropdown class="d-inline-block ml-2" variant="default" sm>
                <template v-slot:dropdown-button>
                    <i class="fas fa-ellipsis-h"></i>
                </template>
                <template v-slot:dropdown-menu>
                    <div class="px-2">
                        <span class="dropdown-item-text">List actions</span>
                        <div class="dropdown-divider"></div>
                    </div>
                </template>
            </Dropdown>
        </div>
        <div class="flex-grow-1">
            <slot />
        </div>
        <div>
            <ButtonAddCard :columnId="columnId"></ButtonAddCard>
        </div>
    </div>
</template>

<style lang="scss" scoped>
.kanban-column {
    display: flex;
    flex-direction: column;
    min-width: 18rem;
    width: 18rem;
    padding: 10px;
    margin: 0 5px;
    background-color: rgba(243, 244, 250, 1);
    border-radius: 6px;
}
</style>
