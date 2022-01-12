import { createStore } from 'vuex'
import logger from "./logger"

const debug = import.meta.env.MODE !== 'production'

export default createStore({
    modules: {
        // dynamic modules
    },
    strict: debug,
    plugins: debug ? [logger] : [],
    state: {
        // Sample normaliazed data (Github: https://github.com/paularmstrong/normalizr)
        boards: {
            byId: {
                1: {id: 1, title: 'Agile Board', rows: [1, 2, 3], imageUrl: 'https://www.teahub.io/photos/full/90-908127_1920-x-1080-landscape..jpg'},
                2: {id: 2, title: 'Design Huddle', rows: [1]},
                3: {id: 3, title: 'Company overview', rows: [1, 2]}
            },
            allIds: [1, 2, 3]
        },
        rows: {
            byId: {
                1: {id: 1, title: 'UX', columns: [1, 2, 3, 4]},
                2: {id: 2, title: 'Google Code-in Tasks', columns: [5, 6, 7, 8]},
                3: {id: 3, title: 'Design', columns: [9, 10, 11, 12]}
            },
            allIds: [1, 2, 3]
        },
        cols: {
            byId: {
                1: {id: 1, title: 'To Do', limit: 15},
                2: {id: 2, title: 'In progress', limit: 10},
                3: {id: 3, title: 'Test', limit: 100},
                4: {id: 4, title: 'Done', limit: 100}
            },
            allIds: [1, 2, 3, 4]
        },
        columns: { // cells
            byId: {
                1: {id: 1, title: 'To do', cards: [], limit: 10},
                2: {id: 2, title: 'In progress', cards: [], limit: 5},
                3: {id: 3, title: 'Done', cards: [], limit: 50},
                4: {id: 4, title: 'To do', cards: [], limit: 10},
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
        getAllBoards(state) {
            return state.boards.allIds.map(id => state.boards.byId[id])
        },
        getBoard(state) {
            return id => state.boards.byId[id]
        },
        getAllRows(state) {
            return state.rows.allIds.map(id => state.rows.byId[id])
        },
        getRows(state) {
            return ids => ids.map(id => state.rows.byId[id])
        },
        getCols(state) {
            return state.cols.allIds.map(id => state.cols.byId[id])
        },
        getColumns(state) {
            return ids => ids.map(id => state.columns.byId[id])
        },
        getCards(state) {
            return ids => ids.map(id => state.cards.byId[id])
        },
    },
    actions: {
        initRows({ commit }, data) {
            commit('setRows', {rows: [1,1,1]})
        },
        initCells({ commit }, data) {
            commit('setCells', {rows: [1,1,1], cols: [1,1,1,1]})
        },
        addBoard({ commit }, added) {
            commit('addBoard', added)
        },
        addRow({ commit }, added) {
            commit('addRow', added)
        },
        moveRowBack({ commit }, moved) {
            commit('moveRowBack', moved)
        },
        moveRowForth({ commit }, moved) {
            commit('moveRowForth', moved)
        },
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
        editBoardField({ commit }, {id, field, data}) {
            commit('editBoardField', {id, field, data})
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
        setRows(state, data) {
            let { rows, allIds } = makeRows(data);
            state.rows.byId = rows;
            state.rows.allIds = allIds;
        },
        setCells(state, data) {
            let { cells, allIds } = makeCells(data);
            state.columns.byId = cells;
            state.columns.allIds = allIds;
        },
        addBoard(state, data) {
            // Add new board
            let newId = Math.max(...state.boards.allIds) + 1
            let newRowId = Math.max(...state.rows.allIds) + 1
            state.boards.allIds.push(newId)
            state.boards.byId[newId] = { id: newId, title: data.title, rows: [newRowId] }
            // Add new row
            state.rows.allIds.push(newRowId)
            state.rows.byId[newRowId] = { id: newRowId, title: 'New swimlane', columns: [] }
        },
        addRow(state, data) {
            // let newRowId = Math.max(...state.rows.allIds) + 1
            // state.rows.allIds.push(newRowId)
            // state.rows.byId[newRowId] = {id: newRowId, title: data.title, columns: []}
            // state.boards.byId[data.boardId].rows.push(newRowId)

            // Adds row
            let newRowId = Math.max(...state.rows.allIds) + 1
            state.rows.allIds.push(newRowId)
            state.rows.byId[newRowId] = {id: newRowId, title: data.title, columns: []}
            state.boards.byId[data.boardId].rows.push(newRowId)
            // Adds columns
            state.cols.allIds.forEach(id => {
                let newId = Math.max(...state.columns.allIds) + 1
                state.columns.allIds.push(newId)
                state.columns.byId[newId] = { id: newId, title: 'New col', cards: [], limit: 50 }
                state.rows.byId[newRowId].columns.push(newId)
            })
        },
        moveRowBack(state, data) {
            arrayMove(state.boards.byId[data.boardId].rows, data.oldIndex, data.oldIndex - 1)
        },
        moveRowForth(state, data) {
            arrayMove(state.boards.byId[data.boardId].rows, data.oldIndex, data.oldIndex + 1)
        },
        moveColumn(state, data) {
            // arrayMove(state.rows.byId[data.rowId].columns, data.oldIndex, data.newIndex)
            state.rows.allIds.forEach(rowId => {
                arrayMove(state.rows.byId[rowId].columns, data.oldIndex, data.newIndex)
            })
            // Sync cols
            arrayMove(state.cols.allIds, data.oldIndex, data.newIndex)
        },
        addColumn(state, data) {
            state.rows.byId[data.rowId].columns.splice(data.newIndex, 0, data.element.id)
        },
        addNewColumn(state, data) {
            // let newId = Math.max(...state.columns.allIds) + 1
            // state.columns.allIds.push(newId)
            // state.columns.byId[newId] = { id: newId, title: data.title, cards: [], limit: 10 }
            // state.rows.byId[data.rowId].columns.push(newId)

            state.rows.allIds.forEach(rowId => {
                let newId = Math.max(...state.columns.allIds) + 1
                state.columns.allIds.push(newId)
                state.columns.byId[newId] = { id: newId, title: data.title, cards: [], limit: 10 }
                state.rows.byId[rowId].columns.push(newId)
            })
            let newColId = Math.max(...state.cols.allIds) + 1
            state.cols.byId[newColId] = {id: newColId, title: data.title, limit: 12};
            state.cols.allIds.push(newColId)
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
        editBoardField(state, {id, field, data}) {
            state.boards.byId[id][field] = data
        },
        editRowField(state, {id, field, data}) {
            state.rows.byId[id][field] = data
        },
        editColumnField(state, {id, field, data}) {
            state.cols.byId[id][field] = data
        },
        editCardField(state, {id, field, data}) {
            state.cards.byId[id][field] = data
        }
    }
});

function arrayMove(arr, fromIndex, toIndex) {
    let element = arr[fromIndex]
    arr.splice(fromIndex, 1)
    arr.splice(toIndex, 0, element)
}

function makeRows() {
    let rows = {}
    return rows;
}

function makeCells({rows, cols}) {
    let cells = {};
    let allIds = [];
    let id = 1;
    rows.forEach(row => {
        cols.forEach(col => {
            cells[id] = {id: id, title: 'Done', cards: [], limit: 50}
            allIds.push(id);
            id++;
        })
    })
    return { cells, allIds };
}
