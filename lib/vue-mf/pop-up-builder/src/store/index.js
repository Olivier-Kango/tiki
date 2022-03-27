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
        user: null,
    },
    getters: {

    },
    actions: {

    },
    mutations: {

    }
});
