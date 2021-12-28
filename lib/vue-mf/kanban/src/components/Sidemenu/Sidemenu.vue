<script>
export default {
    name: 'Sidemenu'
}
</script>
<script setup>
import { computed } from 'vue'
import { Button } from '@vue-mf/styleguide'
import store from '../../store'

const emit = defineEmits(['boardId'])

const boards = computed(() => store.getters.getAllBoards)
</script>

<template>
    <div class="p-2 d-flex flex-column text-truncate">
        <Button
            v-for="board in boards"
            :key="board.title"
            class="w-100 mb-1 text-left"
            variant="light"
            @click="emit('boardId', board.id)"
        >
            {{ board.title }}
        </Button>
    </div>
</template>
