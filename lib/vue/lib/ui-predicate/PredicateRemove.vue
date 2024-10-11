<template>
    <button class="btn btn-outline-secondary btn-predicate-remove" type="button" :disabled="disabled">-</button>
</template>

<script>
export default {
    name: 'PredicateRemove',
    props: {
        disabled: {
            type: Boolean,
            default: false,
        },
    }
}
</script>