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
const pageInput = ref("");
const relationInput = ref("");

onMounted(() => {
    $(tdgLabel.value.$el)
        .parents(".modal").first()
        .on("show.bs.modal", (event) => {
            _shown(event);
            $(this).find('[data-bs-toggle="tooltip"]').tooltip();
        });
});

function _shown() {
    const $textArea = $("#" + toolbarObject.value.domElementId),
            syntax = getTASelection($textArea.get(0));

    const editor = toolbarObject.value.editor;

    let parts = syntax.match(/\((.*?)\((.*?)\|(.*?)\)\)/);
    if (!parts) {
        parts = syntax.match(/\((.*?)\((.*?)\)\)/);
    }

    if (parts) {
        labelInput.value = parts[3] ?? "";
        pageInput.value = parts[2] ?? "";
        relationInput.value = parts[1];
    } else {
        labelInput.value = syntax;
        pageInput.value = "";
        relationInput.value = "";
    }
}

function _save() {
    let output = "";
    if (pageInput.value) {
        output += "(";
        if (relationInput.value) {
            output += relationInput.value;
        }
        output += `(${pageInput.value}`;
        if (labelInput.value) {
            output += `|${labelInput.value}`;
        }
        output += "))";
        console.log("wiki link:" + output);
    }

    insertAt(toolbarObject.value.domElementId, output, false, false, true);

    return output;
}

defineExpose({ save: _save, shown: _shown });
</script>

<template>
    <DialogInput ref="tdgLabel" v-model="labelInput" label="Label" />
    <DialogInput ref="tdgPage" v-model="pageInput" label="Page" />
    <div class="input-group mr-sm-2">
        <DialogInput ref="tdgRelation" v-model="relationInput" label="Semantic Relation" />
        <div class="input-group-append" data-bs-toggle="tooltip" title="Going beyond Backlinks functionality, this allows some semantic relationships to be defined between wiki pages.">
            <div class="input-group-text">
                <span class="fa fa-circle-info"></span>
            </div>
        </div>
    </div>
</template>
