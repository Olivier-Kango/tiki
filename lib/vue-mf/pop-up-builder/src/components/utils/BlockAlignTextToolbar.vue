<script>
export default {
    name: 'BlockAlignTextToolbar'
}
</script>

<script setup>
import { Button, ButtonGroup, ButtonToolbar } from '@vue-mf/styleguide'

const props = defineProps({
    action: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['action'])
</script>

<template>
    <ButtonToolbar>
        <ButtonGroup class="mr-1" sm>
            <Button variant="default" @click="emit('action', { textAlign: 'left' })"><i class="fas fa-align-left"></i></Button>
            <Button variant="default" @click="emit('action', { textAlign: 'center' })"><i class="fas fa-align-center"></i></Button>
            <Button variant="default" @click="emit('action', { textAlign: 'right' })"><i class="fas fa-align-right"></i></Button>
        </ButtonGroup>
    </ButtonToolbar>
</template>
