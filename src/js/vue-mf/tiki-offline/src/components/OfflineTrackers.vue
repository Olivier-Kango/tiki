<template>
    <div class="offline-trackers">
        <h2>Offline tracker items</h2>
        <OfflineTracker
            v-for="(tracker, index) in trackers"
            :tracker="tracker"
            :index="index"
        >
            <FormAddTrackerItem :formFields=tracker.fields :trackerId=tracker.trackerId />
            <!--Todo: Add table style in @vue-mf/styleguide-->
            <div class="table-responsive large-table-no-wrap">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th v-for="field in visibleFields(tracker.fields)">{{ field.name }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in tracker.items">
                            <!-- Todo: Show link on one of the main fields-->
                            <td v-for="field in visibleFields(tracker.fields)">{{ item[field.ins_id] }}</td>
                            <td><a href="#" @click.prevent="handleEditItem(tracker.trackerId, index)"><div class="iconmenu"><span class="icon icon-edit fa-fw fas fa-edit "></span><span class="iconmenutext"> Edit</span></div></a></td>
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
    import FormAddTrackerItem from "./Forms/FormAddTrackerItem.vue";
</script>

<script>
    export default {
        name: "OfflineTrackers",
        components: {
            OfflineTracker,
            FormAddTrackerItem,
        },
        computed: {
            trackers () {
                return store.state.trackers
            },
        },
        methods: {
            visibleFields (fields) {
                return fields.filter(field => field.isTblVisible == 'y')
            },
            handleEditItem (trackerId, index) {
                store.commit('changeEditedItem', {trackerId: trackerId, itemId: index})
                $('#formAddTrackerItemModal' + trackerId).modal('show')
            },
        },
    };
</script>
