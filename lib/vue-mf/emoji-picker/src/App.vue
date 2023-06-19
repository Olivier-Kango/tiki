<template>
    <EmojiPicker :toolbar-object="customProps.toolbarObject" :settings="customProps.settings"/>
</template>

<script setup>
import EmojiPicker from "./components/EmojiPicker.vue";
import {defineProps} from "vue";

const props = defineProps({
    customProps: {
        type: Object
    }
})

</script>
