<script setup>
import DialogInput from "@/components/DialogInput.vue";

const props = defineProps({
  syntax: {
    type: String,
  },
});

let labelInput = "";
let pageInput = "";
let relationInput = "";


function _save() {
  let output = "(";
  if (pageInput) {
    if (relationInput) {
      output += relationInput;
    }
    output += `(${pageInput}`;
    if (labelInput) {
      output += `|${labelInput}`;
    }
  }
  output += "))";
  console.log("wiki link:" + output);

  return output;
}

defineExpose({save: _save});
</script>

<template>
  <DialogInput id="tdgLabel" label="Label" v-model="labelInput" />
  <DialogInput id="tdgPage" label="Page" v-model="pageInput" />
  <DialogInput id="tdgRelation" label="Semantic Relation" v-model="relationInput" />
</template>
