import { create } from 'zustand';
import { Company, CompanyExploreFilters, Industry } from '../types/company';
import { client } from '../services/api/client';

interface CompanyState {
    companies: Company[];
    followedCompanies: Company[];
    industries: Industry[];
    isLoading: boolean;

    fetchCompanies: (filters?: CompanyExploreFilters) => Promise<void>;
    fetchFollowedCompanies: () => Promise<void>;
    fetchCompany: (id: string) => Promise<Company>;
    fetchIndustries: () => Promise<void>;
    followCompany: (id: string) => Promise<void>;
    unfollowCompany: (id: string) => Promise<void>;
}

export const useCompanyStore = create<CompanyState>((set, get) => ({
    companies: [],
    followedCompanies: [],
    industries: [],
    isLoading: false,

    fetchCompanies: async (filters = {}) => {
        set({ isLoading: true });
        try {
            const response = await client.get('/api/companies/explore', { params: filters });
            set({ companies: response.data.data, isLoading: false });
        } catch (error) {
            set({ isLoading: false });
            throw error;
        }
    },

    fetchFollowedCompanies: async () => {
        set({ isLoading: true });
        try {
            // Assuming endpoint exists or we filter explore
            // Prompt says: "3.3 My Followed Companies Screen ... Lista filtrada de empresas que el usuario sigue"
            // And "GET /api/companies/explore" has "followed_by_me" filter.
            // But also "GET /api/companies/explore" is for "Explorar empresas con filtros".
            // Let's use the filter.
            const response = await client.get('/api/companies/explore', {
                params: { followed_by_me: true }
            });
            set({ followedCompanies: response.data.data, isLoading: false });
        } catch (error) {
            set({ isLoading: false });
            throw error;
        }
    },

    fetchCompany: async (id) => {
        const response = await client.get(`/api/companies/${id}`);
        return response.data.data;
    },

    fetchIndustries: async () => {
        const response = await client.get('/api/company-industries');
        set({ industries: response.data.data });
    },

    followCompany: async (id) => {
        await client.post(`/api/companies/${id}/follow`);
        // Optimistic update
        set((state) => ({
            companies: state.companies.map((c) =>
                c.id === id ? { ...c, isFollowing: true } : c
            ),
            followedCompanies: [...state.followedCompanies, { ...state.companies.find(c => c.id === id)!, isFollowing: true }]
        }));
    },

    unfollowCompany: async (id) => {
        await client.delete(`/api/companies/${id}/unfollow`);
        // Optimistic update
        set((state) => ({
            companies: state.companies.map((c) =>
                c.id === id ? { ...c, isFollowing: false } : c
            ),
            followedCompanies: state.followedCompanies.filter((c) => c.id !== id)
        }));
    },
}));
