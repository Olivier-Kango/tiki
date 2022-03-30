<script>
export default {
    name: 'App'
}
</script>
<script setup>
import { onBeforeMount } from 'vue'
import BlockEditor from './components/utils/BlockEditor.vue'

const props = defineProps({
    customProps: {
        type: Object
    }
})

onBeforeMount(() => {
    console.log('pop-up-builder initial data', props.customProps.data)
})
</script>

<template>
    <BlockEditor></BlockEditor>
</template>
