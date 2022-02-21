<script>
export default {
    name: 'App'
}
</script>
<script setup>
import { onBeforeMount } from 'vue'
import DurationPicker from './components/DurationPicker.vue'
import store from './store';

const props = defineProps({
    customProps: {
        type: Object
    }
})

onBeforeMount(() => {
    console.log(props.customProps.durationData)
    store.setInitialDuration(props.customProps.durationData);
    store.setInputName('ins_1');
})
</script>

<template>
    <DurationPicker />
</template>
