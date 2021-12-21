import { createStore } from 'vuex'
import logger from "./logger"

const debug = import.meta.env.MODE !== 'production'

function arrayMove(arr, fromIndex, toIndex) {
    let element = arr[fromIndex]
    arr.splice(fromIndex, 1)
    arr.splice(toIndex, 0, element)
}

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
                1: {id: 1, title: 'To do', cards: [1, 2, 3], limit: 10},
                2: {id: 2, title: 'In progress', cards: [], limit: 5},
                3: {id: 3, title: 'Done', cards: [], limit: 50},
                4: {id: 4, title: 'To do', cards: [4, 5, 6], limit: 10},
                5: {id: 5, title: 'In progress', cards: [], limit: 5},
                6: {id: 6, title: 'Done', cards: [], limit: 50}
            },
            allIds: [1, 2, 3, 4, 5, 6]
        },
        cards: {
            byId: {
                1: {id: 1, title: 'Make start button', desc: 'Some card description'},
                2: {id: 2, title: 'Create time tracking', desc: 'Some card description'},
                3: {id: 3, title: 'Rich text formatting', desc: 'Some card description'},
                4: {id: 4, title: 'Add feature to Maps application', desc: 'Some card description'},
                5: {id: 5, title: 'Create a new activity for Sugarizer', desc: 'Some card description'},
                6: {id: 6, title: 'Agora-web Display election detail during voting', desc: 'Some card description'}
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
        moveColumn({ commit }, moved) {
            commit('moveColumn', moved)
        },
        addColumn({ commit }, added) {
            commit('addColumn', added)
        },
        addNewColumn({ commit }, data) {
            commit('addNewColumn', data)
        },
        removeColumn({ commit }, removed) {
            commit('removeColumn', removed)
        },
        moveCard({ commit }, moved) {
            commit('moveCard', moved)
        },
        addCard({ commit }, added) {
            commit('addCard', added)
        },
        addNewCard({ commit }, data) {
            commit('addNewCard', data)
        },
        removeCard({ commit }, removed) {
            commit('removeCard', removed)
        },
        editRowField({ commit }, {id, field, data}) {
            commit('editRowField', {id, field, data})
        },
        editColumnField({ commit }, {id, field, data}) {
            commit('editColumnField', {id, field, data})
        },
        editCardField({ commit }, {id, field, data}) {
            commit('editCardField', {id, field, data})
        }
    },
    mutations: {
        moveColumn(state, data) {
            arrayMove(state.rows.byId[data.rowId].columns, data.oldIndex, data.newIndex)
        },
        addColumn(state, data) {
            state.rows.byId[data.rowId].columns.splice(data.newIndex, 0, data.element.id)
        },
        addNewColumn(state, data) {
            let newId = Math.max(...state.columns.allIds) + 1
            state.columns.allIds.push(newId)
            state.columns.byId[newId] = { id: newId, title: data.title, cards: [], limit: 10 }
            state.rows.byId[data.rowId].columns.push(newId)
        },
        removeColumn(state, data) {
            state.rows.byId[data.rowId].columns.splice(data.oldIndex, 1)
        },
        moveCard(state, data) {
            arrayMove(state.columns.byId[data.columnId].cards, data.oldIndex, data.newIndex)
        },
        addCard(state, data) {
            state.columns.byId[data.columnId].cards.splice(data.newIndex, 0, data.element.id)
        },
        addNewCard(state, data) {
            let newId = Math.max(...state.cards.allIds) + 1
            state.cards.allIds.push(newId)
            state.cards.byId[newId] = { id: newId, title: data.title }
            state.columns.byId[data.columnId].cards.push(newId)
        },
        removeCard(state, data) {
            state.columns.byId[data.columnId].cards.splice(data.oldIndex, 1)
        },
        editRowField(state, {id, field, data}) {
            state.rows.byId[id][field] = data
        },
        editColumnField(state, {id, field, data}) {
            state.columns.byId[id][field] = data
        },
        editCardField(state, {id, field, data}) {
            state.cards.byId[id][field] = data
        }
    }
});
