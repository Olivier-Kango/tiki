<template>
    <div>
        <input class="form-control" type="text" @change="_onChange" :value="value">
    </div>
</template>

<script>
    export default {
        name: 'collection-argument',
        methods: {
            _onChange({target: {value: newValue}})
            {
                this.$emit('change', newValue);
            },
        },
        props: {
            value: {
                type: null,
                required: true,
            },
        }
    }
</script>
