# 💀 Sistema de Skeleton Loading

Sistema profesional y reutilizable de skeleton screens para React + Tailwind CSS.

## 📁 Estructura

```
Components/Skeleton/
├── index.ts              # Barrel export (importar desde aquí)
├── base/                 # Componentes fundamentales
│   ├── Skeleton.tsx      # Componente base con animación shimmer
│   ├── Input.tsx         # InputSkeleton
│   ├── Button.tsx        # ButtonSkeleton
│   ├── Avatar.tsx        # AvatarSkeleton
│   └── Badge.tsx         # BadgeSkeleton
├── forms/                # Skeletons para formularios
│   ├── FormSkeleton.tsx
│   └── OnboardingForm.tsx
├── cards/                # Skeletons para tarjetas
│   ├── Card.tsx
│   ├── CardGrid.tsx
│   └── ListItem.tsx
└── data-display/         # Skeletons para datos (futuro)
    └── .gitkeep
```

## 🚀 Uso Básico

### Importación

```tsx
// Opción 1: Desde @/Components/Skeleton (recomendado)
import { Skeleton, FormSkeleton, CardSkeleton } from '@/Components/Skeleton';

// Opción 2: Desde @/Components/ui (también funciona por re-export)
import { Skeleton, FormSkeleton, CardSkeleton } from '@/Components/ui';
```

### Componente Base

```tsx
// Skeleton simple
<Skeleton className="h-4 w-full" />

// Skeleton circular (para avatares)
<Skeleton variant="circular" className="w-12 h-12" />

// Múltiples líneas de texto
<Skeleton variant="text" lines={3} lastLineWidth="70%" />
```

### Componentes Derivados

#### InputSkeleton
```tsx
<InputSkeleton withLabel />
<InputSkeleton withLabel={false} />
```

#### ButtonSkeleton
```tsx
<ButtonSkeleton />
<ButtonSkeleton fullWidth />
```

#### AvatarSkeleton
```tsx
<AvatarSkeleton size="sm" />
<AvatarSkeleton size="md" />
<AvatarSkeleton size="lg" />
```

#### BadgeSkeleton
```tsx
<BadgeSkeleton />
<BadgeSkeleton className="w-32" />
```

## 📋 Componentes Complejos

### FormSkeleton
```tsx
// Formulario básico
<FormSkeleton fields={5} />

// Con botón
<FormSkeleton fields={3} withButton />

// Layout en grid
<FormSkeleton fields={6} layout="grid" columns={2} />

// Con header y múltiples botones
<FormSkeleton 
  fields={4} 
  withHeader 
  withButton 
  withMultipleButtons 
/>
```

### OnboardingFormSkeleton
```tsx
// Skeleton específico para onboarding
<OnboardingFormSkeleton />
<OnboardingFormSkeleton fields={4} columns={2} />
```

### CardSkeleton
```tsx
// Card básico
<CardSkeleton />

// Con imagen y acciones
<CardSkeleton withImage withActions />

// Variantes
<CardSkeleton variant="horizontal" />
<CardSkeleton variant="compact" />

// Con badge
<CardSkeleton withBadge lines={2} />
```

### CardGridSkeleton
```tsx
// Grid de 6 cards en 3 columnas
<CardGridSkeleton count={6} columns={3} />

// Grid personalizado
<CardGridSkeleton 
  count={8} 
  columns={4}
  cardProps={{ withImage: true, withBadge: true }} 
/>
```

### ListItemSkeleton
```tsx
// Item de lista simple
<ListItemSkeleton />

// Con acciones
<ListItemSkeleton withActions />

// Sin avatar
<ListItemSkeleton withAvatar={false} />
```

## 🎨 Ejemplo de Uso en Componente

```tsx
import React from 'react';
import { FormSkeleton } from '@/Components/Skeleton';
import { Form } from '@/Components/forms';

export const MyComponent = () => {
  const { data, loading } = useQuery(MY_QUERY);

  if (loading) {
    return <FormSkeleton fields={5} withButton />;
  }

  return <Form data={data} />;
};
```

