<script>
export default {
    name: 'VueRichTextToolbar'
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
            <Button variant="secondary" @click="emit('action', 'strong')"><i class="fas fa-bold"></i></Button>
            <Button variant="secondary" @click="emit('action', 'em')"><i class="fas fa-italic"></i></Button>
        </ButtonGroup>
        <ButtonGroup class="mr-1" sm>
            <Button variant="secondary" @click="emit('action', 'sub')"><i class="fas fa-subscript"></i></Button>
            <Button variant="secondary" @click="emit('action', 'sup')"><i class="fas fa-superscript"></i></Button>
        </ButtonGroup>
        <ButtonGroup sm>
            <Button variant="secondary" @click="emit('action', 'h1')"><b>H1</b></Button>
            <Button variant="secondary" @click="emit('action', 'h2')"><b>H2</b></Button>
            <Button variant="secondary" @click="emit('action', 'h3')"><b>H3</b></Button>
        </ButtonGroup>
    </ButtonToolbar>
</template>

<style lang="scss">
</style>
