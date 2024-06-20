<!--
    Field type: n
-->
<template>
    <div class="form-check form-check-inline">
        <input v-model="model" type="checkbox"
            :id="field.ins_id"
            :name="field.ins_id"
            class="form-check-input">
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
        name: "Checkbox",
    };
</script>
