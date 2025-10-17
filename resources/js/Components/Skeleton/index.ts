/**
 * Skeleton Components - Sistema de Loading Profesional
 * 
 * Estructura organizada de skeletons reutilizables:
 * - Base: Componentes fundamentales (Skeleton, Input, Button, Avatar, Badge)
 * - Forms: Skeletons para formularios
 * - Cards: Skeletons para tarjetas y listas
 * - Data Display: Skeletons para tablas, gráficos, etc. (futuro)
 * 
 * @example
 * ```tsx
 * // Componentes base
 * import { Skeleton, InputSkeleton, ButtonSkeleton } from '@/Components/Skeleton';
 * 
 * // Componentes complejos
 * import { FormSkeleton, CardSkeleton, CardGridSkeleton } from '@/Components/Skeleton';
 * 
 * // En tu componente
 * {isLoading ? <FormSkeleton fields={5} withButton /> : <ActualForm />}
 * ```
 */

// ===== BASE COMPONENTS =====
export { Skeleton } from './base/Skeleton';
export { InputSkeleton } from './base/Input';
export { ButtonSkeleton } from './base/Button';
export { AvatarSkeleton } from './base/Avatar';
export { BadgeSkeleton } from './base/Badge';

// ===== FORM SKELETONS =====
export { FormSkeleton } from './forms/FormSkeleton';
export { OnboardingFormSkeleton } from './forms/OnboardingForm';

// ===== CARD SKELETONS =====
export { CardSkeleton } from './cards/Card';
export { CardGridSkeleton } from './cards/CardGrid';
export { ListItemSkeleton } from './cards/ListItem';

// ===== PAGE SKELETONS =====
export { PageSkeleton } from './page/PageSkeleton';

// ===== DATA DISPLAY SKELETONS =====
// TODO: Implementar TableSkeleton, ChartSkeleton, StatsSkeleton

