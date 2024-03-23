<template>
    <input v-model="itemLinkValue" placeholder="" />
</template>

<script>
    export default {
        name: "ItemList",
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