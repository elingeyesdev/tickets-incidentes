export interface Announcement {
    id: string;
    title: string;
    content: string;
    type: 'info' | 'maintenance' | 'outage';
    priority: 'low' | 'medium' | 'high';
    company: {
        id: string;
        name: string;
        logoUrl: string | null;
    };
    createdAt: string;
    expiresAt: string | null;
    isRead: boolean;
}

export interface Article {
    id: string;
    title: string;
    excerpt: string;
    content: string; // HTML or Markdown
    category: {
        id: string;
        name: string;
    };
    company: {
        id: string;
        name: string;
    };
    author: {
        displayName: string;
    };
    viewsCount: number;
    helpfulCount: number;
    createdAt: string;
    updatedAt: string;
}

export interface ContentFilters {
    search?: string;
    company_id?: string;
    type?: 'announcement' | 'article';
    page?: number;
    per_page?: number;
}
