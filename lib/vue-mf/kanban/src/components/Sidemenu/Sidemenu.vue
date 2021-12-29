<script>
export default {
    name: 'Sidemenu'
}
</script>
<script setup>
import { ref, computed } from 'vue'
import { Field } from 'vee-validate'
import { Button, Dropdown } from '@vue-mf/styleguide'
import store from '../../store'

const emit = defineEmits(['boardId'])

const title = ref('New board')

const boards = computed(() => store.getters.getAllBoards)

const handleAddBoard = event => {
    store.dispatch('addBoard', {
        title: title.value
    })
}
</script>

<template>
    <div class="p-2 d-flex flex-column">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <p class="mb-0">Your boards</p>
            <Dropdown class="d-inline-block ml-2" variant="default" sm>
                <template v-slot:dropdown-button>
                    <i class="fas fa-plus"></i>
                </template>
                <template v-slot:dropdown-menu>
                    <div class="px-2">
                        <span class="dropdown-item-text">Create board</span>
                        <div class="dropdown-divider"></div>
                        <Field
                            class="form-control mb-2"
                            v-focus
                            v-model="title"
                            name="boardTitle"
                            type="text"
                            :rules="{ minLength: 1 }"
                        />

                        <Button class="w-100" @click="handleAddBoard">Create</Button>
                    </div>
                </template>
            </Dropdown>
        </div>
        <Button
            v-for="board in boards"
            :key="board.title"
            class="w-100 mb-1 text-left"
            variant="light"
            @click="emit('boardId', board.id)"
        >{{ board.title }}</Button>
    </div>
</template>
