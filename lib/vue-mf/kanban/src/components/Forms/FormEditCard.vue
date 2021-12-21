<script>
export default {
    name: 'FormEditCard'
}
</script>
<script setup>
import { ref, watchEffect } from 'vue'
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

const editDesc = ref(false)
const description = ref('')
const textarea = ref(null)

watchEffect(() => {
    description.value = props.desc
    autosize(textarea.value)
})

const handleTitleInput = event => {
    store.dispatch('editCardField', {
        id: props.id,
        field: 'title',
        data: event.target.textContent
    })
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
    <h4><span @input="handleTitleInput" contenteditable="true">{{ title }}</span></h4>
    <h6>Description</h6>
    <p v-if="!editDesc" @click="handleEditDesc">{{ description }}</p>
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
