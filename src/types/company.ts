export interface Company {
    id: string;
    companyCode: string; // CMP-2025-00001
    name: string;
    legalName: string | null;
    description: string | null;
    supportEmail: string;
    phone: string | null;
    website: string | null;
    logoUrl: string | null;
    primaryColor: string; // #007bff
    industry: {
        id: string;
        name: string;
    } | null;
    businessHours: Record<string, { open: string; close: string }>;
    timezone: string;
    status: 'active' | 'suspended';
    createdAt: string;

    // Para usuarios autenticados
    isFollowing?: boolean;
    followedAt?: string;
    statistics?: {
        myTicketsCount: number;
        lastTicketCreatedAt: string | null;
        hasUnreadAnnouncements: boolean;
    };
}

export interface CompanyExploreFilters {
    search?: string;
    industry_id?: string;
    country?: string;
    followed_by_me?: boolean;
    sort_by?: 'name' | 'followers_count' | 'created_at';
    sort_direction?: 'asc' | 'desc';
    page?: number;
    per_page?: number;
}

export interface Industry {
    id: string;
    name: string;
}
