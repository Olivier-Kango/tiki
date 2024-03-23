<!--
Type 'n': // numeric
-->
<template>
    <input v-model="numeric" placeholder="" />
</template>

<script>
    export default {
        name: "Numeric",
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