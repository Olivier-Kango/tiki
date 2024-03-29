<!--
    Field type: t
-->
<template>
    <input v-model="field.ins_id" id="{{id}}" type="text" name="{{name}}" value="{{value}}" style="" class="form-control" autocomplete="off" placeholder="" />
</template>

<script>
    export default {
        name: "Text",
        props: {
            data: {
                type: Object
            },
            options: {
                type: Object
            }
        }
    };
</script>
