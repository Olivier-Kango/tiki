<script>
export default {
    name: "App",
};
</script>
<script setup>
import ToolbarDialogs from "./components/ToolbarDialogs.vue";
import { ref } from "vue";

const props = defineProps({
    customProps: {
        type: Object,
        required: true,
    },
});

const toolbarObject = ref(props.customProps.toolbarObject);
const syntax = ref(props.customProps.syntax);
</script>

<template>
    <ToolbarDialogs :toolbar-object="toolbarObject" :syntax="syntax" />
</template>
