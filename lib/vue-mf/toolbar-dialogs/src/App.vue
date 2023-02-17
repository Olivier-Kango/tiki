<script>
export default {
  name: "App",
};
</script>
<script setup>
import ToolbarDialogs from "./components/ToolbarDialogs.vue";
import {ref} from "vue";

const props = defineProps({
  customProps: {
    type: Object
  }
});

// console.log("toolbarObject: " + props.customProps.toolbarObject);
const toolbarObject = ref(props.customProps.toolbarObject);

</script>

<template>
  <ToolbarDialogs :toolbarObject="toolbarObject" :syntax="syntax"/>
</template>
