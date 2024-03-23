<!--
    Type: M
-->
<template>
    <input v-model="multipleSelection" placeholder="" />
</template>

<script>
    export default {
        name: "MultipleSelection",
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