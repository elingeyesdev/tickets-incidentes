export interface CompanyExploreItem {
    id: string;
    companyCode: string;
    name: string;
    logoUrl: string | null;
    description: string;
    industry: {
        id: string;
        code: string;
        name: string;
    };
    city: string;
    country: string;
    primaryColor: string;
    status: 'ACTIVE' | 'SUSPENDED';
    followersCount: number;
    isFollowedByMe: boolean;
}

export interface CompanyDetail extends CompanyExploreItem {
    legalName: string | null;
    supportEmail: string;
    phone: string | null;
    website: string | null;
    contactAddress: string | null;
    contactCity: string | null;
    contactState: string | null;
    contactCountry: string | null;
    contactPostalCode: string | null;
    businessHours: {
        monday?: { open: string; close: string };
        tuesday?: { open: string; close: string };
        wednesday?: { open: string; close: string };
        thursday?: { open: string; close: string };
        friday?: { open: string; close: string };
        saturday?: { open: string; close: string };
        sunday?: { open: string; close: string };
    };
    timezone: string;
    faviconUrl: string | null;
    secondaryColor: string;
    // Campos adicionales si sigue la empresa
    myTicketsCount?: number;
    lastTicketCreatedAt?: string | null;
    hasUnreadAnnouncements?: boolean;
}

export interface CompanyExploreFilters {
    search?: string;
    industryId?: string;
    country?: string;
    followedByMe?: boolean;
    sortBy?: 'name' | 'followers_count' | 'created_at';
    sortDirection?: 'asc' | 'desc';
    page?: number;
    perPage?: number;
}

export interface Industry {
    id: string;
    name: string;
    code: string;
}

