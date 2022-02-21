<template>
    <div class="dp-editor--task-title__wrapper">
        <div class="dp-editor--task-title__input">
            <span>{{store.state.activeTimestamp + 1}}. </span>
            <input
                class="duration-field"
                type="text"
                placeholder="Title"
                v-on:input="handleInputTitle"
                :value="getTitle"
            >
        </div>
        <div class="dp-editor--task-title__textarea">
            <textarea
                class="duration-field"
                style="width: 100%"
                type="text"
                placeholder="Description"
                v-on:input="handleInputDescription"
                :value="getDescription"
            />
        </div>
    </div>
</template>

<script>
    import store from '../store';

    export default {
        name: "DurationPickerTitle",
        data: function () {
            return {
                store: store
            }
        },
        computed: {
            getTitle: function () {
                return store.getTimestamp(store.state.activeTimestamp).title
            },
            getDescription: function () {
                return store.getTimestamp(store.state.activeTimestamp).description
            }
        },
        methods: {
            handleInputTitle: function (e) {
                store.setTimestamp('update', {
                    ...store.getTimestamp(store.state.activeTimestamp),
                    title: e.target.value
                });
            },
            handleInputDescription: function (e) {
                store.setTimestamp('update', {
                    ...store.getTimestamp(store.state.activeTimestamp),
                    description: e.target.value
                });
            }
        }
    };
</script>