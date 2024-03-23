<!--
    Field type 'D': // drop down + other
-->
<template>
    <!-- Todo: See Dropdown.tpl -->
    <div class="form-check form-check-inline">
        <select name="" multiple="multiple" class="form-select">
            <option v-for="option in options" :value="option">{{option}}</option>
        </select>
    </div>
    
</template>

<script>
    export default {
        name: "Dropdown",
        props: {
            data: {
                type: Object
            },
            options: {
                type: Object
            },
        },
        methods: {
            
        }
    };
</script>