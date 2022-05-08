import { createStore } from 'vuex'
import logger from "./logger"
import {
    saveToLocalStorage,
    createBlock,
    cloneBlockDeep,
    getParentBlockId,
    getDescendantIds,
    swapIndices
} from './helpers'

const debug = import.meta.env.MODE !== 'production'

export default createStore({
    modules: {
        // dynamic modules
    },
    strict: debug,
    plugins: debug ? [logger] : [],
    state: {
        apiOptions: [],
        mode: 'prod', // 'edit'
        dropzonedBlock: null,
        draggedBlock: null,
        selectedBlock: null,
        highlightedBlock: null,
        blockTypes: {},
        blocks: {
            'root': {
                id: `root`,
                name: 'core/root',
                innerBlocks: [],
                attributes: {}
            }
        }
    },
    getters: {
        getMode(state) {
            return state.mode
        },
        getDropzonedBlock(state) {
            return state.blocks[state.dropzonedBlock]
        },
        getDraggedBlock(state) {
            return state.blocks[state.draggedBlock]
        },
        getAPIoptions(state) {
            return state.apiOptions
        },
        getAPIfields(state) {
            return state.apiOptions.map(api => api.field)
        },
        getRootBlock(state) {
            return state.blocks['root']
        },
        getBlock(state) {
            return (id) => state.blocks[id]
        },
        getParentBlock(state) {
            return (id) => state.blocks[getParentBlockId(state.blocks, id)]
        },
        getBlocks(state) {
            return state.blocks
        },
        getBlockList(state) {
            return Object.getOwnPropertyNames(state.blockTypes)
        },
        getBlockType(state) {
            return type => state.blockTypes[type]
        },
        getSelected(state) {
            return state.blocks[state.selectedBlock]
        },
        getHighlighted(state) {
            return state.blocks[state.highlightedBlock]
        }
    },
    actions: {
        addBlockType({ commit }, data) {
            commit('addBlockType', data)
        },
        setMode({ commit }, data) {
            commit('setMode', data)
        },
        setDragged({ commit }, data) {
            commit('setDragged', data)
        },
        setDropzoned({ commit }, data) {
            commit('setDropzoned', data)
        },
        setDroppedIn({ commit, state }) {
            commit('setDroppedIn')
            saveToLocalStorage(state.blocks)
        },
        setDroppedUp({ commit, state }) {
            commit('setDroppedUp')
            saveToLocalStorage(state.blocks)
        },
        setAPIoptions({ commit }, data) {
            commit('setAPIoptions', data)
        },
        addAPIoption({ commit }, data) {
            commit('addAPIoption', data)
        },
        updateAPIoption({ commit }, data) {
            commit('updateAPIoption', data)
        },
        removeAPIoption({ commit }, data) {
            commit('removeAPIoption', data)
        },
        setSelected({ commit }, data) {
            commit('setSelected', data)
        },
        setHighlighted({ commit }, data) {
            commit('setHighlighted', data)
        },
        setBlocks({ commit }, data) {
            commit('setBlocks', data)
        },
        updateBlock({ commit, state }, data) {
            commit('updateBlock', data)
            saveToLocalStorage(state.blocks)
        },
        insertBlock({ commit, state }, data) {
            commit('insertBlock', data)
            saveToLocalStorage(state.blocks)
        },
        insertBlockAfter({ commit, state }, data) {
            commit('insertBlockAfter', data)
            saveToLocalStorage(state.blocks)
        },
        deleteBlock({ commit, state }, data) {
            commit('deleteBlock', data)
            saveToLocalStorage(state.blocks)
        },
        addBlock({ commit, state }, data) {
            commit('addBlock', data)
            saveToLocalStorage(state.blocks)
        },
        moveBlock({ commit, state }, data) {
            commit('moveBlock', data)
            saveToLocalStorage(state.blocks)
        },
        removeBlock({ commit, state }, data) {
            commit('removeBlock', data)
            saveToLocalStorage(state.blocks)
        },
        moveBlockBackward({ commit, state }, data) {
            commit('moveBlockBackward', data)
            saveToLocalStorage(state.blocks)
        },
        moveBlockForward({ commit, state }, data) {
            commit('moveBlockForward', data)
            saveToLocalStorage(state.blocks)
        },
        cloneBlock({ commit }, data) {
            commit('cloneBlock', data)
        },
    },
    mutations: {
        addBlockType(state, data) {
            state.blockTypes = { ...state.blockTypes, ...data }
        },
        setMode(state, data) {
            state.mode = data
        },
        setDropzoned(state, data) {
            state.dropzonedBlock = data
        },
        setDragged(state, data) {
            state.draggedBlock = data
        },
        setDroppedIn(state, data) {
            if (!state.selectedBlock || !state.dropzonedBlock) return
            if (state.selectedBlock === state.dropzonedBlock) return

            let parentBlockId = getParentBlockId(state.blocks, state.selectedBlock)
            let index = state.blocks[parentBlockId].innerBlocks.findIndex(id => id === state.selectedBlock)
            // remove block
            state.blocks[parentBlockId].innerBlocks.splice(index, 1)
            // add block
            state.blocks[state.dropzonedBlock].innerBlocks.push(state.selectedBlock)
        },
        setDroppedUp(state, data) {
            if (!state.selectedBlock || !state.dropzonedBlock) return
            if (state.selectedBlock === state.dropzonedBlock) return

            let dropzonedParentBlockId = getParentBlockId(state.blocks, state.dropzonedBlock)
            let indexDropzonedBlock = state.blocks[dropzonedParentBlockId].innerBlocks.findIndex(id => id === state.dropzonedBlock)
            let selectedParentBlockId = getParentBlockId(state.blocks, state.selectedBlock)
            let indexSelectedBlock = state.blocks[selectedParentBlockId].innerBlocks.findIndex(id => id === state.selectedBlock)

            // if at same level
            if (selectedParentBlockId === dropzonedParentBlockId) {
                swapIndices(indexSelectedBlock, indexDropzonedBlock, state.blocks[selectedParentBlockId].innerBlocks)
                return
            }
            // if at different levels
            // remove block
            state.blocks[selectedParentBlockId].innerBlocks.splice(indexSelectedBlock, 1)
            // add block
            state.blocks[dropzonedParentBlockId].innerBlocks.splice(indexDropzonedBlock + 1, 0, state.selectedBlock)
            // state.blocks[state.dropzonedBlock].innerBlocks.push(state.selectedBlock)
        },
        setAPIoptions(state, data) {
            state.apiOptions = data
        },
        addAPIoption(state, data) {
            state.apiOptions.push(data)
        },
        updateAPIoption(state, { value, field, index }) {
            state.apiOptions[index][field] = value
        },
        removeAPIoption(state, index) {
            state.apiOptions.splice(index, 1)
        },
        setBlocks(state, data) {
            state.blocks = data
        },
        setSelected(state, data) {
            state.selectedBlock = data;
        },
        setHighlighted(state, data) {
            state.highlightedBlock = data;
        },
        updateBlock(state, data) {
            let { block, field, value } = data
            state.blocks[block.id][field] = value
        },
        insertBlock(state, { name, targetId, position = 0 }) {
            let block = createBlock(state.blockTypes[name])
            state.blocks[block.id] = block
            state.blocks[targetId].innerBlocks.splice(position, 0, block.id)
        },
        insertBlockAfter(state, { name, targetId }) {
            let block = createBlock(state.blockTypes[name])
            state.blocks[block.id] = block

            let parentBlockId = getParentBlockId(state.blocks, targetId)
            let targetIndex = state.blocks[parentBlockId].innerBlocks.findIndex(id => id === targetId)
            state.blocks[parentBlockId].innerBlocks.splice(targetIndex + 1, 0, block.id)
        },
        deleteBlock(state, blockId) {
            // Removes id in parent innerBlocks
            let parentBlockId = getParentBlockId(state.blocks, blockId)
            if (!parentBlockId) {
                console.warn('Missing parent block ID')
                return
            }
            let parentInnerBlocks = state.blocks[parentBlockId].innerBlocks
            let index = parentInnerBlocks.findIndex(id => id === blockId)
            parentInnerBlocks.splice(index, 1)
            // Removes all
            // node.descendants returns an array of descendant nodes: the given node, then each child, and each child’s child, and so on.
            let ids = getDescendantIds(state.blocks, blockId)
            ids.forEach(id => delete state.blocks[id])
        },
        addBlock(state, { id, newIndex }) {
            console.log({ id, newIndex })
            // let parentId = getParentBlockId(state.blocks, id)
            // state.blocks[parentId].innerBlocks.splice(newIndex, 0, id)
        },
        moveBlock(state, { id, oldIndex, newIndex }) {
            console.log({ id, oldIndex, newIndex })
            // let parentId = getParentBlockId(state.blocks, id)
            // swapIndices(oldIndex, newIndex, state.blocks[parentId].innerBlocks)
        },
        removeBlock(state, { id, oldIndex }) {
            console.log({ id, oldIndex })
            // let parentId = getParentBlockId(state.blocks, id)
            // state.blocks[parentId].innerBlocks.splice(oldIndex, 1)
        },
        moveBlockBackward(state, blockId) {
            let parentId = getParentBlockId(state.blocks, blockId)
            let index = state.blocks[parentId].innerBlocks.findIndex(id => id === blockId)
            if (index > 0)
                swapIndices(index, index - 1, state.blocks[parentId].innerBlocks) 
        },
        moveBlockForward(state, blockId) {
            let parentId = getParentBlockId(state.blocks, blockId)
            let index = state.blocks[parentId].innerBlocks.findIndex(id => id === blockId)
            if (index < state.blocks[parentId].innerBlocks.length - 1)
                swapIndices(index, index + 1, state.blocks[parentId].innerBlocks) 
        },
        cloneBlock(state, blockId) {
            let res = cloneBlockDeep(state.blocks, blockId)
            
            console.log(res)
        }
    }
});
