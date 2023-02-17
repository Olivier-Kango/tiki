<script setup>
import DialogInput from "./DialogInput.vue";
import { onMounted, defineProps, defineExpose, ref } from "vue";

const props = defineProps({
    toolbarObject: {
        type: Object,
        required: true,
    },
    syntax: {
        type: String,
        default: "",
    },
});

const tdgLabel = ref();
const toolbarObject = ref(props.toolbarObject);

const labelInput = ref("");
const urlInput = ref("");
const relationInput = ref("");

onMounted(() => {
    $(tdgLabel.value.$el)
        .parents(".modal:first")
        .on("show.bs.modal", (event) => {
            _shown(event);
        });
});

function _shown() {
    const $textArea = $("#" + toolbarObject.value.domElementId),
            syntax = getTASelection($textArea.get(0));


    let parts;

    if (! toolbarObject.value.editor.isMarkdown) {
        parts = syntax.match(/\[(.*?)\|(.*?)\|(.*?)]/);
        if (!parts) {
            parts = syntax.match(/\[(.*?)\|(.*?)]/);
        }
        if (!parts) {
            parts = syntax.match(/\[(.*?)]/);
        }
    } else {
        parts = syntax.match(/\[(.*?)]\((.*?)\)/);
        if (parts) {
            const label = parts[1];
            parts[1] = parts[2];
            parts[2] = label;
        }
    }

    if (parts) {
        urlInput.value = parts[1];
        labelInput.value = parts[2] ?? "";
        relationInput.value = parts[3] ?? "";
    } else {
        urlInput.value = "";
        labelInput.value = syntax;
        relationInput.value = "";
    }
}

function _save() {
    let output = "";
    if (! toolbarObject.value.editor.isMarkdown) {
        if (urlInput.value) {
            output = "[";
            output += urlInput.value;
            if (labelInput.value) {
                output += `|${labelInput.value}`;
                if (relationInput.value) {
                    output += `|${relationInput.value}`;
                }
                output += "]";
                console.log("external link:" + output);
            }
        }
    } else {    // markdown
        if (toolbarObject.value.editor.isWysiwyg) {
            output = {linkText: labelInput.value, linkUrl: urlInput.value};
            const foo = tuiEditors[toolbarObject.value.domElementId].exec("addLink", output);
            return;
        } else {
            output = `[${labelInput.value}](${urlInput.value})`;
        }
    }
    insertAt(toolbarObject.value.domElementId, output, false, false, true);

    return output;
}

defineExpose({ save: _save, shown: _shown });
</script>

<template>
    <DialogInput ref="tdgLabel" v-model="labelInput" label="Label" />
    <DialogInput ref="tdgUrl" v-model="urlInput" label="URL" />
    <DialogInput ref="tdgRelation" v-model="relationInput" label="Relation" v-if="! toolbarObject.editor.isMarkdown" />
</template>
