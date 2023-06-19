<template>
    <div class="row">
        <Picker
                @select="selectEmoji"
                native="true"
                :data="emojiIndex"
                :color="settings.defaultColor"
                :title="settings.title"
                :emoji="settings.emoji"
        />
    </div>
</template>

<script setup>
import {ref, defineProps, defineExpose} from "vue";
import data from "emoji-mart-vue-fast/data/all.json";
import "emoji-mart-vue-fast/css/emoji-mart.css";
import {Picker, EmojiIndex} from "emoji-mart-vue-fast/src";

const props = defineProps({
    toolbarObject: {
        type: Object,
        required: true,
    },
    settings: {
        type: Object,
        required: false,
        default: {
            defaultColor: "#f00",   // just to show the wrong settings are loading, should come from the php
            title: "a title",
            emoji: "scream",
        }
    }
});

const toolbarObject = ref(props.toolbarObject);
const settings = ref(props.settings);
const emojiPickerSelected = ref(false)

let emojiIndex = new EmojiIndex(data);
// To do: use main theme color
let defaultColor = settings.defaultColor;

let pickerTitle = settings.title;
let emoji = settings.emoji;

function selectEmoji(emoji) {
    insertAt(toolbarObject.value.domElementId, emoji.native);
    displayEmojiPicker(toolbarObject.value.pickerId, toolbarObject.value.domElementId);
}

</script>

<style scoped>
.row {
    display: flex;
}

.row > * {
    margin: auto;
}
</style>