<script>
export default {
    name: 'FormAddCard'
}
</script>
<script setup>
import { ref } from 'vue'
import { Button, Card } from '@vue-mf/styleguide'
import KanbanCard from '../KanbanCard.vue'
import store from '../../store'

const props = defineProps({
    columnId: {
        type: Number
    }
})
const emit = defineEmits(['close'])

const title = ref('')

const handleAddCard = () => {
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
