import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';
import { route } from 'ziggy-js';

export const useCageStore = defineStore('cage', () => {
    const cages = ref(null);
    const loading = ref(false);
    const error = ref(null);
    const filters = ref({
        search: '',
        page: 1
    });

    const fetchCages = async () => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.get(route('cages.list'), {
                params: filters.value
            });
            cages.value = response.data.cages;
        } catch (e) {
            error.value = e.message;
        } finally {
            loading.value = false;
        }
    };

    const createCage = async (cageData) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.post(route('cages.store'), cageData);
            return response.data;
        } catch (e) {
            error.value = e.message;
            throw e;
        } finally {
            loading.value = false;
        }
    };

    const updateCage = async (id, cageData) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.put(route('cages.update', id), cageData);
            if (cages.value && cages.value.data && Array.isArray(cages.value.data)) {
                const index = cages.value.data.findIndex(i => i.id === id);
                if (index !== -1) {
                    cages.value.data[index] = response.data;
                }
            }
            return response.data;
        } catch (e) {
            error.value = e.message;
            throw e;
        } finally {
            loading.value = false;
        }
    };

    const deleteCage = async (id) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.delete(route('cages.destroy', id));
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
        cages,
        loading,
        error,
        filters,
        fetchCages,
        createCage,
        updateCage,
        deleteCage,
        setFilters,
    };
}); 