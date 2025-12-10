# Guía Rápida - Sistema de Announcements Mejorado

## 🚀 Para Empezar

### 1. Verificar que los archivos estén cargados

Abrir DevTools (F12) en `manage.blade.php` y verificar en Console:

```javascript
console.log(AnnouncementsErrorHandler);    // Debe mostrar el objeto
console.log(AnnouncementsValidator);       // Debe mostrar el objeto
console.log(AnnouncementsSchemaHandler);   // Debe mostrar el objeto
```

Si alguno muestra `undefined`, revisar que los scripts estén incluidos en el blade.

---

## 📝 Crear un Anuncio

### Flujo Normal

1. **Usuario selecciona tipo** → Campos se actualizan automáticamente
2. **Usuario rellena formulario** → Validación en tiempo real
3. **Usuario hace click en "Guardar"** → Validación completa
4. **Si hay errores** → Se muestran debajo de cada campo
5. **Si está correcto** → Se envía a la API
6. **API responde** → Manejo según código HTTP

### Validaciones por Tipo

#### MAINTENANCE
```
✓ Título: mínimo 5 caracteres
✓ Contenido: mínimo 10 caracteres
✓ Inicio Programado: requerido, formato datetime
✓ Fin Programado: requerido, debe ser después del inicio
✓ Urgencia: LOW, MEDIUM, HIGH
✓ Servicios: opcional, multi-select
```

#### INCIDENT
```
✓ Título: mínimo 5 caracteres
✓ Contenido: mínimo 10 caracteres
✓ Urgencia: LOW, MEDIUM, HIGH, CRITICAL
✓ Servicios: opcional, multi-select
```

#### NEWS
```
✓ Título: mínimo 5 caracteres
✓ Contenido: mínimo 10 caracteres
✓ Tipo de Noticia: requerido (enum)
✓ Audiencia: requerido, multi-select
✓ Resumen: requerido, max 200 caracteres
```

#### ALERT
```
✓ Título: mínimo 5 caracteres
✓ Contenido: mínimo 10 caracteres
✓ Urgencia: HIGH o CRITICAL solamente
✓ Tipo de Alerta: requerido (enum)
✓ Mensaje: requerido, max 200 caracteres
✓ Acción Requerida: checkbox
✓ Descripción Acción: requerido SI checkbox = true
✓ Servicios: opcional, multi-select
```

---

## ❌ Manejo de Errores

### Códigos HTTP y Respuestas

| Código | Qué Significa                      | Qué Hace el Sistema                                  |
|--------|-----------------------------------|------------------------------------------------------|
| 200/201| Éxito                             | Toast verde "Operación exitosa"                      |
| 400    | Solicitud incorrecta              | Toast amarillo con mensaje específico                |
| 401    | No autenticado                    | Toast rojo + redirección a /login en 2s              |
| 403    | Sin permisos                      | Toast rojo "No tiene permisos"                       |
| 404    | No encontrado                     | Toast rojo "El anuncio no existe"                    |
| 422    | Errores de validación             | Toast rojo con lista de errores + campos resaltados  |
| 500    | Error del servidor                | Toast rojo "Error interno del servidor"              |

### Ejemplo de Error 422

**Respuesta de API:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "title": ["The title must be at least 5 characters."],
    "metadata.urgency": ["The urgency is required."]
  }
}
```

**Lo que ve el usuario:**
```
🔴 Errores de validación:
• Título: El título debe tener al menos 5 caracteres
• Urgencia: La urgencia es obligatoria
```

**En el formulario:**
- Campo `title` tiene borde rojo
- Debajo del campo aparece el mensaje de error
- El texto de ayuda se oculta mientras hay error

---

## 🎨 Select2 - Campos Controlados

### Servicios Afectados

**Características:**
- 14 servicios predefinidos
- Búsqueda en tiempo real
- Selección múltiple
- Puede agregar servicios personalizados

**Uso:**
```javascript
// Obtener servicios seleccionados
const services = AnnouncementsSchemaHandler.getSelectedServices('meta-services');
console.log(services); // ['api', 'database', 'email']
```

**HTML generado:**
```html
<select id="meta-services" class="select2-services" multiple>
    <option value="api">API</option>
    <option value="web_application">Aplicación Web</option>
    <!-- ... más opciones -->
</select>
```

### Audiencia Objetivo (NEWS)

**Valores permitidos:**
- `users` → Usuarios
- `agents` → Agentes
- `admins` → Administradores

**Por defecto se seleccionan:** `users` y `agents`

---

## 🔧 Funciones de Utilidad

### Validar Formulario Manualmente

```javascript
// Validar formulario de creación
const isValid = AnnouncementsValidator.validateForm(false);
if (isValid) {
    console.log('✓ Formulario válido');
} else {
    console.log('✗ Formulario tiene errores');
}

// Validar formulario de edición
const isValid = AnnouncementsValidator.validateForm(true);
```

### Agregar Validaciones Dinámicas

```javascript
// Al cambiar el tipo de anuncio
AnnouncementsValidator.addDynamicRules('MAINTENANCE', false);
```

### Obtener Valores de Select2

```javascript
// Servicios afectados
const services = AnnouncementsSchemaHandler.getSelectedServices('meta-services');

