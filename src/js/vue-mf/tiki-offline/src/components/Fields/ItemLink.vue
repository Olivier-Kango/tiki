<!--
    Field type 'r'
-->
<template>
    <select v-model="model"
        :id="field.ins_id"
        :name="field.html_name"
        class="form-select"
        :multiple="field.canHaveMultipleValues ? 'multiple' : null"
        v-select2>
        <option value="" v-if="field.isMandatory != 'y' || !model"></option>
        <option v-for="(label, value) in field.possibilities" :value="value">
            {{label}}
        </option>
    </select>
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
        name: "ItemLink",
    };
</script>
