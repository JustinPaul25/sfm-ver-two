import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';

export const useSamplingStore = defineStore('sampling', () => {
    const samplings = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const filters = ref({
        search: '',
        page: 1
    });

    const fetchSamplings = async () => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.get(route('samplings.list'), {
                params: filters.value
            });
            samplings.value = response.data.samplings;
        } catch (e) {
            error.value = e.message;
        } finally {
            loading.value = false;
        }
    };

    const createSampling = async (samplingData) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.post(route('samplings.store'), samplingData);
            return response.data;
        } catch (e) {
            error.value = e.message;
            throw e;
        } finally {
            loading.value = false;
        }
    };

    const updateSampling = async (id, samplingData) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.put(route('samplings.update', id), samplingData);
            if (samplings.value && samplings.value.data && Array.isArray(samplings.value.data)) {
                const index = samplings.value.data.findIndex(s => s.id === id);
                if (index !== -1) {
                    samplings.value.data[index] = response.data.sampling;
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

    const deleteSampling = async (id) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.delete(route('samplings.destroy', id));
            return response.data;
        } catch (e) {
            error.value = e.message;
            throw e;
        } finally {
            loading.value = false;
        }
    };

    const generateSamples = async (samplingId) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.post(route('samplings.generate-samples', samplingId));
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
        samplings,
        loading,
        error,
        filters,
        fetchSamplings,
        createSampling,
        updateSampling,
        deleteSampling,
        generateSamples,
        setFilters
    };
});
