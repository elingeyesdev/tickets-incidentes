# Implementación Inline de Validación y Control de Errores - Announcements

## Descripción General

Se ha implementado un sistema completo de validación profesional y control de errores inline en la vista `resources/views/app/company-admin/announcements/manage.blade.php` siguiendo el patrón de `blade-components-jquery.mdc`.

## Cambios Implementados

### 1. **Manejo Profesional de Errores de API (ErrorHandler)**

Se implementó un manejador robusto de errores que procesa todos los códigos HTTP de manera específica:

#### Códigos HTTP Manejados:

- **400 Bad Request**: Mensajes específicos según el error
  - "El anuncio ya está publicado"
  - "El anuncio no está programado"
  - "No se puede editar un anuncio publicado"
  - "La fecha de fin debe ser posterior a la fecha de inicio"
  - Y más...

- **401 Unauthorized**: Sesión expirada con redirección automática al login

- **403 Forbidden**: Permisos insuficientes

- **404 Not Found**: Anuncio no encontrado o eliminado

- **422 Validation Error**: Errores de validación detallados
  - Toast con lista de errores formateada
  - Resaltado automático de campos con error
  - Traducción de nombres técnicos a nombres legibles
  - Mapeo automático de campos a IDs del formulario

- **500 Internal Server Error**: Error del servidor

#### Características:

```javascript
ErrorHandler.handleApiError(response, data)
```

- Traduce nombres de campos técnicos a español
- Muestra toasts profesionales con toastr
- Resalta campos con error en el formulario
- Limpia errores previos automáticamente

### 2. **Validación con jQuery Validation Plugin (FormValidator)**

Se implementó validación profesional usando jQuery Validation siguiendo el patrón AdminLTE:

#### Características:

- Validación en tiempo real
- Mensajes personalizados en español
- Resaltado visual de campos con error
- Ocultación de textos de ayuda cuando hay error

#### Reglas Básicas:

- **Tipo de anuncio**: Requerido
- **Título**: Requerido, mínimo 5 caracteres, máximo 255
- **Contenido**: Requerido, mínimo 10 caracteres

#### Reglas Dinámicas por Tipo:

**MAINTENANCE:**
- Fecha de inicio: Requerida
- Fecha de fin: Requerida
- Validación cruzada: Fin debe ser posterior al inicio

**NEWS:**
- Tipo de noticia: Requerido
- Resumen: Requerido, máximo 200 caracteres

**ALERT:**
- Mensaje: Requerido, máximo 200 caracteres

### 3. **Campos Controlados con Select2 (SchemaHandler)**

Se implementaron campos controlados usando Select2 en lugar de campos de texto libre:

#### Servicios Afectados (14 opciones predefinidas):

1. API
2. Aplicación Web
3. Aplicación Móvil
4. Base de Datos
5. Correo Electrónico
6. Autenticación
7. Almacenamiento de Archivos
8. Pasarela de Pago
9. Sistema de Notificaciones
10. Reportes
11. Búsqueda
12. Chat de Soporte
13. CDN
14. Sistema de Respaldo

#### Audiencia Objetivo (para NEWS):

- Usuarios
- Agentes
- Administradores

#### Características Select2:

- Tema Bootstrap 4
- Multi-selección
- Permite crear opciones personalizadas (tags)
- Búsqueda integrada
- Separadores de tokens (coma)
- Placeholders descriptivos

### 4. **Patrón IIFE con jQuery Availability Check**

El código completo está envuelto en un IIFE (Immediately Invoked Function Expression) que verifica la disponibilidad de jQuery:

```javascript
(function() {
    'use strict';

    function initializeAnnouncementsModule() {
        // Todo el código del módulo
    }

    // jQuery Availability Check
    if (typeof jQuery !== 'undefined') {
        $(document).ready(initializeAnnouncementsModule);
    } else {
        var checkInterval = setInterval(function() {
            if (typeof jQuery !== 'undefined') {
                clearInterval(checkInterval);
                $(document).ready(initializeAnnouncementsModule);
            } else if (jQueryCheckAttempts >= maxAttempts) {
                console.error('[Announcements] jQuery failed to load');
                clearInterval(checkInterval);
            }
            jQueryCheckAttempts++;
        }, 100);
    }
})();
```

### 5. **Integración en Funciones Existentes**

Se actualizaron las siguientes funciones para usar los nuevos módulos:

