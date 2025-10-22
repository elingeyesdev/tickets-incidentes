/**
 * EJEMPLOS DE USO - Sistema de Skeleton Loading
 * 
 * Este archivo contiene ejemplos prácticos de cómo usar los skeletons
 * en diferentes escenarios reales del proyecto Helpdesk.
 * 
 * 🚨 IMPORTANTE: Este archivo es solo de referencia, NO se importa en la app.
 */

import React from 'react';
import { 
  // Base
  Skeleton, 
  InputSkeleton, 
  ButtonSkeleton,
  AvatarSkeleton,
  BadgeSkeleton,
  // Forms
  FormSkeleton,
  OnboardingFormSkeleton as _OnboardingFormSkeleton, // Example component, intentionally unused
  // Cards
  CardSkeleton,
  CardGridSkeleton,
  ListItemSkeleton 
} from '@/Components/Skeleton';

// ====================================================================
// EJEMPLO 1: Login/Register Form
// ====================================================================
export const LoginFormSkeleton = () => (
  <div className="max-w-md mx-auto bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
    {/* Logo */}
    <Skeleton className="h-12 w-32 mx-auto mb-6" />
    
    {/* Título */}
    <Skeleton className="h-8 w-48 mx-auto mb-2" />
    <Skeleton className="h-4 w-64 mx-auto mb-8" />
    
    {/* Campos */}
    <div className="space-y-4">
      <InputSkeleton withLabel />
      <InputSkeleton withLabel />
    </div>
    
    {/* Botones */}
    <div className="space-y-3 mt-6">
      <ButtonSkeleton fullWidth />
      <ButtonSkeleton fullWidth />
    </div>
    
    {/* Link inferior */}
    <Skeleton className="h-4 w-56 mx-auto mt-6" />
  </div>
);

// ====================================================================
// EJEMPLO 2: Dashboard con Stats Cards
// ====================================================================
export const DashboardSkeleton = () => (
  <div className="space-y-8">
    {/* Header */}
    <div className="flex items-center justify-between">
      <div>
        <Skeleton className="h-8 w-64 mb-2" />
        <Skeleton className="h-4 w-96" />
      </div>
      <ButtonSkeleton className="w-40" />
    </div>
    
    {/* Stats Cards (4 columnas) */}
    <CardGridSkeleton 
      count={4} 
      columns={4}
      cardProps={{ variant: 'compact', withBadge: true }}
    />
    
    {/* Charts Section */}
    <div className="grid grid-cols-2 gap-6">
      <CardSkeleton withBadge lines={0} className="h-64" />
      <CardSkeleton withBadge lines={0} className="h-64" />
    </div>
    
    {/* Recent Activity */}
    <div className="bg-white dark:bg-gray-800 rounded-lg p-6">
      <Skeleton className="h-6 w-48 mb-4" />
      <ListItemSkeleton withAvatar withActions />
      <ListItemSkeleton withAvatar withActions />
      <ListItemSkeleton withAvatar withActions />
    </div>
  </div>
);

// ====================================================================
// EJEMPLO 3: Lista de Tickets
// ====================================================================
export const TicketsListSkeleton = () => (
  <div className="space-y-6">
    {/* Header con filtros */}
    <div className="flex items-center justify-between">
      <Skeleton className="h-8 w-48" />
      <div className="flex gap-3">
        <Skeleton className="h-10 w-32" />
        <Skeleton className="h-10 w-32" />
        <ButtonSkeleton className="w-40" />
      </div>
    </div>
    
    {/* Lista de tickets */}
    <div className="space-y-3">
      {Array.from({ length: 5 }).map((_, i) => (
        <div key={i} className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
          <div className="flex items-center justify-between mb-3">
            <div className="flex items-center gap-3">
              <BadgeSkeleton />
              <Skeleton className="h-5 w-64" />
            </div>
            <BadgeSkeleton className="w-16" />
          </div>
          <Skeleton variant="text" lines={2} lastLineWidth="70%" />
          <div className="flex items-center gap-4 mt-3">
            <div className="flex items-center gap-2">
              <AvatarSkeleton size="sm" />
              <Skeleton className="h-4 w-24" />
            </div>
            <Skeleton className="h-4 w-32" />
          </div>
        </div>
      ))}
    </div>
    
    {/* Paginación */}
    <div className="flex justify-center gap-2">
      {Array.from({ length: 5 }).map((_, i) => (
        <Skeleton key={i} className="w-10 h-10 rounded-lg" />
      ))}
    </div>
  </div>
);

