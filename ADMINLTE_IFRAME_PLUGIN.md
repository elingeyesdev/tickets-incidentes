# AdminLTE v3 - IFrame Mode Plugin

## Descripción

El **IFrame Mode** es un widget nativo de AdminLTE v3 que permite abrir múltiples páginas/vistas en pestañas dentro de la misma aplicación usando iframes.

## Propósito

- Cargar múltiples URLs en pestañas sin navegar
- Mantener el sidebar y navbar principal visible
- Gestionar múltiples contenidos simultáneamente

## Navbar Adicional (Tabs Bar)

El plugin genera automáticamente una barra de navegación adicional con:

```html
<div class="nav navbar navbar-expand navbar-white navbar-light border-bottom p-0">
    <!-- Botón Close (Dropdown) -->
    <div class="nav-item dropdown">
        <a class="nav-link bg-danger dropdown-toggle">Close</a>
        <div class="dropdown-menu">
            <a href="#" data-widget="iframe-close" data-type="all">Close All</a>
            <a href="#" data-widget="iframe-close" data-type="all-other">Close Others</a>
        </div>
    </div>

    <!-- Scroll Left -->
    <a class="nav-link bg-light" data-widget="iframe-scrollleft">
        <i class="fas fa-angle-double-left"></i>
    </a>

    <!-- Lista de Tabs -->
    <ul class="navbar-nav overflow-hidden" role="tablist">
        <!-- Las pestañas se agregan aquí dinámicamente -->
    </ul>

    <!-- Scroll Right -->
    <a class="nav-link bg-light" data-widget="iframe-scrollright">
        <i class="fas fa-angle-double-right"></i>
    </a>

    <!-- Fullscreen -->
    <a class="nav-link bg-light" data-widget="iframe-fullscreen">
        <i class="fas fa-expand"></i>
    </a>
</div>
```

## Ubicación del Código

| Archivo | Líneas | Propósito |
|---------|--------|-----------|
| `public/vendor/adminlte/dist/js/adminlte.js` | 1196-1450+ | Widget JavaScript |
| `resources/views/vendor/adminlte/views/partials/cwrapper/cwrapper-iframe.blade.php` | 1-118 | Componente Blade |
| `config/adminlte.php` | 517-535 | Configuración |

## ¿Qué tan Fundamental es?

**Opcional.** No es necesario para AdminLTE v3 funcionar. Es una característica adicional para casos específicos de navegación avanzada.

## Configuración en config/adminlte.php

```php
'iframe' => [
    'default_tab' => [
        'url' => '/ruta-por-defecto',      // URL que carga al inicio
        'title' => 'Título de la pestaña',
    ],
    'buttons' => [
        'close' => true,                   // Botón close
        'close_all' => true,               // Close all
        'close_all_other' => true,         // Close others
        'scroll_left' => true,             // Scroll izquierda
        'scroll_right' => true,            // Scroll derecha
        'fullscreen' => true,              // Fullscreen
    ],
    'options' => [
        'loading_screen' => 1000,          // Tiempo de loading (ms)
        'auto_show_new_tab' => true,       // Auto-mostrar nueva pestaña
        'use_navbar_items' => true,        // Usar items del navbar principal
    ],
],
```

## Para Seguir Prácticas de AdminLTE

### 1. Usar el Layout Correcto
Crear un layout que herede de `authenticated.blade.php` pero con clase `iframe-mode`:

```blade
<div class="content-wrapper iframe-mode" data-widget="iframe" ...>
    <!-- Navbar de tabs aquí -->
    <!-- Tab content aquí -->
</div>
```

### 2. Inicialización Automática
AdminLTE v3 inicializa el widget automáticamente si encuentra:
- Elemento con `data-widget="iframe"`
- Estructura HTML correcta (navbar + tab-content)

### 3. JavaScript para Crear Tabs
```javascript
// El widget proporciona automáticamente:
$.data(element, 'lte.iframe').createTab(title, url, uniqueName, autoOpen);
```

### 4. Estructura de Rutas
Las rutas que se cargan en iframe deben retornar:
- **Full HTML** (incluyendo navbar, sidebar, etc) - Si es una página completa
- **Partial HTML** - Si solo devuelves contenido

### 5. Validación en Rutas
El middleware JWT debe estar en las rutas que se cargan en iframe, exactamente como cualquier otra ruta.

## Limitaciones Conocidas

- Los iframes comparten localStorage con el mismo origen
- No funcionan bien con navegación de sidebar (el sidebar no se actualiza)
- Cada iframe carga una página completa (más overhead)
- Los datos entre pestañas no se sincronizan automáticamente

## Alternativa: No Usar IFrame

Si quieres evitar iframes, usa AJAX + Alpine.js en su lugar:
- Más moderno
- Sin limitaciones de iframe
- Mejor performance
- Es lo que hacen Gmail, Slack, etc.