#### `createDraft()`
- Validación con `FormValidator.validateForm(false)`
- Manejo de errores con `ErrorHandler.handleApiError()`
- Limpieza de Select2 con `SchemaHandler.destroyAllSelect2()`

#### `updateAnnouncement()`
- Validación con `FormValidator.validateForm(true)`
- Manejo de errores con `ErrorHandler.handleApiError()`
- Limpieza de Select2

#### `publishAnnouncement()`
- Manejo de errores con `ErrorHandler.handleApiError()`

#### `updateMetadataFields()`
- Generación de campos Select2 con `SchemaHandler.generateAffectedServicesField()`
- Generación de audiencia con `SchemaHandler.generateTargetAudienceField()`
- Inicialización automática de Select2
- Validación de fechas para MAINTENANCE
- Reglas dinámicas con `FormValidator.addDynamicRules()`

#### `buildMetadata()` y `buildEditMetadata()`
- Obtención de valores desde Select2 usando `$('#field-id').val()`
- Arrays correctos en lugar de strings separados por comas

## Dependencias CDN Agregadas

```html
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css" rel="stylesheet" />

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- jQuery Validation -->
<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/additional-methods.min.js"></script>
```

## Archivos de Referencia Conservados

Los siguientes archivos se mantienen como referencia de la implementación modular original:

- `public/js/announcements-validation.js` (512 líneas)
- `public/js/announcements-schema-handler.js` (322 líneas)
- `public/test-announcements-modules.html`

## Ventajas de la Implementación Inline

1. **Simplicidad**: Todo el código en un solo archivo
2. **Sin dependencias externas**: No requiere archivos JS separados
3. **Patrón consistente**: Sigue blade-components-jquery.mdc
4. **Mejor mantenibilidad**: Código junto a la vista relacionada
5. **Carga optimizada**: Sin peticiones HTTP adicionales

## Testing

### Escenarios a Probar:

1. **Validación de formulario**:
   - Dejar campos vacíos
   - Ingresar títulos muy cortos/largos
   - Verificar mensajes en español

2. **Errores de API**:
   - Intentar publicar un borrador inválido
   - Intentar editar un anuncio publicado
   - Verificar redirección en sesión expirada

3. **Select2**:
   - Seleccionar múltiples servicios
   - Crear servicios personalizados
   - Verificar que se envíen como array

4. **Fechas de mantenimiento**:
   - Fecha fin antes que inicio
   - Verificar mensaje de error

5. **Audiencia (NEWS)**:
   - Seleccionar múltiples audiencias
   - Verificar que se requiera al menos una

## Logs de Consola

El sistema incluye logs detallados para debugging:

```
[Announcements] Initializing module...
[Validator] Create form validation initialized
[Validator] Edit form validation initialized
[Validator] Adding dynamic rules for type: MAINTENANCE
[Schema Handler] Select2 initialized for #meta-services
[Schema Handler] Date validation setup for meta-scheduled-start and meta-scheduled-end
[Announcements] API Error: { status: 422, data: {...} }
[Validation Error] {...}
```

## Mantenimiento Futuro

### Para agregar un nuevo tipo de anuncio:

1. Agregar caso en `updateMetadataFields()`
2. Agregar caso en `buildMetadata()`
3. Agregar reglas en `FormValidator.addDynamicRules()`
4. Agregar traducción en `ErrorHandler.getFieldDisplayName()`

### Para agregar más servicios:

Modificar `SchemaHandler.commonServices`:

```javascript
{ value: 'nuevo_servicio', label: 'Nuevo Servicio' }
```

### Para personalizar validaciones:

Modificar las reglas en `FormValidator.initCreateForm()` o `FormValidator.addDynamicRules()`

## Compatibilidad

- jQuery 3.x
- jQuery Validation 1.19.5
- Select2 4.1.0
- Bootstrap 4
- AdminLTE 3
- Toastr (incluido en AdminLTE)

## Estado de Implementación

✅ Error handling profesional para todos los códigos HTTP
✅ Validación jQuery con reglas dinámicas
✅ Campos controlados con Select2
✅ Patrón IIFE con jQuery check
✅ Integración en funciones existentes
✅ Limpieza automática de Select2
✅ Validación cruzada de fechas
✅ Mensajes en español
✅ Logs de debugging
✅ Sin errores de sintaxis

## Notas Importantes

- La implementación NO requiere cambios en el backend
- Todos los archivos JS externos se mantienen solo como referencia
- El código inline es completamente autosuficiente
- Los toasts requieren toastr (incluido en AdminLTE)
