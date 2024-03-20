<template>
    <h3>{{tracker.name}} items</h3>
    <slot></slot>
</template>

<script>
    export default {
        name: "OfflineTracker",
        props: {
            tracker: {
                type: Object
            },
            index: {
                type: Number
            },
            items:  {
                type: Object
            }
        }
    };
</script>
