import { create } from 'zustand';
import { Ticket, TicketFilters, CreateTicketData, TicketResponse, TicketCategory } from '../types/ticket';
import { client } from '../services/api/client';

interface TicketState {
    tickets: Ticket[];
    currentTicket: Ticket | null;
    currentTicketResponses: TicketResponse[];
    categories: TicketCategory[];
    isLoading: boolean;
    isCreating: boolean;

    fetchTickets: (filters?: TicketFilters) => Promise<void>;
    fetchTicket: (ticketCode: string) => Promise<Ticket>;
    createTicket: (data: CreateTicketData, attachments?: any[]) => Promise<Ticket>;
    fetchTicketResponses: (ticketCode: string) => Promise<void>;
    createResponse: (ticketCode: string, content: string, attachments?: any[]) => Promise<void>;
    fetchCategories: (companyId: string) => Promise<void>;
    rateTicket: (ticketCode: string, rating: number, comment?: string) => Promise<void>;
    reopenTicket: (ticketCode: string) => Promise<void>;
}

export const useTicketStore = create<TicketState>((set, get) => ({
    tickets: [],
    currentTicket: null,
    currentTicketResponses: [],
    categories: [],
    isLoading: false,
    isCreating: false,

    fetchTickets: async (filters = {}) => {
        set({ isLoading: true });
        try {
            const response = await client.get('/api/tickets', { params: filters });
            set({ tickets: response.data.data, isLoading: false });
        } catch (error) {
            set({ isLoading: false });
            throw error;
        }
    },

    fetchTicket: async (ticketCode) => {
        set({ isLoading: true });
        try {
            const response = await client.get(`/api/tickets/${ticketCode}`);
            set({ currentTicket: response.data.data, isLoading: false });
            return response.data.data;
        } catch (error) {
            set({ isLoading: false });
            throw error;
        }
    },

    createTicket: async (data, attachments = []) => {
        set({ isCreating: true });
        try {
            // 1. Create ticket
            const response = await client.post('/api/tickets', data);
            const newTicket = response.data.data;

            // 2. Upload attachments if any
            if (attachments.length > 0) {
                const formData = new FormData();
                attachments.forEach((file) => {
                    // @ts-ignore
                    formData.append('files', {
                        uri: file.uri,
                        name: file.fileName || 'file',
                        type: file.mimeType || 'application/octet-stream',
                    });
                });

                await client.post(`/api/tickets/${newTicket.ticketCode}/attachments`, formData, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                });
            }

            set({ isCreating: false });
            return newTicket;
        } catch (error) {
            set({ isCreating: false });
            throw error;
        }
    },

    fetchTicketResponses: async (ticketCode) => {
        const response = await client.get(`/api/tickets/${ticketCode}/responses`);
        set({ currentTicketResponses: response.data.data });
    },

    createResponse: async (ticketCode, content, attachments = []) => {
        // 1. Create response
        const response = await client.post(`/api/tickets/${ticketCode}/responses`, { content });
        const newResponse = response.data.data;

        // 2. Upload attachments if any
        if (attachments.length > 0) {
            const formData = new FormData();
            attachments.forEach((file) => {
                // @ts-ignore
                formData.append('files', {
                    uri: file.uri,
                    name: file.fileName || 'file',
                    type: file.mimeType || 'application/octet-stream',
                });
            });

            await client.post(`/api/tickets/${ticketCode}/responses/${newResponse.id}/attachments`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
        }

        // Refresh responses
        await get().fetchTicketResponses(ticketCode);
    },

    fetchCategories: async (companyId) => {
        const response = await client.get('/api/tickets/categories', { params: { company_id: companyId } });
        set({ categories: response.data.data });
    },

    rateTicket: async (ticketCode, rating, comment) => {
        await client.post(`/api/tickets/${ticketCode}/rate`, { rating, comment });
        // Refresh ticket
        await get().fetchTicket(ticketCode);
    },

    reopenTicket: async (ticketCode) => {
        await client.post(`/api/tickets/${ticketCode}/reopen`);
        // Refresh ticket
        await get().fetchTicket(ticketCode);
    },
}));