## 🎯 Ejemplo de Página Completa

```tsx
import React from 'react';
import { CardGridSkeleton, ListItemSkeleton } from '@/Components/Skeleton';

export const Dashboard = () => {
  const { data, loading } = useQuery(DASHBOARD_QUERY);

  if (loading) {
    return (
      <div className="space-y-8">
        <CardGridSkeleton count={3} columns={3} cardProps={{ withBadge: true }} />
        <div className="bg-white rounded-lg p-6">
          <ListItemSkeleton withActions />
          <ListItemSkeleton withActions />
          <ListItemSkeleton withActions />
        </div>
      </div>
    );
  }

  return <ActualDashboard data={data} />;
};
```

## 🔧 Creando Nuevos Skeletons

### 1. Skeleton Simple (base)
```tsx
// Components/Skeleton/base/MyComponent.tsx
import React from 'react';
import { Skeleton } from './Skeleton';

export const MyComponentSkeleton: React.FC = () => (
  <div className="space-y-2">
    <Skeleton className="h-6 w-32" />
    <Skeleton className="h-4 w-full" />
  </div>
);
```

### 2. Skeleton Complejo (con props)
```tsx
// Components/Skeleton/cards/MyCard.tsx
import React from 'react';
import { Skeleton } from '../base/Skeleton';

interface MyCardSkeletonProps {
  withImage?: boolean;
  lines?: number;
}

export const MyCardSkeleton: React.FC<MyCardSkeletonProps> = ({ 
  withImage = false, 
  lines = 3 
}) => (
  <div className="bg-white rounded-lg p-4">
    {withImage && <Skeleton className="w-full h-48 mb-4" />}
    <Skeleton variant="text" lines={lines} />
  </div>
);
```

### 3. Actualizar index.ts
```tsx
// Components/Skeleton/index.ts
export { MyComponentSkeleton } from './base/MyComponent';
export { MyCardSkeleton } from './cards/MyCard';
```

## 🎨 Animación Shimmer

La animación shimmer está definida en `resources/css/app.css`:

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

Duración: **2 segundos**  
Efecto: Brillo de izquierda a derecha que se repite infinitamente

## 📐 Diseño Consistente

Todos los skeletons mantienen consistencia con los componentes reales:

- **Radio**: `rounded-md` (base), `rounded-lg` (cards), `rounded-full` (avatares)
- **Colores**: `bg-gray-200` (light), `bg-gray-700` (dark)
- **Shimmer**: `via-white/20` (light), `via-white/10` (dark)
- **Espaciado**: `space-y-3`, `space-y-4`, `space-y-6` (según contexto)
- **Bordes**: `border border-gray-200 dark:border-gray-700`

## 🏆 Best Practices

1. **Usa el skeleton más específico disponible**
   ```tsx
   ❌ <Skeleton className="h-12 w-full rounded-lg" />
   ✅ <InputSkeleton />
   ```

2. **Mantén la estructura visual similar al componente real**
   ```tsx
   // Si tu form tiene 5 campos + botón
   <FormSkeleton fields={5} withButton />
   ```

3. **Usa grid para layouts complejos**
   ```tsx
   <CardGridSkeleton count={6} columns={3} />
   ```

4. **Combina skeletons para estructuras únicas**
   ```tsx
   <div className="flex gap-4">
     <AvatarSkeleton size="lg" />
     <div className="flex-1">
       <Skeleton className="h-6 w-48 mb-2" />
       <Skeleton variant="text" lines={2} />
     </div>
   </div>
   ```

## 🚧 Futuras Adiciones

- `TableSkeleton` (data-display)
- `ChartSkeleton` (data-display)
- `StatsSkeleton` (data-display)
- `NavbarSkeleton` (navigation)
- `SidebarSkeleton` (navigation)

## 📚 Referencias

- [Skeleton Screens - UX Pattern](https://www.nngroup.com/articles/skeleton-screens/)
- [React Skeleton Best Practices](https://blog.logrocket.com/skeleton-screens-react/)
- [Tailwind CSS Animation](https://tailwindcss.com/docs/animation)

