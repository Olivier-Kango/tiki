import { createStore } from "vuex";

export default createStore({
    strict: import.meta.env.MODE !== "production",
    state: {
        trackers: [],
        editedItem: {
            trackerId: null,
            itemId: null,
            values: {},
        },
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
        storeEditedItem(state) {
            state.trackers.forEach(tracker => {
                if (tracker.trackerId == state.editedItem.trackerId) {
                    if (state.editedItem.itemId === null) {
                        tracker.items.push(Object.assign({}, state.editedItem.values));
                    } else {
                        tracker.items[state.editedItem.itemId] = Object.assign({}, state.editedItem.values);
                    }
                }
            });
        },
        changeEditedItem(state, data) {
            if (! data) {
                state.editedItem.trackerId = null;
                state.editedItem.itemId = null;
                state.editedItem.values = {};
                return;
            }
            state.editedItem.trackerId = data.trackerId;
            state.editedItem.itemId = data.itemId;
            state.editedItem.values = {};
            if (data.trackerId !== null && data.itemId !== null) {
                for (let tracker of state.trackers) {
                    if (tracker.trackerId == data.trackerId) {
                        state.editedItem.values = Object.assign({}, tracker.items[data.itemId])
                        return;
                    }
                }
            }
        },
    }
});
