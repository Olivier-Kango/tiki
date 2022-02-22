<script>
export default {
    name: 'App'
}
</script>
<script setup>
import { onBeforeMount, inject } from 'vue'
import DurationPicker from './components/DurationPicker.vue'

const props = defineProps({
    customProps: {
        type: Object
    }
})

const store = inject('store')

onBeforeMount(() => {
    store.setInitialDuration(props.customProps.durationData);
})
</script>

<template>
    <DurationPicker />
</template>
