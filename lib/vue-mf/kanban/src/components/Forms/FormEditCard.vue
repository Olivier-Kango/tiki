<script>
export default {
    name: 'FormEditCard'
}
</script>
<script setup>
import { ref } from 'vue'
import { Button } from '@vue-mf/styleguide'
import store from '../../store'

const props = defineProps({
    columnId: {
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

// const handleSaveCard = () => {
//     store.dispatch('addNewCard', {
//         title: title.value,
//         columnId: props.columnId
//     })
// }
const handleTitleInput = event => {
    // TODO: Save new title
    console.log(event.target.textContent)
}
const handleEditDesc = () => {
    editDesc.value = true
}
const handleSaveDesc = () => {
    editDesc.value = false
}
const handleCancel = () => {
    editDesc.value = false
}
</script>

<template>
    <h3 @input="handleTitleInput" contenteditable="true">{{ title }}</h3>
    <p v-if="!editDesc" @click="handleEditDesc">{{ desc }}</p>
    <div v-if="editDesc">
        <textarea class="form-control mb-2" name="" id="" rows="5">{{ desc }}</textarea>
        <div>
            <Button class="d-inline-block" sm @click="handleSaveDesc">Save card</Button>
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
