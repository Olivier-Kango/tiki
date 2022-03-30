import { createStore } from 'vuex'
import logger from "./logger"
import { arrayToTree } from "performant-array-to-tree"

const debug = import.meta.env.MODE !== 'production'

export default createStore({
    modules: {
        // dynamic modules
    },
    strict: debug,
    plugins: debug ? [logger] : [],
    state: {
        toTreeConfig: { id: "id", parentId: "parent_id" },
        blocks: [
            {
                id: 1,
                parent_id: null,
                name: 'Container',
                classes: 'container shadow rounded-4 p-4',
                styles: {
                    marginTop: '5rem',
                    marginBottom: '5rem'
                }
            },
            {
                id: 2,
                parent_id: 1,
                name: 'Title',
                text: 'Say Hello to the New Editor',
                classes: '',
                styles: {

                }
            },
            {
                id: 3,
                parent_id: 1,
                name: 'Paragraph',
                text: 'We call the new editor Gutenberg. The entire editing experience has been rebuilt for media rich pages and posts. Experience the flexibility that blocks will bring, whether you are building your first site, or write code for a living.',
                classes: '',
                styles: {

                }
            },
            {
                id: 4,
                parent_id: 1,
                name: 'Button',
                text: 'Sign in',
                classes: '',
                styles: {

                }
            },
            {
                id: 5,
                parent_id: 1,
                name: 'Row',
                classes: 'row mt-3',
                styles: {
                    minHeight: '50px'
                }
            },
            {
                id: 6,
                parent_id: 5,
                name: 'Col',
                classes: 'col',
                styles: {

                }
            },
            {
                id: 7,
                parent_id: 5,
                name: 'Col',
                classes: 'col',
                styles: {

                }
            },
            {
                id: 8,
                parent_id: 5,
                name: 'Col',
                classes: 'col',
                styles: {

                }
            },
            {
                id: 9,
                parent_id: 7,
                name: 'Paragraph',
                text: 'Proident. Minim. Dolor enim fugit, enim for tempor so dolores but ex. Cillum natus quasi in sed for nemo. Minim aute so enim but consequat yet commodi voluptas corporis. Iure magna aliquid. Aspernatur amet. Ad iste eu lorem. Anim ipsum so adipisicing.',
                classes: '',
                styles: {

                }
            },
            {
                id: 10,
                parent_id: 8,
                name: 'Image',
                text: 'https://www.solitarywanderer.com/wp-content/uploads/2019/01/Paris-Solo-Travel-Tips-1024x727.jpg',
                classes: '',
                styles: {

                }
            },
            {
                id: 11,
                parent_id: 6,
                name: 'Paragraph',
                text: 'Savannah. Birman havana brown yet tom abyssinian so abyssinian . Ocelot munchkin, kitten for american shorthair munchkin yet russian blue. Norwegian forest mouser yet bobcat malkin. Devonshire rex norwegian forest singapura for kitty ragdoll so tom but cheetah. Tom maine coon thai, persian kitten lynx. American shorthair cougar egyptian mau. Siamese cornish rex bobcat. Russian blue ocicat, egyptian mau ragdoll so tomcat. Donskoy tom. Tomcat lynx cougar.',
                classes: '',
                styles: {

                }
            },
        ],
    },
    getters: {
        getBlocksTree(state) {
            console.log(arrayToTree(state.blocks, state.toTreeConfig))
            return arrayToTree(state.blocks, state.toTreeConfig)
        },
        getBlocks(state) {
            return state.blocks
        },
        getEditorData(state) {
            return state.editorData
        },
    },
    actions: {
        setBlocks({ commit }, data) {
            commit('setBlocks', data)
        },
        setEditorData({ commit }, data) {
            commit('setData', data)
        },
    },
    mutations: {
        setBlocks(state, data) {
            state.blocks = data;
        },
        setData(state, data) {
            state.editorData = data;
        },
    }
});
