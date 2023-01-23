<script setup>
/*
 * Wrapper for bootstrap 5 Modals
 * Largely from https://stackoverflow.com/a/71461086/2459703
 */

import {Modal} from "bootstrap";
import {onMounted, ref} from "vue";

defineProps({
  title: {
    type: String,
    default: "<<Title goes here>>",
  },
});
let modalEle = ref(null);
let thisModalObj = null;

onMounted(() => {
  thisModalObj = new Modal(modalEle.value);
});

function _show() {
  thisModalObj.show();
}

defineExpose({show: _show});
</script>

<template>
  <div class="modal modal-sm fade" id="exampleModal" tabIndex="-1" aria-labelledby="" aria-hidden="true" ref="modalEle">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h6 class="modal-title" id="exampleModalLabel">{{ title }}</h6>
          <button type="button" class="btn btn-close btn-sm" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <slot name="body"/>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
            Close
          </button>
          <slot name="footer"></slot>
        </div>
      </div>
    </div>
  </div>
</template>
