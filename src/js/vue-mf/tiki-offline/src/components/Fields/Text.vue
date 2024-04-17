<!--
    Field type: t
-->
<template>
    <input v-model="model" type="text" :id="ins_id" :name="ins_id" class="form-control" autocomplete="off" placeholder="" />
</template>

<script setup>
    const model = defineModel()
</script>

<script>
    export default {
        name: "Text",
        props: {
            ins_id: {
                type: String
            }
        }
    };
</script>
