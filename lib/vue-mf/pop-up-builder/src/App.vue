<script>
export default {
    name: 'App'
}
</script>
<script setup>
import { ref, onBeforeMount } from 'vue'
// import store from './store'

const props = defineProps({
    customProps: {
        type: Object
    }
})

onBeforeMount(() => {
    console.log(props.customProps.data)
})
</script>

<template>
    <h1>Hello pop-up-builder</h1>
</template>
