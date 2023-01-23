<script setup>
import Modal from "@/components/Modal.vue";
import {ref} from "vue";
import ExternalLink from "@/components/ExternalLink.vue";
import WikiLink from "@/components/WikiLink.vue";

// hopefully these refs cold be automated somehow
const extModal = ref(null);
const wikiModal = ref(null);
const extLink = ref(null);
const wikiLink = ref(null);

function showModal(type) {
  switch (type) {
    case "wiki":
      wikiModal.value.show();
      break;
    case "ext":
      extModal.value.show();
      break;
  }
}

function extSave(e) {
  return extLink.value.save();
}
function wikiSave(e) {
  return wikiLink.value.save();
}
</script>


<template>
  <button @click="showModal('ext')" class="btn btn-sm btn-secondary me-2">Show Ext</button>
  <button @click="showModal('wiki')" class="btn btn-sm btn-secondary me-2">Show Wiki</button>
  <Modal title="External Link" ref="extModal">
    <template #body><ExternalLink ref="extLink" /></template>
    <template #footer><button @click="extSave()" class="btn btn-primary btn-sm">Apply</button></template>
  </Modal>

  <Modal title="Wiki Link" ref="wikiModal">
    <template #body><WikiLink ref="wikiLink" /></template>
    <template #footer><button  @click="wikiSave()" class="btn btn-primary btn-sm">Apply</button></template>
  </Modal>
</template>

<style scoped>

</style>
