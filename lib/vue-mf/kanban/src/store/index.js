import { createStore } from 'vuex';
import logger from "./logger";

const debug = import.meta.env.MODE !== 'production';

export default createStore({
    modules: {
        // dynamic modules
    },
    strict: debug,
    plugins: debug ? [logger] : [],
    state: {
        // Sample normaliazed data (Github: https://github.com/paularmstrong/normalizr)
        rows: {
            byId: {
                1: {id: 1, title: 'UX', columns: [1, 2, 3]},
                2: {id: 2, title: 'Google Code-in Tasks', columns: [4, 5, 6]}
            },
            allIds: [1, 2]
        },
        columns: {
            byId: {
                1: {id: 1, title: 'To do', cards: [1, 2, 3]},
                2: {id: 2, title: 'In progress', cards: []},
                3: {id: 3, title: 'Done', cards: []},
                4: {id: 4, title: 'To do', cards: [4, 5, 6]},
                5: {id: 5, title: 'In progress', cards: []},
                6: {id: 6, title: 'Done', cards: []}
            },
            allIds: [1, 2, 3, 4, 5, 6]
        },
        cards: {
            byId: {
                1: {id: 1, title: 'Make start button'},
                2: {id: 2, title: 'Create time tracking'},
                3: {id: 3, title: 'Rich text formatting'},
                4: {id: 4, title: 'Add feature to Maps application'},
                5: {id: 5, title: 'Create a new activity for Sugarizer'},
                6: {id: 6, title: 'Agora-web Display election detail during voting'}
            },
            allIds: [1, 2, 3, 4, 5, 6]
        }
    },
    getters: {
        getAllRows(state) {
            return state.rows.allIds.map(id => state.rows.byId[id])
        },
        getColumns(state) {
            return (ids) => ids.map(id => state.columns.byId[id])
        },
        getCards(state) {
            return (ids) => ids.map(id => state.cards.byId[id])
        }
    },
    actions: {
        // TODO
        // updateColumnCards({ commit }, cards) {
        //     commit('setColumnCards', cards);
        // },
    },
    mutations: {
        // TODO
        // setColumnCards(state, cards) {
        //     state.columns = cards;
        // },
    }
});
