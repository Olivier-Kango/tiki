<template>
    <span :id="field.ins_id">{{output}}</span>
</template>

<script setup>
    import { computed, inject } from 'vue'
    import store from '../../store'
    const props = defineProps({
        field: {
            type: Object
        }
    })
    const output = computed(() => {
        return store.getters.renderFieldOutput(store.state.editedItem[props.field.trackerId].values, props.field)
    })
</script>

<script>
    export default {
        name: "ItemsList",
    }
</script>
