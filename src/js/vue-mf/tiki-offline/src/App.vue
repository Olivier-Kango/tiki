<script>
export default {
    name: 'App'
}
</script>
<script setup>
import { onBeforeMount } from 'vue'
import OfflineTrackers from './components/OfflineTrackers.vue'
import store from './store'

const props = defineProps({
    trackerData: {
        type: Object,
    }
})

onBeforeMount(() => {
    store.commit('initState', {trackerData: props.trackerData})
})
</script>

<template>
    <OfflineTrackers />
</template>
