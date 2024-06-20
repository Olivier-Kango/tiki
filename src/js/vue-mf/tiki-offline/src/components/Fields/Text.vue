<!--
    Field type: t
    TODO: multilingual
-->
<template>
    <div :class="{'input-group': field.options_map.prepend || field.options_map.append || field.options_map.labelasplaceholder}">
        <span class="input-group-text" v-if="field.options_map.prepend">{{field.options_map.prepend}}</span>
        <input v-model="model" type="text"
            :id="field.ins_id"
            :name="field.ins_id"
            class="form-control"
            :class="{labelasplaceholder: field.options_map.labelasplaceholder}"
            :placeholder="field.options_map.labelasplaceholder ? field.name : null"
            :size="field.options_map.size ? field.options_map.size : null"
            :maxlength="field.options_map.max ? field.options_map.max : null"
            autocomplete="off" />
        <span class="input-group-text" v-if="field.options_map.labelasplaceholder && field.isMandatory == 'y'">
            <strong class='mandatory_star text-danger tips' title=":This field is mandatory" style="font-size: 100%">*</strong>
        </span>
        <span class="input-group-text" v-if="field.options_map.append">{{field.options_map.append}}</span>
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
        name: "Text",
    };
</script>
