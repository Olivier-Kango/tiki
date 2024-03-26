import { createStore } from "vuex";

export default createStore({
    strict: import.meta.env.MODE !== "production",
    state: {
        trackers: [],
    },
    getters: {
    },
    actions: {
    },
    mutations: {
        initState(state, data) {
            state.trackers = data.trackerData;
            state.trackers.each(tracker => tracker.items = []);
        },
        createItem(state, trackerId, fieldValues) {
            state.trackers.each(tracker => {
                if (tracker.trackerId == trackerId) {
                    tracker.items.push(fieldValues);
                }
            });
        }
    }
});