// ====================================================================
// EJEMPLO 4: Perfil de Usuario
// ====================================================================
export const UserProfileSkeleton = () => (
  <div className="space-y-6">
    {/* Card de perfil */}
    <div className="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-8">
      <div className="flex items-start gap-6">
        {/* Avatar grande */}
        <AvatarSkeleton size="lg" className="w-24 h-24" />
        
        <div className="flex-1">
          {/* Nombre y badge */}
          <div className="flex items-center gap-3 mb-2">
            <Skeleton className="h-8 w-48" />
            <BadgeSkeleton />
          </div>
          
          {/* Email */}
          <Skeleton className="h-4 w-64 mb-4" />
          
          {/* Stats */}
          <div className="flex gap-6">
            <div>
              <Skeleton className="h-6 w-12 mb-1" />
              <Skeleton className="h-3 w-16" />
            </div>
            <div>
              <Skeleton className="h-6 w-12 mb-1" />
              <Skeleton className="h-3 w-16" />
            </div>
            <div>
              <Skeleton className="h-6 w-12 mb-1" />
              <Skeleton className="h-3 w-16" />
            </div>
          </div>
        </div>
        
        {/* Botón editar */}
        <ButtonSkeleton className="w-32" />
      </div>
    </div>
    
    {/* Tabs */}
    <div className="flex gap-4 border-b border-gray-200 dark:border-gray-700">
      <Skeleton className="h-10 w-32" />
      <Skeleton className="h-10 w-32" />
      <Skeleton className="h-10 w-32" />
    </div>
    
    {/* Contenido de tabs */}
    <FormSkeleton fields={6} layout="grid" columns={2} withButton />
  </div>
);

