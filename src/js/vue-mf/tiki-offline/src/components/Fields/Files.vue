<!--
field type: FG
-->
<template>
    <div class="upload">
      <ul>
        <li v-for="file in model" :key="file.id">
          <span>{{file.name}}</span> -
          <span>{{formatSize(file.size)}}</span>
          &nbsp;
          <a href="#" class="text-danger" @click.prevent="$refs.upload.remove(file.id)"><small><i class="far fa-trash-alt"></i></small></a>
          <span v-if="file.error"> - {{file.error}}</span>
        </li>
      </ul>
      <div class="example-btn">
        <VueUploadComponent
          class="btn btn-primary"
          :accept="field.options_map.filter"
          :multiple="field.options_map.count > 1"
          :maximum="field.options_map.count"
          :value="model.value"
          @update:modelValue="inputUpdate"
          ref="upload">
          <i class="fa fa-plus"></i>
          {{ tr('Upload Files') }}
        </VueUploadComponent>
      </div>
    </div>
</template>

<script setup>
    import { inject } from 'vue'
    const model = defineModel()
    const props = defineProps({
        field: {
            type: Object
        }
    })
    const tr = inject('tr')
    const formatSize = function(size) {
        const units = ['B', 'kB', 'MB', 'GB', 'TB'];
        const exponent = Math.min(Math.floor(Math.log(size) / Math.log(1000)), units.length - 1);
        size = (size / Math.pow(1000, exponent)).toFixed(2) * 1;
        const unit = units[exponent];
        return size + ' ' + unit;
    }

    const inputUpdate = function(files) {
        let validatedFiles = []
        for (let file of files) {
            if (props.field.options_map.namefilter) {
                let regexStr = props.field.options_map.namefilter
                if (regexStr[0] == '/' && regexStr[regexStr.length - 1] == '/') {
                    regexStr = regexStr.slice(1, regexStr.length - 1)
                }
                console.log(regexStr)
                if (! file.name.match(new RegExp(regexStr))) {
                    feedback(tr(props.field.options_map.namefilterError || 'The uploaded file name doesn\'t match desired pattern.'), 'error', false, null, null, true)
                    continue
                }
            }
            let reader = new FileReader()
            reader.onload = (evt) => {
                file.data = btoa(evt.target.result)
            };
            reader.readAsBinaryString(file.file)
            validatedFiles.push(file)
        }
        model.value = validatedFiles
    }
</script>

<script>
    import VueUploadComponent from 'vue-upload-component/src/FileUpload.vue'
    export default {
        name: "Files",
        components: {
            VueUploadComponent,
        }
    }
</script>
