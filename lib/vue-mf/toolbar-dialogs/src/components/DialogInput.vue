<template>
  <input
      class="form-control form-control-sm mb-2"
      type="text"
      :ref="id"
      :placeholder="label"
      v-model="inputValue"
  >
</template>

<script setup>
// value of the input
let	inputValue = "";

const props = defineProps({
  id: {
    type: String,
    required: true,
  },
  label: {
    type: String,
    required: true,
  },
  value: {
    type: String,
  },
});

</script>
