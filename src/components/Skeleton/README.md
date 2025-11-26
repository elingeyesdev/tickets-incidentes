# Skeleton Loading System

This directory contains a comprehensive Skeleton Loading System for the Helpdesk Mobile App. It is built using `react-native-reanimated` for high-performance animations and `nativewind` (Tailwind CSS) for styling.

## Structure

The system follows the Atomic Design methodology:

- **Base**: The core animated component (`BaseSkeleton`).
- **Atoms**: Basic building blocks (`SkeletonBox`, `SkeletonCircle`, `SkeletonText`).
- **Molecules**: Combinations of atoms (`ListItemSkeleton`, `CardSkeleton`, `FormSkeleton`).
- **Organisms**: Complex, screen-specific compositions (`TicketCardSkeleton`, `HomeSkeleton`, etc.).

## Usage

### Basic Atoms

```tsx
import { SkeletonBox, SkeletonCircle, SkeletonText } from '@/components/Skeleton';

// A simple box
<SkeletonBox width={100} height={20} />

// A circle (avatar)
<SkeletonCircle size={40} />

// Text lines
<SkeletonText lines={3} lineHeight={16} />
```

### Molecules

```tsx
import { ListItemSkeleton, CardSkeleton } from '@/components/Skeleton';

// A list item with avatar
<ListItemSkeleton withAvatar={true} lines={2} />

// A generic card
<CardSkeleton hasHeader={true} hasFooter={true} />
```

### Organisms (Screen Specific)

```tsx
import { HomeSkeleton, TicketCardSkeleton } from '@/components/Skeleton';

// Full Home Screen skeleton
if (isLoading) return <HomeSkeleton />;

// Ticket List Item skeleton
if (isLoading) {
  return (
    <View>
      {Array.from({ length: 5 }).map((_, i) => (
        <TicketCardSkeleton key={i} />
      ))}
    </View>
  );
}
```

## Customization

All components accept `style` and `className` props for further customization. The base pulse animation is handled automatically by `BaseSkeleton` and adapts to the current theme (light/dark mode).
