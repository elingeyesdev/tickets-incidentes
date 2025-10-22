# 🔐 Módulo de Autenticación Enterprise

> **Estado:** FASE 1 COMPLETA
> **Versión:** 1.0.0

Este directorio contiene la implementación completa del sistema de autenticación del lado del cliente. Está diseñado para ser robusto, resiliente, mantenible y seguro, siguiendo las mejores prácticas de la industria.

## Arquitectura del Módulo

El sistema está dividido en varios archivos, cada uno con una responsabilidad única (Principio de Responsabilidad Única).

### `TokenManager.ts`

Es el **corazón** del sistema. Se implementa como un **Singleton**, lo que garantiza que solo haya una instancia gestionando los tokens en toda la aplicación. Sus responsabilidades son:

- **Gestión del Ciclo de Vida:** Maneja el `AccessToken`, incluyendo su almacenamiento, recuperación y limpieza.
- **Refresco Proactivo:** Programa automáticamente el refresco del token *antes* de que expire, utilizando un buffer configurable (e.g., al 80% de su vida útil). Esto previene que el usuario experimente micro-cortes o errores de "token expirado".
- **Sistema de Eventos (Observer Pattern):** Permite que otras partes de la aplicación se suscriban a eventos clave como `onRefresh` (cuando se obtiene un nuevo token) y `onExpiry` (cuando la sesión expira definitivamente).

### `TokenRefreshService.ts` (Fase 2)

Servicio dedicado exclusivamente a la lógica de refresco del token. Se comunica con el backend para obtener un nuevo `AccessToken`.

- **Resiliencia:** Implementa una estrategia de reintentos con **Exponential Backoff + Jitter** para manejar de forma inteligente los fallos de red.
- **Prevención de "Thundering Herd":** Utiliza una cola de peticiones (`Request Queueing`) para asegurar que si 10 peticiones fallan al mismo tiempo por un token expirado, solo se realice **una** petición de refresco al backend.

### `AuthChannel.ts` (Fase 3)

Sincroniza el estado de autenticación entre múltiples pestañas del navegador.

- **Comunicación Multi-Tab:** Utiliza la `BroadcastChannel API` para una comunicación eficiente.
- **Fallback Inteligente:** Si `BroadcastChannel` no está disponible, utiliza automáticamente el evento `storage` de `localStorage` como fallback, garantizando compatibilidad con navegadores más antiguos.
- **Casos de uso:** Si el usuario hace logout en una pestaña, todas las demás pestañas se desloguean automáticamente.

### `PersistenceService.ts` (Fase 5)

Gestiona la persistencia del estado de la sesión para que el usuario no sea deslogueado al cerrar el navegador.

- **Estrategia de Persistencia:** Utiliza **IndexedDB** como almacenamiento principal por su robustez y capacidad.
- **Fallback en Cascada:** Si IndexedDB falla o no está disponible, degrada elegantemente a `localStorage` y, como último recurso, a un almacenamiento en memoria.

### `AuthMachine.ts` (Fase 4)

Implementa una **máquina de estados finitos** (usando XState) para gestionar los complejos estados de la autenticación (`initializing`, `authenticated`, `refreshing`, `error`, etc.).

- **Previene Race Conditions:** Elimina por completo los bugs relacionados con estados de carga y transiciones impredecibles.
- **Declarativo y Predecible:** Hace que el flujo de autenticación sea explícito y fácil de depurar.

---

### Archivos de Soporte

- **`types.ts`:** Contiene todas las definiciones de tipos de TypeScript. Proporciona seguridad de tipos en todo el módulo.
- **`constants.ts`:** Centraliza toda la configuración, como llaves de `localStorage`, tiempos de espera, configuración de reintentos, etc. Permite ajustar el comportamiento del sistema desde un solo lugar.
- **`utils.ts`:** Colección de funciones puras y reutilizables (validación de JWT, cálculo de delays, etc.).
- **`index.ts`:** Un "barrel file" que exporta todos los componentes públicos del módulo para permitir importaciones limpias y centralizadas.
