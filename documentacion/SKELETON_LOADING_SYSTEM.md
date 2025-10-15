# Sistema de Skeleton Loading - Documentación Completa

Sistema profesional y reutilizable de skeleton loading para React + Tailwind CSS implementado en el proyecto Helpdesk.

---

## 📦 Componentes Disponibles

### 1. **Componentes Base**

#### `<Skeleton />`
Componente base para crear cualquier tipo de skeleton.

```tsx
import { Skeleton } from '@/Components/ui';

// Skeleton rectangular
<Skeleton className="h-4 w-full" />

// Skeleton circular (avatar)
<Skeleton variant="circular" className="w-12 h-12" />

// Skeleton con bordes redondeados
<Skeleton variant="rounded" className="h-20 w-full" />

// Skeleton de texto con múltiples líneas
<Skeleton variant="text" lines={3} lastLineWidth="60%" />
```

**Props:**
- `variant?: 'rectangular' | 'circular' | 'rounded' | 'text'` - Tipo de skeleton (default: `'rectangular'`)
- `className?: string` - Clases de Tailwind para personalizar tamaño y forma
- `lines?: number` - Número de líneas (solo para `variant="text"`)
- `lastLineWidth?: string` - Ancho de la última línea (default: `'60%'`)

---

#### `<InputSkeleton />`
Skeleton para un campo de input.

```tsx
import { InputSkeleton } from '@/Components/ui';

// Input con label
<InputSkeleton withLabel />

// Input sin label
<InputSkeleton withLabel={false} />
```

**Props:**
- `className?: string`
- `withLabel?: boolean` - Mostrar skeleton de label (default: `true`)

---

#### `<ButtonSkeleton />`
Skeleton para un botón.

```tsx
import { ButtonSkeleton } from '@/Components/ui';

// Botón normal
<ButtonSkeleton />

// Botón ancho completo
<ButtonSkeleton fullWidth />
```

**Props:**
- `className?: string`
- `fullWidth?: boolean` - Ancho completo (default: `false`)

---

#### `<AvatarSkeleton />`
Skeleton circular para avatares.

```tsx
import { AvatarSkeleton } from '@/Components/ui';

// Avatar pequeño
<AvatarSkeleton size="sm" />

// Avatar mediano (default)
<AvatarSkeleton size="md" />

// Avatar grande
<AvatarSkeleton size="lg" />
```

**Props:**
- `size?: 'sm' | 'md' | 'lg'` - Tamaño del avatar (default: `'md'`)
- `className?: string`

---

#### `<BadgeSkeleton />`
Skeleton para badges/etiquetas.

```tsx
import { BadgeSkeleton } from '@/Components/ui';

<BadgeSkeleton />
```

---

### 2. **Componentes de Formulario**

#### `<FormSkeleton />`
Skeleton completo de formulario modular.

```tsx
import { FormSkeleton } from '@/Components/ui';

// Formulario básico con 3 campos
<FormSkeleton fields={3} />

// Formulario en grid de 2 columnas
<FormSkeleton fields={6} layout="grid" columns={2} />

// Formulario con header y botones
<FormSkeleton 
    fields={5} 
    withHeader 
    withButton 
    withMultipleButtons 
/>

// Formulario sin botones
<FormSkeleton fields={4} withButton={false} />
```

**Props:**
- `fields?: number` - Número de campos (default: `3`)
- `layout?: 'vertical' | 'grid'` - Layout del formulario (default: `'vertical'`)
- `columns?: 2 | 3` - Columnas del grid (default: `2`)
- `withButton?: boolean` - Mostrar botón (default: `true`)
- `withMultipleButtons?: boolean` - Mostrar múltiples botones (default: `false`)
- `withHeader?: boolean` - Mostrar header con título (default: `false`)
- `className?: string`

---

#### `<OnboardingFormSkeleton />`
Skeleton específico para formularios de onboarding (badge + título + formulario + separador + botones).

