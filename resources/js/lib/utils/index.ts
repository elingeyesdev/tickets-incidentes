/**
 * Utility Functions
 */

/**
 * Formatea una fecha en formato legible
 */
export const formatDate = (date: string | Date, locale: 'es' | 'en' = 'es'): string => {
    const d = typeof date === 'string' ? new Date(date) : date;
    
    return new Intl.DateTimeFormat(locale === 'es' ? 'es-ES' : 'en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    }).format(d);
};

/**
 * Formatea una fecha y hora en formato legible
 */
export const formatDateTime = (date: string | Date, locale: 'es' | 'en' = 'es'): string => {
    const d = typeof date === 'string' ? new Date(date) : date;
    
    return new Intl.DateTimeFormat(locale === 'es' ? 'es-ES' : 'en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(d);
};

/**
 * Formatea fecha relativa (hace 2 horas, hace 3 días)
 */
export const formatRelativeTime = (date: string | Date, locale: 'es' | 'en' = 'es'): string => {
    const d = typeof date === 'string' ? new Date(date) : date;
    const now = new Date();
    const diff = now.getTime() - d.getTime();
    
    const seconds = Math.floor(diff / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);
    
    if (days > 0) {
        return locale === 'es' 
            ? `hace ${days} ${days === 1 ? 'día' : 'días'}`
            : `${days} ${days === 1 ? 'day' : 'days'} ago`;
    }
    
    if (hours > 0) {
        return locale === 'es'
            ? `hace ${hours} ${hours === 1 ? 'hora' : 'horas'}`
            : `${hours} ${hours === 1 ? 'hour' : 'hours'} ago`;
    }
    
    if (minutes > 0) {
        return locale === 'es'
            ? `hace ${minutes} ${minutes === 1 ? 'minuto' : 'minutos'}`
            : `${minutes} ${minutes === 1 ? 'minute' : 'minutes'} ago`;
    }
    
    return locale === 'es' ? 'hace un momento' : 'just now';
};

/**
 * Trunca un string a un límite de caracteres
 */
export const truncate = (str: string, limit: number): string => {
    if (str.length <= limit) return str;
    return str.substring(0, limit) + '...';
};

/**
 * Capitaliza la primera letra de un string
 */
export const capitalize = (str: string): string => {
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
};

/**
 * Genera iniciales desde un nombre completo
 */
export const getInitials = (name: string): string => {
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .substring(0, 2);
};

/**
 * Valida un email
 */
export const isValidEmail = (email: string): boolean => {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
};

/**
 * Valida una contraseña (mínimo 8 caracteres)
 */
export const isValidPassword = (password: string): boolean => {
    return password.length >= 8;
};

/**
 * Debounce function
 */
export const debounce = <T extends (...args: any[]) => any>(
    func: T,
    delay: number
): ((...args: Parameters<T>) => void) => {
    let timeoutId: ReturnType<typeof setTimeout>;
    
    return (...args: Parameters<T>) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => func(...args), delay);
    };
};

/**
 * Clasifica un rol en categoría
 */
export const getRoleCategory = (roleCode: string): 'admin' | 'staff' | 'user' => {
    if (roleCode === 'PLATFORM_ADMIN' || roleCode === 'COMPANY_ADMIN') {
        return 'admin';
    }
    if (roleCode === 'AGENT') {
        return 'staff';
    }
    return 'user';
};

