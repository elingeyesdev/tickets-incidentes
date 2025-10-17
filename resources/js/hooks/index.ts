/**
 * Barrel Export - Global Hooks
 * Centraliza todos los hooks (propios + contextos) para imports unificados
 */

// Context Hooks (re-export desde contexts)
export {
    useAuth,
    useTheme,
    useLocale,
    useNotification,
} from '@/contexts';

// Custom Hooks
export { useForm } from './useForm';
export { usePermissions } from './usePermissions';

// TODO: Agregar aquí hooks futuros:
// export { useDebounce } from './useDebounce';
// export { usePagination } from './usePagination';
// export { useLocalStorage } from './useLocalStorage';
// export { useMediaQuery } from './useMediaQuery';
// export { useOnClickOutside } from './useOnClickOutside';
