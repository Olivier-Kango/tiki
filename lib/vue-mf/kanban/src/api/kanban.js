import axios from 'axios';
import qs from 'qs';
import store from '../store';

const baseUrl = location.protocol + '//' + location.host + '/api';
axios.defaults.baseURL = baseUrl;
axios.defaults.headers.common = { 'Authorization': `Bearer ${store.getters.getAccessToken}` };

// Add api method here
export default {
    createBoard: function ({ trackerId, itemId }, payload) {
        return axios({
            method: 'post',
            url: `/trackers/${trackerId}/`,
            data: qs.stringify(payload)
        });
    },
    getItem: function ({ trackerId, itemId }, payload) {
        return axios({
            method: 'get',
            url: `/trackers/${trackerId}/items/${itemId}`,
            data: qs.stringify(payload)
        });
    },
    setItem: function ({ trackerId, itemId }, payload) {
        return axios({
            method: 'post',
            url: `/trackers/${trackerId}/items/${itemId}`,
            data: qs.stringify(payload)
        });
    },
    deleteItem: function ({ trackerId, itemId }) {
        return axios({
            method: 'delete',
            url: `/trackers/${trackerId}/items/${itemId}`
        });
    },
    getField: function ({ trackerId, fieldId }, payload) {
        return axios({
            method: 'get',
            url: `/trackers/${trackerId}/fields/${fieldId}`,
            data: qs.stringify(payload)
        });
    },
    setField: function ({ trackerId, fieldId }, payload) {
        return axios({
            method: 'post',
            url: `/trackers/${trackerId}/fields/${fieldId}`,
            data: qs.stringify(payload)
        });
    },
    deleteField: function ({ trackerId, fieldId }) {
        return axios({
            method: 'delete',
            url: `/trackers/${trackerId}/fields/${fieldId}`
        });
    }
}
