<script>
export default {
    name: 'BlocksStoreView'
}
</script>

<script setup>
import { computed } from 'vue'
import store from '../../../store'

const props = defineProps({
    field: String
})

const getAPIdata = computed(() => store.getters[`module_${props.field}/get_${props.field}`])
</script>

<template>
    <div>
        <b><u>{{ field }}</u></b>
        <pre>{{ JSON.stringify(Array.isArray(getAPIdata) && getAPIdata[0], undefined, 4) }}</pre>
    </div>
</template>
