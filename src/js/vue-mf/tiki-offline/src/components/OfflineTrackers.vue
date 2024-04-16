<template>
    <div class="offline-trackers">
        <h2>Offline tracker items</h2>
        <OfflineTracker
            v-for="(tracker, index) in trackers"
            :tracker="tracker"
            :index="index"
        >
            <!--Todo: Add table style in @vue-mf/styleguide-->
            <div class="table-responsive large-table-no-wrap">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th v-for="field in visibleFields(tracker.fields)">{{ field.name }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in tracker.items">
                            <!-- Todo: Show link on one of the main fields-->
                            <td v-for="field in visibleFields(tracker.fields)">{{ item[field.ins_id] }}</td>
                        </tr> 
                    </tbody>
                </table>
            </div>
        </OfflineTracker>
    </div>
</template>

<script setup>
    import store from '../store'
    import OfflineTracker from "./OfflineTracker.vue";
</script>

<script>
    export default {
        name: "OfflineTrackers",
        components: {
            OfflineTracker: OfflineTracker,
        },
        computed: {
            trackers () {
                return store.state.trackers
            },
        },
        methods: {
            visibleFields (fields) {
                return fields.filter(field => field.isTblVisible == 'y')
            }
        },
    };
</script>
