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
        accessToken: null,
        trackerId: null,
        kanbanId:null,
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
                1: {id: 1, title: 'To do', cards: []},
                2: {id: 2, title: 'In progress', cards: []},
                3: {id: 3, title: 'Done', cards: []},
                4: {id: 4, title: 'To do', cards: []},
                5: {id: 5, title: 'In progress', cards: []},
                6: {id: 6, title: 'Done', cards: []}
            },
            allIds: [1, 2, 3, 4, 5, 6]
        },
        cards: {
            byId: {
                1: {id: 1, column: 1, row: 1, sortOrder: 1, title: 'Make start button', description: 'Some card description'},
                2: {id: 2, column: 1, row: 1, sortOrder: 3, title: 'Create time tracking', description: 'Some card description'},
                3: {id: 3, column: 1, row: 1, sortOrder: 2, title: 'Rich text formatting', description: 'Some card description'},
                4: {id: 4, column: 1, row: 2, sortOrder: 3, title: 'Add feature to Maps application', description: 'Some card description'},
                5: {id: 5, column: 1, row: 2, sortOrder: 1, title: 'Create a new activity for Sugarizer', description: 'Some card description'},
                6: {id: 6, column: 1, row: 2, sortOrder: 2, title: 'Agora-web Display election detail during voting', description: 'Some card description'}
            },
            allIds: [1, 2, 3, 4, 5, 6]
        }
    },
    getters: {
        getAccessToken(state) {
            return state.accessToken
        },
        getTrackerId(state) {
            return state.trackerId
        },
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
        initBoard({ commit }, data) {
            commit('setBoard', data)
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
        setBoard(state, data) {
            let { boards, rows, cols, cells, cards } = makeKanbanData(data);
            state.boards = boards;
            state.rows = rows;
            state.cols = cols;
            state.columns = cells;
            state.cards = cards;
            state.accessToken = data.accessToken;
            state.trackerId = data.trackerId;
            state.kanbanId = data.kanbanId;
        },
        addBoard(state, data) {
            // Add new board
            let newId = makeId(state.boards.allIds);
            let newRowId = makeId(state.rows.allIds);
            state.boards.allIds.push(newId)
            state.boards.byId[newId] = { id: newId, title: data.title, rows: [newRowId] }
            // Add new row
            state.rows.allIds.push(newRowId)
            state.rows.byId[newRowId] = { id: newRowId, title: 'New swimlane', columns: [] }
        },
        addRow(state, data) {
            // Adds row
            let newRowId = makeId(state.rows.allIds);
            state.rows.allIds.push(newRowId)
            state.rows.byId[newRowId] = { id: newRowId, title: data.title, columns: [] }
            // Where does it push the new ID?
            // state.boards.byId[data.boardId].rows.push(newRowId)
            // Adds columns
            state.cols.allIds.forEach(id => {
                let newId = makeId(state.columns.allIds);
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
            state.rows.allIds.forEach(rowId => {
                let newId = makeId(state.columns.allIds);
                state.columns.allIds.push(newId)
                state.columns.byId[newId] = { id: newId, title: data.title, cards: [], limit: 10 }
                state.rows.byId[rowId].columns.push(newId)
            })
            let newColId = makeId(state.cols.allIds);
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
            let newId = makeId(state.cards.allIds);
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

function makeKanbanData(data) {
    let boardsById = {};
    let boardsAllIds = [data.trackerId];
    boardsById[data.trackerId] = {
        id: data.trackerId,
        title: `Board ${data.trackerId}`,
        // imageUrl: 'https://www.teahub.io/photos/full/90-908127_1920-x-1080-landscape..jpg',
        rows: []
    };

    let rowsById = {};
    let rowsAllIds = data.rows.map(row => row.id);
    data.rows.sort((a, b) => a.id - b.id).forEach(row => {
        rowsById[row.id] = row;
    });
    boardsById[data.trackerId].rows = rowsAllIds;

    let colsById = {};
    let colsAllIds = data.columns.map(col => col.id);
    data.columns.sort((a, b) => a.id - b.id).forEach(col => {
        colsById[col.id] = col;
    });

    let cellsById = {};
    let cellsAllIds = [];
    let cellId = 1;
    let rowColumns = [];
    data.rows.forEach(row => {
        data.columns.forEach(col => {
            let cardsIds = data.cards
                .filter(card => {
                    return card.row === row.id && card.column === col.id;
                })
                .sort((a, b) => a.sortOrder - b.sortOrder)
                .map(card => card.id);

            cellsById[cellId] = { id: cellId, cards: cardsIds, limit: col.limit }
            cellsAllIds.push(cellId);
            rowColumns.push(cellId);
            cellId++;
        })
        rowsById[row.id].columns = rowColumns;
        rowColumns = [];
    })

    let cardsById = {};
    let cardsAllIds = data.cards.map(card => card.id);
    data.cards.sort((a, b) => a.id - b.id).forEach(card => {
        cardsById[card.id] = card;
    });

    return {
        boards: {
            byId: boardsById,
            allIds: boardsAllIds 
        },
        rows: {
            byId: rowsById,
            allIds: rowsAllIds
        },
        cols: {
            byId: colsById,
            allIds: colsAllIds
        },
        cells: {
            byId: cellsById,
            allIds: cellsAllIds
        },
        cards: {
            byId: cardsById,
            allIds: cardsAllIds
        }
    };
}

function arrayMove(arr, fromIndex, toIndex) {
    let element = arr[fromIndex]
    arr.splice(fromIndex, 1)
    arr.splice(toIndex, 0, element)
}

function makeId(ids) {
    return ids.length ? Math.max(...ids) + 1 : 1
}