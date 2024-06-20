<!--
    Field type 'R' (radio buttons)
-->
<template>
    <div class="form-check form-check-inline" v-for="(label, value) in field.possibilities">
        <input v-model="model"
            class="form-check-input"
            type="radio"
            :name="field.ins_id"
            :id="`${field.ins_id}-${value}`"
            :value="value">
        <label class="form-check-label" :for="`${field.ins_id}-${value}`">
            {{label}}
        </label>
    </div>
</template>

<script setup>
    const model = defineModel()
    const props = defineProps({
        field: {
            type: Object
        }
    })
</script>

<script>
    export default {
        name: "Radios",
    };
</script>
