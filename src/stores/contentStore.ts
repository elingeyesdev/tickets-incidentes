import { create } from 'zustand';
import { Announcement, Article, ContentFilters } from '../types/content';
import { client } from '../services/api/client';

interface ContentState {
    announcements: Announcement[];
    articles: Article[];
    currentArticle: Article | null;
    isLoading: boolean;

    fetchAnnouncements: () => Promise<void>;
    markAnnouncementAsRead: (id: string) => Promise<void>;
    fetchArticles: (filters?: ContentFilters) => Promise<void>;
    fetchArticle: (id: string) => Promise<Article>;
    rateArticle: (id: string, helpful: boolean) => Promise<void>;
}

export const useContentStore = create<ContentState>((set, get) => ({
    announcements: [],
    articles: [],
    currentArticle: null,
    isLoading: false,

    fetchAnnouncements: async () => {
        set({ isLoading: true });
        try {
            const response = await client.get('/api/announcements');
            set({ announcements: response.data.data, isLoading: false });
        } catch (error) {
            set({ isLoading: false });
            throw error;
        }
    },

    markAnnouncementAsRead: async (id) => {
        await client.post(`/api/announcements/${id}/read`);
        set((state) => ({
            announcements: state.announcements.map((a) =>
                a.id === id ? { ...a, isRead: true } : a
            ),
        }));
    },

    fetchArticles: async (filters = {}) => {
        set({ isLoading: true });
        try {
            const response = await client.get('/api/help-center/articles', { params: filters });
            set({ articles: response.data.data, isLoading: false });
        } catch (error) {
            set({ isLoading: false });
            throw error;
        }
    },

    fetchArticle: async (id) => {
        set({ isLoading: true });
        try {
            const response = await client.get(`/api/help-center/articles/${id}`);
            set({ currentArticle: response.data.data, isLoading: false });
            return response.data.data;
        } catch (error) {
            set({ isLoading: false });
            throw error;
        }
    },

    rateArticle: async (id, helpful) => {
        await client.post(`/api/help-center/articles/${id}/rate`, { helpful });
        // Optimistic update
        set((state) => {
            if (state.currentArticle?.id === id) {
                return {
                    currentArticle: {
                        ...state.currentArticle,
                        helpfulCount: state.currentArticle.helpfulCount + 1
                    }
                };
            }
            return {};
        });
    },
}));
