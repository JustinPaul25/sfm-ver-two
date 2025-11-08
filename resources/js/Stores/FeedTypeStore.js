import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';

export const useFeedTypeStore = defineStore('feedType', () => {
    const feedTypes = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const filters = ref({
        search: '',
        page: 1
    });

    const fetchFeedTypes = async () => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.get(route('feed-types.list'), {
                params: filters.value
            });
            feedTypes.value = response.data.feedTypes;
        } catch (e) {
            error.value = e.message;
        } finally {
            loading.value = false;
        }
    };

    const createFeedType = async (data) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.post(route('feed-types.store'), data);
            await fetchFeedTypes();
            return response.data;
        } catch (e) {
            error.value = e.message;
            throw e;
        } finally {
            loading.value = false;
        }
    };

    const updateFeedType = async (id, data) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.put(route('feed-types.update', id), data);
            await fetchFeedTypes();
            return response.data;
        } catch (e) {
            error.value = e.message;
            throw e;
        } finally {
            loading.value = false;
        }
    };

    const deleteFeedType = async (id) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.delete(route('feed-types.destroy', id));
            await fetchFeedTypes();
            return response.data;
        } catch (e) {
            error.value = e.message;
            throw e;
        } finally {
            loading.value = false;
        }
    };

    const restoreFeedType = async (id) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.post(route('feed-types.restore', id));
            await fetchFeedTypes();
            return response.data;
        } catch (e) {
            error.value = e.message;
            throw e;
        } finally {
            loading.value = false;
        }
    };

    const setFilters = (newFilters) => {
        filters.value = { ...filters.value, ...newFilters };
    };

    return {
        feedTypes,
        loading,
        error,
        filters,
        fetchFeedTypes,
        createFeedType,
        updateFeedType,
        deleteFeedType,
        restoreFeedType,
        setFilters,
    };
}); 