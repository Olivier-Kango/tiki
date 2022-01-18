<script>
export default {
    name: 'FormAddCard'
}
</script>
<script setup>
import { ref, watchEffect } from 'vue'
import { Button, Card } from '@vue-mf/styleguide'
import KanbanCard from '../KanbanCard.vue'
import { useToast } from "vue-toastification"
import autosize from 'autosize'
import kanban from '../../api/kanban'
import store from '../../store'

const props = defineProps({
    columnId: {
        type: Number
    },
    rowValue: [String, Number],
    columnValue: [String, Number],
})
const emit = defineEmits(['close'])

const toast = useToast()
const trackerId = ref(store.getters.getTrackerId)
const title = ref('')
const textarea = ref(null)

watchEffect(() => {
    autosize(textarea.value)
})

const handleAddCard = () => {
    kanban.createItem(
        { trackerId: trackerId.value },
        { fields: {
                [store.getters.getTitleField]: title.value,
                [store.getters.getSwimlaneField]: props.rowValue,
                [store.getters.getXaxisField]: props.columnValue
            },
        }
    )
        .then(res => {
            toast.success(`Success! Item created.`)
        })
        .catch(err => {
            const { code, errortitle, message } = err.response.data
            const msg = `Code: ${code} - ${message}`
            toast.error(msg)
        })

    store.dispatch('addNewCard', {
        title: title.value,
        columnId: props.columnId
    })
    emit('close')
}
</script>

<template>
    <KanbanCard>
        <Card>
            <textarea
                ref="textarea"
                v-model="title"
                class="form-control"
                rows="3"
                placeholder="Enter a title for this card..."
            >{{ title }}</textarea>
        </Card>
    </KanbanCard>
    <Button sm @click="handleAddCard">Add card</Button>
    <Button class="ml-2" variant="default" sm @click="$emit('close')">
        <i class="fas fa-times"></i>
    </Button>
</template>

<style lang="scss" scoped>
:deep(.card-body) {
    padding: 0;
}
.btn-default {
    // background-color: rgba(228, 230, 240, 0.658);
    &:hover {
        background-color: rgba(9, 30, 66, 0.08);
    }
}
</style>
