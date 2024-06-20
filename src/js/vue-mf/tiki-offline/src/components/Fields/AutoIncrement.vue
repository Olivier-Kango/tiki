<!--
    Field type: q
-->
<template>
    <div :class="{'input-group': field.options_map.prepend || field.options_map.append}">
        <span class="input-group-text" v-if="field.options_map.prepend">{{field.options_map.prepend}}</span>
        <input v-model="model" v-if="model.value"
            type="text"
            :id="field.ins_id"
            :name="field.ins_id"
            class="form-control"
            disabled="disabled" />
        <span v-else>{{ tr('(automatically generated after item creation)') }}</span>
        <span class="input-group-text" v-if="field.options_map.append">{{field.options_map.append}}</span>
    </div>
</template>

<script setup>
    import { inject } from 'vue'

    const model = defineModel()
    const props = defineProps({
        field: {
            type: Object
        }
    })

    const tr = inject('tr')
</script>

<script>
    export default {
        name: "AutoIncrement",
    };
</script>
