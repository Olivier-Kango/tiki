import { createStore } from 'vuex'
import logger from "./logger"
import {
    saveToLocalStorage,
    createBlock,
    createPattern,
    cloneBlockDeep,
    getParentBlockId,
    getDescendantIds,
    swapIndices
} from './helpers'
import cloneDeep from 'lodash/cloneDeep'

const debug = import.meta.env.MODE !== 'production'
let history = []

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
        patterns: {
            "root": {
                id: "root",
                type: 'page',
                name: "Home page",
                innerBlocks: []
            }
        },
        blocks: {},
        historyLength: 0
    },
    getters: {
        getHistoryLength(state) {
            return state.historyLength
        },
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
        getPatterns(state) {
            return state.patterns
        },
        getPatternsArray(state) {
            return Object
                    .getOwnPropertyNames(state.patterns)
                    .map(id => state.patterns[id])
        },
        getPatternPages(state) {
            return Object
                    .getOwnPropertyNames(state.patterns)
                    .map(id => state.patterns[id])
                    .filter(pattern => pattern.type === 'page')
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
        },
        setDroppedUp({ commit, state }) {
            commit('setDroppedUp')
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
        setRootBlock({ commit }, data) {
            commit('setRootBlock', data)
            commit('clearHistory', data)
        },
        setPatterns({ commit }, data) {
            commit('setPatterns', data)
        },
        addPattern({ commit }, data) {
            commit('addPattern', data)
        },
        setBlocks({ commit }, data) {
            commit('setBlocks', data)
        },
        addBlocks({ commit }, data) {
            commit('addBlocks', data)
        },
        setHistory({ commit, state }) {
            commit('setHistory')
        },
        setBlocksFromHistory({ commit, state }, index) {
            commit('setBlocksFromHistory', index)
        },
        updateBlock({ commit, state }, data) {
            commit('updateBlock', data)
        },
        insertBlock({ commit, state }, data) {
            commit('insertBlock', data)
        },
        insertNewBlock({ commit, state }, data) {
            commit('insertNewBlock', data)
        },
        insertNewBlockAfter({ commit, state }, data) {
            commit('insertNewBlockAfter', data)
        },
        deleteBlock({ commit, state }, data) {
            commit('deleteBlock', data)
        },
        moveBlockBackward({ commit, state }, data) {
            commit('moveBlockBackward', data)
        },
        moveBlockForward({ commit, state }, data) {
            commit('moveBlockForward', data)
        },
        cloneBlock({ commit }, data) {
            commit('cloneBlock', data)
        },
    },
    mutations: {
        addBlockType(state, data) {
            state.blockTypes = { ...state.blockTypes, ...data }
        },
        setHistory(state) {
            history.push(cloneDeep(state.blocks))
            if (history.length > 20) history.shift()
            state.historyLength = history.length
            console.log(history)
        },
        clearHistory(state) {
            history = []
            state.historyLength = history.length
        },
        setBlocksFromHistory(state, index) {
            if (0 <= index < history.length) {
                state.blocks = history[index]
                // Sync by reference
                state.patterns['root'].innerBlocks = state.blocks['root'].innerBlocks
                saveToLocalStorage()
            }
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
        updateAPIoption(state, { option, index }) {
            state.apiOptions[index] = option
        },
        removeAPIoption(state, index) {
            state.apiOptions.splice(index, 1)
        },
        setRootBlock(state, patternId) {
            // let pattern = Object.getOwnPropertyNames(state.patterns)
            //     .map(patternId => state.patterns[patternId])
            //     .find(pattern => pattern.name === data)
            state.blocks['root'] = { ...state.patterns[patternId] }
            state.blocks['root'].id = 'root'
            state.blocks['root'].patternId = patternId
        },
        setPatterns(state, data) {
            state.patterns = data
        },
        addPattern(state, { blockId, patternName }) {
            if (!blockId) return
            let clonedBlock = cloneBlockDeep(state.blocks, blockId)
            let pattern = createPattern({ name: patternName }, [clonedBlock.rootId])
            state.blocks = { ...state.blocks, ...clonedBlock.blocks }
            state.patterns[pattern.id] = pattern
        },
        setBlocks(state, blocks) {
            state.blocks = blocks
            // Syncs or set root block with corresponding pattern
            let patternId = blocks['root']?.patternId || 'root'
            state.blocks['root'] = { ...state.patterns[patternId] }
            state.blocks['root'].id = patternId
            state.blocks['root'].patternId = patternId
        },
        addBlocks(state, blocks) {
            Object.getOwnPropertyNames(blocks).forEach(blockId => state.blocks[blockId] = blocks[blockId])
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
        insertBlock(state, { blockId, targetId, position = 0 }) {
            state.blocks[targetId].innerBlocks.splice(position, 0, blockId)
            // state.patterns[targetId].innerBlocks.splice(position, 0, blockId)
        },
        insertNewBlock(state, { name, targetId, position = 0 }) {
            let block = createBlock(state.blockTypes[name])
            state.blocks[block.id] = block
            state.blocks[targetId].innerBlocks.splice(position, 0, block.id)
        },
        insertNewBlockAfter(state, { name, targetId }) {
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
