<script>
export default {
    name: 'FormEditCard'
}
</script>
<script setup>
import { ref, watchEffect } from 'vue'
import { Form, Field } from 'vee-validate'
import { useToast } from "vue-toastification"
import { Button } from '@vue-mf/styleguide'
import store from '../../store'
import autosize from 'autosize'

const props = defineProps({
    id: {
        type: Number
    },
    title: {
        type: String
    },
    desc: {
        type: String,
        default: ''
    }
})

const showEditField = ref(false)
const toast = useToast()
const editDesc = ref(false)
const description = ref('')
const textarea = ref(null)

watchEffect(() => {
    description.value = props.desc
    autosize(textarea.value)
})

const handleTitleChange = event => {
    showEditField.value = false

    if (event.target.value.length < 1) {
        toast.error(`This field must be at least 1 character`)
        return
    }

    store.dispatch('editCardField', {
        id: props.id,
        field: 'title',
        data: event.target.value
    })
}

const handleEditClick = event => {
    showEditField.value = true
}

const handleDescriptionInput = event => {
    description.value = event.target.value
}
const handleEditDesc = () => {
    editDesc.value = true
}
const handleSaveDesc = () => {
    store.dispatch('editCardField', {
        id: props.id,
        field: 'desc',
        data: description.value
    })
    editDesc.value = false
}
const handleCancel = () => {
    editDesc.value = false
}
</script>

<template>
    <h4>
        <div v-if="!showEditField" @click="handleEditClick">{{ title }}</div>
        <Form v-if="showEditField">
            <Field
                class="w-100"
                v-focus
                :value="title"
                @blur="handleTitleChange"
                name="rowTitle"
                type="text"
                :rules="{ minLength: 1 }"
            />
        </Form>
    </h4>
    <h6>Description</h6>
    <p v-if="!editDesc" @click="handleEditDesc">
        <div v-if="description.length === 0" @click="handleEditDesc">Click to add description...</div>
        {{ description }}
    </p>
    <div v-if="editDesc">
        <textarea ref="textarea" @input="handleDescriptionInput" class="form-control mb-2" name="" id="">{{ description }}</textarea>
        <div>
            <Button class="d-inline-block" sm @click="handleSaveDesc">Save</Button>
            <Button class="d-inline-block ml-2" variant="default" sm @click="handleCancel">
                <i class="fas fa-times"></i>
            </Button>
        </div>
    </div>
</template>

<style lang="scss" scoped>
.btn-default {
    // background-color: rgba(228, 230, 240, 0.658);
    &:hover {
        background-color: rgba(9, 30, 66, 0.08);
    }
}
</style>
