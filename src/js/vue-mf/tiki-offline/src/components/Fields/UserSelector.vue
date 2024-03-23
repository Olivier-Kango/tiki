<!--
field type: u
-->
<template>
    <input v-model="userSelector" placeholder="" />
</template>

<script>
    export default {
        name: "UserSelector",
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