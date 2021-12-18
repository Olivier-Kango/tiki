<script>
export default {
    name: 'ButtonAddColumn'
}
</script>
<script setup>
import { ref } from 'vue'
import { Button } from '@vue-mf/styleguide'
import FormAddColumn from '../Forms/FormAddColumn.vue'

defineProps({
    rowId: {
        type: Number
    }
});

const showForm = ref(false)

const handleClose = () => {
    showForm.value = false
}

</script>

<template>
    <div class="button-container">
        <Button v-if="!showForm" variant="default" sm @click="showForm = true">
            <i class="fas fa-plus"></i>
            <span class="ml-2">Add another list</span>
        </Button>
        <FormAddColumn v-if="showForm" :rowId="rowId" @close="handleClose"></FormAddColumn>
    </div>
</template>

<style lang="scss" scoped>
    .button-container {
        width: 18rem;
        min-width: 18rem;
    }
    .btn-default {
        // background-color: rgba(228, 230, 240, 0.658);
        &:hover {
            background-color: rgba(9, 30, 66, 0.08);
        }
    }
</style>
