<script>
export default {
    name: 'ButtonAddCard'
}
</script>
<script setup>
import { ref } from 'vue'
import { Button } from '@vue-mf/styleguide'
import FormAddCard from '../Forms/FormAddCard.vue'

defineProps({
    columnId: {
        type: Number
    },
    rowValue: [String, Number],
    columnValue: [String, Number],
});

const showForm = ref(false)

const handleClose = () => {
    showForm.value = false
}

</script>

<template>
    <Button v-if="!showForm" class="w-100" variant="default" sm @click="showForm = true">
        <i class="fas fa-plus"></i>
        <span class="ml-2">Add a card</span>
    </Button>
    <FormAddCard v-if="showForm" :columnId="columnId" :rowValue="rowValue" :columnValue="columnValue"  @close="handleClose"></FormAddCard>
</template>

<style lang="scss" scoped>
    .btn-default {
        // background-color: rgba(228, 230, 240, 0.658);
        &:hover {
            background-color: rgba(9, 30, 66, 0.08);
        }
    }
</style>
