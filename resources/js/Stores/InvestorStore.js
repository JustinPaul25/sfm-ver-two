import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';

export const useInvestorStore = defineStore('investor', () => {
    const investors = ref([]);
    const investorsSelect = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const filters = ref({
        search: '',
        page: 1
    });

    const fetchInvestorsSelect = async () => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.get(route('investors.select'));
            investorsSelect.value = response.data;
        } catch (e) {
            error.value = e.message;
        } finally {
            loading.value = false;
        }
    };

    const fetchInvestors = async () => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.get(route('investors.list'), {
                params: filters.value
            });
            investors.value = response.data.investors;
        } catch (e) {
            error.value = e.message;
        } finally {
            loading.value = false;
        }
    };

    const createInvestor = async (investorData) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.post(route('investors.store'), investorData);
            // investors.value.push(response.data);
            return response.data;
        } catch (e) {
            error.value = e.message;
            throw e;
        } finally {
            loading.value = false;
        }
    };

    const updateInvestor = async (id, investorData) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.put(route('investors.update', id), investorData);
            if (investors.value && investors.value.data && Array.isArray(investors.value.data)) {
                const index = investors.value.data.findIndex(i => i.id === id);
                if (index !== -1) {
                    investors.value.data[index] = response.data;
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

    const deleteInvestor = async (id) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.delete(route('investors.destroy', id));
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
        investors,
        investorsSelect,
        loading,
        error,
        filters,
        fetchInvestors,
        createInvestor,
        updateInvestor,
        deleteInvestor,
        setFilters,
        fetchInvestorsSelect
    };
});
