<!--
    Field type: t
-->
<template>
    <input v-model="text" placeholder="" />
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