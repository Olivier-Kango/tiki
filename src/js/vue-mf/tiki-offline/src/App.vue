<script>
export default {
    name: 'App'
}
</script>
<script setup>
import { provide } from 'vue'
import OfflineTrackers from './components/OfflineTrackers.vue'
//import createStore from './store';

const props = defineProps({
    trackerData: {
        type: Object,
    }
})

//provide('store', createStore());
</script>

<template>
    <OfflineTrackers :trackerData="trackerData" />
</template>