// Audiencia objetivo
const audience = $('#meta-target-audience').val();
```

### Limpiar Formularios

```javascript
// Resetear validaciones
const validator = $('#form-create').validate();
validator.resetForm();
$('.form-control').removeClass('is-invalid');

// Destruir Select2
AnnouncementsSchemaHandler.destroyAllSelect2();

// Limpiar campos
document.getElementById('form-create').reset();
document.getElementById('metadata-fields').innerHTML = '';
```

---

## 🐛 Debugging

### Verificar que el token esté disponible

```javascript
const token = window.tokenManager?.getAccessToken();
console.log('Token:', token ? 'Disponible' : 'NO DISPONIBLE');
```

### Ver errores de validación

```javascript
// En la consola después de intentar guardar
// Los errores se logean automáticamente
```

### Verificar estado de Select2

```javascript
// Ver si Select2 está inicializado
const $select = $('#meta-services');
console.log('Select2 inicializado:', $select.data('select2') ? 'SÍ' : 'NO');

// Ver valores seleccionados
console.log('Valores:', $select.val());
```

### Ver schema cargado

```javascript
console.log('Schema:', AnnouncementsSchemaHandler.schema);
```

---

## 📋 Checklist de Troubleshooting

### ❓ Los módulos no cargan

- [ ] Verificar que los scripts estén en `@section('js')`
- [ ] Verificar rutas de archivos JS
- [ ] Abrir consola y buscar errores 404
- [ ] Verificar que jQuery esté cargado primero

### ❓ Select2 no funciona

- [ ] Verificar que Select2 CSS y JS estén incluidos
- [ ] Verificar que jQuery esté cargado
- [ ] Verificar que el campo tenga clase `select2-services` o `select2-audience`
- [ ] Verificar que `initAffectedServicesSelect()` se llame después de renderizar HTML

### ❓ Validaciones no funcionan

- [ ] Verificar que jQuery Validation Plugin esté cargado
- [ ] Verificar que los campos tengan atributo `name`
- [ ] Verificar que `initCreateFormValidation()` o `initEditFormValidation()` se haya llamado
- [ ] Ver consola para errores

### ❓ Errores de API no se manejan bien

- [ ] Verificar que `AnnouncementsErrorHandler` esté definido
- [ ] Verificar que las funciones usen el patrón correcto:
```javascript
.then(response => {
    return response.json().then(data => ({ response, data }));
})
.then(({ response, data }) => {
    if (response.ok && data.success) {
        // éxito
    } else {
        AnnouncementsErrorHandler.handleApiError(response, data);
    }
})
```

---

## 💡 Tips y Mejores Prácticas

### 1. Siempre validar antes de enviar

```javascript
if (AnnouncementsValidator.validateForm(false)) {
    // Enviar a API
}
```

### 2. Deshabilitar botones durante el envío

```javascript
const $btn = $('#btn-create-draft');
$btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

// ... hacer fetch ...

// Siempre restaurar al final
$btn.prop('disabled', false).html(originalText);
```

### 3. Limpiar formularios al cerrar modales

```javascript
$('#modal-create').on('hidden.bs.modal', function() {
    const validator = $('#form-create').validate();
    validator.resetForm();
    $('.form-control').removeClass('is-invalid');
    AnnouncementsSchemaHandler.destroyAllSelect2();
    document.getElementById('form-create').reset();
    document.getElementById('metadata-fields').innerHTML = '';
});
```

### 4. Usar console.log para debugging

Los módulos ya incluyen logs útiles:

```
[Announcements Manage] Initializing...
[Announcements Manage] DOM Ready - Initializing
[Announcements Manage] Schema loaded successfully
[Validator] Create form validation initialized
[Schema Handler] Select2 initialized for #meta-services
```

---

## 📞 Soporte

Si encuentras problemas:

1. **Revisar consola del navegador** para errores
2. **Verificar que todos los scripts estén cargados**
3. **Revisar el documento MEJORAS_ANNOUNCEMENTS_MANAGE.md**
4. **Ejecutar tests en test-announcements-modules.html**

---

## 🎯 Casos de Uso Comunes

### Agregar un nuevo servicio a la lista

Editar `announcements-schema-handler.js`:

```javascript
commonServices: [
    // ... servicios existentes ...
    { value: 'nuevo_servicio', label: 'Nuevo Servicio' }
]
```

### Cambiar validaciones de un campo

Editar `announcements-validation.js`:

```javascript
rules: {
    'title': {
        required: true,
        minlength: 10, // Cambiar de 5 a 10
        maxlength: 255
    }
}
```

### Agregar un nuevo tipo de error HTTP

Editar `announcements-validation.js`:

```javascript
handleApiError(response, data) {
    const status = response.status;
    
    switch(status) {
        // ... casos existentes ...
        case 429:
            return this.handle429TooManyRequests(data);
        // ...
    }
}

handle429TooManyRequests(data) {
    const message = 'Demasiadas solicitudes. Espere un momento.';
    this.showToast('warning', message);
    return message;
}
```

---

**Última actualización:** 8 de diciembre de 2025  
**Versión:** 1.0