```tsx
import { OnboardingFormSkeleton } from '@/Components/ui';

// Onboarding con 2 campos en 2 columnas
<OnboardingFormSkeleton fields={2} columns={2} />

// Onboarding con 4 campos en 1 columna
<OnboardingFormSkeleton fields={4} columns={1} />
```

**Props:**
- `fields?: number` - Número de campos (default: `3`)
- `columns?: 2 | 3` - Columnas del grid (default: `2`)

---

### 3. **Componentes de Cards**

#### `<CardSkeleton />`
Skeleton de card/tarjeta.

```tsx
import { CardSkeleton } from '@/Components/ui';

// Card básico
<CardSkeleton />

// Card con imagen y acciones
<CardSkeleton withImage withActions />

// Card con badge
<CardSkeleton withBadge lines={4} />

// Card horizontal
<CardSkeleton variant="horizontal" withImage />

// Card compacto
<CardSkeleton variant="compact" withBadge />
```

**Props:**
- `withImage?: boolean` - Mostrar imagen/avatar (default: `false`)
- `withBadge?: boolean` - Mostrar badge (default: `false`)
- `withActions?: boolean` - Mostrar botones de acción (default: `false`)
- `lines?: number` - Líneas de texto (default: `3`)
- `variant?: 'default' | 'horizontal' | 'compact'` - Variante del card (default: `'default'`)
- `className?: string`

---

#### `<CardGridSkeleton />`
Grid de múltiples cards.

```tsx
import { CardGridSkeleton } from '@/Components/ui';

// Grid de 6 cards en 3 columnas
<CardGridSkeleton count={6} columns={3} />

// Grid de cards con imagen
<CardGridSkeleton 
    count={9} 
    columns={3} 
    cardProps={{ withImage: true, withBadge: true }} 
/>
```

**Props:**
- `count?: number` - Número de cards (default: `6`)
- `columns?: 1 | 2 | 3 | 4` - Columnas del grid (default: `3`)
- `cardProps?: Omit<CardSkeletonProps, 'className'>` - Props para cada card

---

#### `<ListItemSkeleton />`
Skeleton para items de lista.

```tsx
import { ListItemSkeleton } from '@/Components/ui';

// Item con avatar y acciones
<ListItemSkeleton withAvatar withActions />

// Item sin avatar
<ListItemSkeleton withAvatar={false} />
```

**Props:**
- `withAvatar?: boolean` - Mostrar avatar (default: `true`)
- `withActions?: boolean` - Mostrar botones de acción (default: `false`)
- `className?: string`

---

## 🎨 Animación Shimmer

El sistema utiliza una animación **shimmer** (efecto de brillo deslizante) en lugar de un simple `animate-pulse` para una apariencia más profesional.

La animación está definida en `/resources/css/app.css`:

```css
@keyframes shimmer {
    0% {
        transform: translateX(-100%);
    }
    100% {
        transform: translateX(100%);
    }
}
```

---

## 📋 Ejemplos de Uso Completos

### Ejemplo 1: Loading de Formulario de Login

```tsx
import { FormSkeleton } from '@/Components/ui';

function LoginPage() {
    const [isLoading, setIsLoading] = useState(true);

    if (isLoading) {
        return (
            <div className="max-w-md mx-auto">
                <FormSkeleton 
                    fields={2} 
                    withButton 
                    withHeader 
                />
            </div>
        );
    }

    return <LoginForm />;
}
```

---

### Ejemplo 2: Loading de Lista de Tickets

```tsx
import { CardGridSkeleton } from '@/Components/ui';

function TicketsList() {
    const { data, loading } = useQuery(GET_TICKETS);

    if (loading) {
        return (
            <CardGridSkeleton 
                count={12} 
                columns={3} 
                cardProps={{ 
                    withBadge: true, 
                    withActions: true,
                    variant: 'compact'
                }} 
            />
        );
    }

    return <TicketGrid tickets={data.tickets} />;
}
```

---

### Ejemplo 3: Loading de Perfil de Usuario

