

<script>
    import { inject } from 'vue';

    export default {
        name: "DurationPickerTitle",
        data: function () {
            return {
                store: inject('store')
            }
        },
        computed: {
            getTitle: function () {
                return this.store.getTimestamp(this.store.state.activeTimestamp).title
            },
            getDescription: function () {
                return this.store.getTimestamp(this.store.state.activeTimestamp).description
            }
        },
        methods: {
            handleInputTitle: function (e) {
                this.store.setTimestamp('update', {
                    ...this.store.getTimestamp(this.store.state.activeTimestamp),
                    title: e.target.value
                });
            },
            handleInputDescription: function (e) {
                this.store.setTimestamp('update', {
                    ...this.store.getTimestamp(this.store.state.activeTimestamp),
                    description: e.target.value
                });
            }
        }
    };
</script>