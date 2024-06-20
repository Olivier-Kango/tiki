<!--
    Field type: a
    TODO: toolbars/wysiwyg/multilingual
-->
<template>
    <textarea v-model="model"
        class='form-control'
        :id="field.ins_id"
        :name="field.ins_id"
        :cols="field.options_array[1] > 1 ? field.options_array[1] : 50"
        :rows="field.options_array[2] > 1 ? field.options_array[2] : 6"
        :onkeyup="field.options_array[5] > 0 ? wordCount(field.options_array[5], this, `cpt_${field.fieldId}`, 'Word Limit Exceeded') : ''">
    </textarea>
    <div class="charCount" v-if="field.options_array[3]">
        Character Count: <input type="text" :id="`cpt_${field.fieldId}`" size="4" readOnly="true" value="0">
        <span v-if="field.options_array[3] > 0">Max: {{field.options_array[3]}}</span>
    </div>
    <div class="wordCount" v-if="field.options_array[5]">
        Word Count: <input type="text" :id="`cpt_${field.fieldId}`" size="4" readOnly="true" value="0">
        <span v-if="field.options_array[5] > 0">Max: {{field.options_array[5]}}</span>
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
        name: "TextArea",
    };
</script>