// ====================================================================
// EJEMPLO 5: Tabla de Datos
// ====================================================================
export const DataTableSkeleton = () => (
  <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
    {/* Header de tabla */}
    <div className="p-4 border-b border-gray-200 dark:border-gray-700">
      <div className="flex items-center justify-between">
        <Skeleton className="h-6 w-48" />
        <div className="flex gap-3">
          <Skeleton className="h-10 w-32" />
          <ButtonSkeleton className="w-32" />
        </div>
      </div>
    </div>
    
    {/* Tabla */}
    <div className="overflow-x-auto">
      <table className="w-full">
        <thead className="bg-gray-50 dark:bg-gray-900">
          <tr>
            {Array.from({ length: 5 }).map((_, i) => (
              <th key={i} className="px-6 py-3">
                <Skeleton className="h-4 w-24" />
              </th>
            ))}
          </tr>
        </thead>
        <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
          {Array.from({ length: 8 }).map((_, i) => (
            <tr key={i}>
              {Array.from({ length: 5 }).map((_, j) => (
                <td key={j} className="px-6 py-4">
                  {j === 0 ? (
                    <div className="flex items-center gap-3">
                      <AvatarSkeleton size="sm" />
                      <Skeleton className="h-4 w-32" />
                    </div>
                  ) : j === 4 ? (
                    <BadgeSkeleton />
                  ) : (
                    <Skeleton className="h-4 w-24" />
                  )}
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
    
    {/* Footer con paginación */}
    <div className="p-4 border-t border-gray-200 dark:border-gray-700">
      <div className="flex items-center justify-between">
        <Skeleton className="h-4 w-48" />
        <div className="flex gap-2">
          {Array.from({ length: 5 }).map((_, i) => (
            <Skeleton key={i} className="w-10 h-10 rounded-lg" />
          ))}
        </div>
      </div>
    </div>
  </div>
);

// ====================================================================
// EJEMPLO 6: Modal/Dialog
// ====================================================================
export const ModalSkeleton = () => (
  <div className="fixed inset-0 bg-black/50 flex items-center justify-center p-4">
    <div className="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-md w-full p-6">
      {/* Header */}
      <div className="flex items-center justify-between mb-6">
        <Skeleton className="h-6 w-48" />
        <Skeleton className="w-8 h-8 rounded-lg" />
      </div>
      
      {/* Body */}
      <FormSkeleton fields={3} withButton={false} />
      
      {/* Footer */}
      <div className="flex justify-end gap-3 mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
        <ButtonSkeleton className="w-24" />
        <ButtonSkeleton className="w-32" />
      </div>
    </div>
  </div>
);

// ====================================================================
// EJEMPLO 7: Sidebar/Navigation
// ====================================================================
export const SidebarSkeleton = () => (
  <div className="w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 p-4">
    {/* Logo */}
    <Skeleton className="h-10 w-40 mb-8" />
    
    {/* Navigation items */}
    <div className="space-y-2">
      {Array.from({ length: 8 }).map((_, i) => (
        <div key={i} className="flex items-center gap-3 p-3">
          <Skeleton className="w-5 h-5" />
          <Skeleton className="h-4 flex-1" />
        </div>
      ))}
    </div>
    
    {/* Divider */}
    <div className="my-6 border-t border-gray-200 dark:border-gray-700" />
    
    {/* User menu */}
    <div className="flex items-center gap-3 p-3">
      <AvatarSkeleton size="md" />
      <div className="flex-1">
        <Skeleton className="h-4 w-24 mb-1" />
        <Skeleton className="h-3 w-32" />
      </div>
    </div>
  </div>
);

// ====================================================================
// EJEMPLO 8: Chat/Messages
// ====================================================================
export const ChatSkeleton = () => (
  <div className="h-screen flex">
    {/* Lista de conversaciones */}
    <div className="w-80 border-r border-gray-200 dark:border-gray-700 p-4">
      <Skeleton className="h-10 w-full mb-4 rounded-lg" />
      <div className="space-y-2">
        {Array.from({ length: 10 }).map((_, i) => (
          <div key={i} className="flex items-center gap-3 p-3 rounded-lg">
            <AvatarSkeleton size="md" />
            <div className="flex-1">
              <Skeleton className="h-4 w-32 mb-1" />
              <Skeleton className="h-3 w-48" />
            </div>
            <BadgeSkeleton className="w-6 h-6 rounded-full" />
          </div>
        ))}
      </div>
    </div>
    
    {/* Área de chat */}
    <div className="flex-1 flex flex-col">
      {/* Header */}
      <div className="p-4 border-b border-gray-200 dark:border-gray-700">
        <div className="flex items-center gap-3">
          <AvatarSkeleton size="md" />
          <div className="flex-1">
            <Skeleton className="h-5 w-32 mb-1" />
            <Skeleton className="h-3 w-24" />
          </div>
          <ButtonSkeleton className="w-10 h-10 rounded-lg" />
        </div>
      </div>
      
      {/* Mensajes */}
      <div className="flex-1 p-4 space-y-4">
        {Array.from({ length: 6 }).map((_, i) => (
          <div key={i} className={`flex gap-3 ${i % 2 === 0 ? '' : 'flex-row-reverse'}`}>
            <AvatarSkeleton size="sm" />
            <div className={`flex-1 ${i % 2 === 0 ? 'max-w-md' : 'max-w-md ml-auto'}`}>
              <Skeleton className="h-4 w-24 mb-2" />
              <Skeleton variant="text" lines={2} lastLineWidth="80%" className="bg-gray-100 dark:bg-gray-700 p-3 rounded-lg" />
            </div>
          </div>
        ))}
      </div>
      
      {/* Input de mensaje */}
      <div className="p-4 border-t border-gray-200 dark:border-gray-700">
        <div className="flex gap-3">
          <Skeleton className="h-12 flex-1 rounded-lg" />
          <ButtonSkeleton className="w-12 h-12 rounded-lg" />
        </div>
      </div>
    </div>
  </div>
);

// ====================================================================
// EJEMPLO 9: Uso en un componente real
// ====================================================================
export const RealWorldExample = () => {
  const [loading, setLoading] = React.useState(true);

  // Simular carga
  React.useEffect(() => {
    setTimeout(() => setLoading(false), 2000);
  }, []);

  if (loading) {
    return <DashboardSkeleton />;
  }

  return (
    <div>
      {/* Contenido real aquí */}
      <h1>Dashboard Real</h1>
    </div>
  );
};

