<script>
export default {
    name: 'App'
}
</script>
<script setup>
import { onBeforeMount, provide } from 'vue'
import OfflineTrackers from './components/OfflineTrackers.vue'
import store from './store'

const props = defineProps({
    trackerData: {
        type: Object,
    },
    userPrefs: {
        type: Object
    },
    dataSync: {
    }
})

onBeforeMount(() => {
    store.commit('initState', {trackerData: props.trackerData, userPrefs: props.userPrefs})
})

provide('dataSync', props.dataSync)
provide('tr', tr)

</script>

<template>
    <OfflineTrackers />
</template>
