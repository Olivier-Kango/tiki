<template>
    <button class="btn btn-outline-secondary btn-predicate-add" type="button">{{ isInAddCompoundMode ? '…' : '+'  }}</button>
</template>

<script>
export default {
    name: 'PredicateAdd',
    props: {
        isInAddCompoundMode: {
            type: Boolean,
            default: false,
        },
    }
}
</script>