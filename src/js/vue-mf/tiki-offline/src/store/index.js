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
            state.trackers.forEach(tracker => tracker.items = []);
        },
        createItem(state, data) {
            state.trackers.forEach(tracker => {
                if (tracker.trackerId == data.trackerId) {
                    tracker.items.push(data.fieldValues);
                }
            });
        }
    }
});
