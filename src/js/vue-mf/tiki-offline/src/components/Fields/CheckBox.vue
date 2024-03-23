<!-- 
    Tracker field type: c
    Checkbox
 -->
<template>
    <div class="form-check form-check-inline">
    <input type="checkbox" class="form-check-input" name=""  checked="checked">
</div>
</template>

<script>
    export default {
        name: "CheckBox",
        props: {
            data: {
                type: Object
            },
            options: {
                type: Object
            }
        },
        methods: {
            
        }
    };
</script>