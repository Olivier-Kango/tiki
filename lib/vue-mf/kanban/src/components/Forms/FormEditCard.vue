<script>
export default {
    name: 'FormEditCard'
}
</script>
<script setup>
import { ref, watchEffect } from 'vue'
import { Field } from 'vee-validate'
import { useToast } from "vue-toastification"
import { Button } from '@vue-mf/styleguide'
import kanban from '../../api/kanban'
import store from '../../store'

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

const trackerId = ref(store.getters.getTrackerId)
const showEditField = ref(false)
const toast = useToast()
const editDesc = ref(false)
const description = ref('')
const textarea = ref(null)

watchEffect(() => {
    description.value = props.desc
})

const handleTitleBlur = event => {
    showEditField.value = false

    if (event.target.value.length < 1) {
        toast.error(`This field must be at least 1 character`)
        return
    }

    kanban.setItem({ trackerId: trackerId.value, itemId: props.id }, { title: event.target.value })
        .then(res => {
            toast.success(`Success! Title saved.`)
        })
        .catch(err => {
            const { code, errortitle, message } = err.response.data
            const msg = `Code: ${code} - ${message}`
            toast.error(msg)
        })

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
    kanban.setItem({ trackerId: trackerId.value, itemId: props.id }, { description: description.value })
        .then(res => {
            toast.success(`Success! Description saved.`)
        })
        .catch(err => {
            toast.error(`Error: can't save item!`)
        })
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
        <Field
            class="w-100"
            v-if="showEditField"
            v-focus
            v-autosize
            as="textarea"
            rows="1"
            :value="title"
            @blur="handleTitleBlur"
            name="cardTitle"
            type="text"
            :rules="{ minLength: 1 }"
        />
    </h4>
    <h6>Description</h6>
    <p v-if="!editDesc" @click="handleEditDesc">
        <div v-if="description.length === 0" @click="handleEditDesc">Click to add description...</div>
        {{ description }}
    </p>
    <div v-if="editDesc">
        <textarea v-autosize v-focus @input="handleDescriptionInput" class="form-control mb-2" name="" id="">{{ description }}</textarea>
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