```tsx
import { Skeleton, AvatarSkeleton } from '@/Components/ui';

function UserProfile() {
    const { user, loading } = useAuth();

    if (loading) {
        return (
            <div className="flex items-center gap-4">
                <AvatarSkeleton size="lg" />
                <div className="flex-1 space-y-2">
                    <Skeleton className="h-6 w-48" />
                    <Skeleton className="h-4 w-64" />
                </div>
            </div>
        );
    }

    return <UserProfileCard user={user} />;
}
```

---

### Ejemplo 4: Onboarding (Ya implementado)

```tsx
import { OnboardingFormSkeleton } from '@/Components/ui';

function CompleteProfile() {
    const { user, loading } = useAuth();

    if (loading) {
        return <OnboardingFormSkeleton fields={2} columns={2} />;
    }

    return <ProfileForm user={user} />;
}
```

---

## 🔧 Cómo Extender el Sistema

### Crear un Skeleton Personalizado

Si necesitas un skeleton específico para un caso de uso particular, puedes crearlo fácilmente:

```tsx
import { Skeleton, AvatarSkeleton, BadgeSkeleton } from '@/Components/ui';

export const TicketCardSkeleton: React.FC<{ className?: string }> = ({ className }) => (
    <div className={clsx(
        'bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4',
        'space-y-4',
        className
    )}>
        {/* Header */}
        <div className="flex items-center justify-between">
            <BadgeSkeleton />
            <Skeleton className="h-4 w-16" />
        </div>

        {/* Title */}
        <Skeleton className="h-6 w-3/4" />

        {/* Description */}
        <Skeleton variant="text" lines={2} lastLineWidth="80%" />

        {/* Footer */}
        <div className="flex items-center gap-2 pt-2 border-t border-gray-200 dark:border-gray-700">
            <AvatarSkeleton size="sm" />
            <Skeleton className="h-4 w-24" />
        </div>
    </div>
);
```

---

### Personalizar la Animación

Si deseas cambiar la velocidad o el estilo de la animación shimmer, edita en `/resources/css/app.css`:

```css
/* Animación más lenta (3 segundos) */
.before\:animate-\[shimmer_3s_infinite\] {
    animation: shimmer 3s infinite;
}

/* Animación más rápida (1 segundo) */
.before\:animate-\[shimmer_1s_infinite\] {
    animation: shimmer 1s infinite;
}
```

Y úsala en el componente:

```tsx
<Skeleton className="before:animate-[shimmer_1s_infinite]" />
```

---

## ✅ Ventajas del Sistema

| Característica | Beneficio |
|----------------|-----------|
| **Modular** | Componentes reutilizables en cualquier parte |
| **Personalizable** | Props para adaptar a cualquier diseño |
| **Profesional** | Animación shimmer de alta calidad |
| **Consistente** | Estilos coherentes con el diseño real |
| **Performance** | Solo CSS, no JavaScript para animaciones |
| **Dark Mode** | Soporte automático para modo oscuro |
| **TypeScript** | Completamente tipado |

---

## 📚 Recursos Adicionales

- **Componentes Base**: `/resources/js/Components/ui/Skeleton.tsx`
- **Form Skeletons**: `/resources/js/Components/ui/FormSkeleton.tsx`
- **Card Skeletons**: `/resources/js/Components/ui/CardSkeleton.tsx`
- **CSS Animations**: `/resources/css/app.css`
- **Index Export**: `/resources/js/Components/ui/index.ts`

---

## 🎯 Mejores Prácticas

1. **Usa skeletons que coincidan con el contenido real**: Si tu card tiene un avatar, imagen y 2 líneas de texto, tu skeleton debe reflejar eso.

2. **Mantén la estructura**: Los skeletons deben tener la misma estructura HTML (contenedores, grids, etc.) que el contenido real.

3. **No abuses de los skeletons**: Para operaciones muy rápidas (<200ms), considera no mostrar skeleton.

4. **Usa variantes específicas**: Prefiere `<OnboardingFormSkeleton />` sobre construir manualmente con `<Skeleton />`.

5. **Testea en modo oscuro**: Asegúrate de que los skeletons se vean bien en ambos temas.

---

**¡El sistema de skeleton loading está listo para usar en todo el proyecto!** 🎉


