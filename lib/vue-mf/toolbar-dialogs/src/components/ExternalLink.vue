<script setup>
import DialogInput from "@/components/DialogInput.vue";

const props = defineProps({
  syntax: {
    type: String,
  },
});

let labelInput = "";
let urlInput = "";
let relationInput = "";

defineExpose({save: _save});

function _save() {
  let output = "[";
  if (urlInput) {
    output += urlInput;
    if (labelInput) {
      output += `|${labelInput}`;
      if (relationInput) {
        output += `|${relationInput}`;
      }
    }
  }
  output += "]";
  console.log("external link:" + output);

  return output;
}


</script>

<template>
  <DialogInput id="tdgLabel" label="Label" v-model="labelInput" />
  <DialogInput id="tdgUrl" label="URL" v-model="urlInput" />
  <DialogInput id="tdgRelation" label="Relation" v-model="relationInput" />
</template>
