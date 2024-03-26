<template>
    <h3>{{tracker.name}} items</h3>
    <FormAddTrackerItem :formFields=tracker.fields :trackerId=tracker.trackerId :initialValues="{}" />
    <slot></slot>
</template>

<script setup>
    import FormAddTrackerItem from "./Forms/FormAddTrackerItem.vue";
</script>

<script>
    export default {
        name: "OfflineTracker",
        components: {
            FormAddTrackerItem: FormAddTrackerItem
        },
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
