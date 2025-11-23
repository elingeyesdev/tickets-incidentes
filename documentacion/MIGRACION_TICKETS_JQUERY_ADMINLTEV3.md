# 📋 MIGRACIÓN VISTA TICKETS A JQUERY + ADMINLTE V3 ESTÁNDAR

**Fecha:** 2025-11-22
**Objetivo:** Estandarizar vista de tickets para usar jQuery puro + plugins oficiales AdminLTE v3
**Alcance:** SOLO `resources/views/app/shared/tickets/`

---

## 📑 TABLA DE CONTENIDOS

1. [Estado Actual vs Objetivo](#estado-actual-vs-objetivo)
2. [Cambios Principales](#cambios-principales)
3. [Plugins jQuery Recomendados](#plugins-jquery-recomendados)
4. [Mapeo de Funcionalidades](#mapeo-de-funcionalidades)
5. [Plan de Migración](#plan-de-migración)
6. [Código de Ejemplo](#código-de-ejemplo)
7. [Testing](#testing)

---

## Estado Actual vs Objetivo

### ❌ ESTADO ACTUAL (Problemático)

```
Mezcla de:
├── jQuery (27 referencias)
├── Alpine.js (3 funciones principales)
├── Validación manual en JavaScript
├── Modales con x-show + Bootstrap
└── Select2 parcialmente integrado
```

**Problemas:**
- Dos frameworks lidiando con estado
- Sincronización manual
- No sigue estándar AdminLTE v3
- Difícil mantener

---

### ✅ OBJETIVO (Estándar AdminLTE v3)

```
jQuery puro:
├── jQuery + plugins oficiales
├── Select2 (estándar AdminLTE)
├── Parsley.js (validación)
├── DataTables (opcional, para tabla)
├── Bootstrap modales (jQuery)
└── AdminLTE components nativos
```

**Beneficios:**
- Un único framework
- Sigue documentación oficial AdminLTE v3
- Más compatible con plugins
- Fácil de mantener

---

## Cambios Principales

### 1. ELIMINAR Alpine.js Completamente

**Archivos afectados:**
- `index.blade.php` - Quitar `x-data`, `x-show`, `x-model`, etc.
- `show-ticket-user.blade.php` - Mismo
- `show-ticket-agent-admin.blade.php` - Mismo
- `create-ticket.blade.php` - Mismo

**Antes (Alpine):**
```blade
<div class="card" x-data="ticketsList()" @load="init()">
  <input type="text" x-model="filters.search" @change="applyFilters()">
  <template x-for="ticket in tickets">
    <tr @click="selectTicket(ticket.code)">
      ...
    </tr>
  </template>
</div>
```

**Después (jQuery):**
```blade
<div class="card" id="ticketsCard">
  <input type="text" id="searchFilter" class="form-control">
  <tbody id="ticketsList">
    <!-- Se llena con jQuery -->
  </tbody>
</div>

<script>
$(document).ready(function() {
  const ticketsManager = {
    tickets: [],
    filters: {},

    init() {
      this.loadTickets();
      this.attachEventHandlers();
    },

    attachEventHandlers() {
      $('#searchFilter').on('change', () => this.applyFilters());
    },

    loadTickets() {
      $.ajax({
        url: '/api/tickets',
        data: this.filters,
        success: (data) => {
          this.tickets = data.data;
          this.renderTickets();
        }
      });
    },

    renderTickets() {
      const html = this.tickets.map(ticket => `
        <tr data-ticket-id="${ticket.id}">
          <td>${ticket.title}</td>
        </tr>
      `).join('');

      $('#ticketsList').html(html);
      this.attachRowHandlers();
    },

    attachRowHandlers() {
      $('#ticketsList tr').on('click', (e) => {
        const ticketId = $(e.currentTarget).data('ticket-id');
        this.selectTicket(ticketId);
      });
    }
  };

  ticketsManager.init();
});
</script>
```

---

### 2. REEMPLAZAR Select2 AJAX CON PARSLEY.JS PARA VALIDACIÓN

**Antes (Alpine + Select2):**
```blade
<select id="createCompany" class="form-control" x-model="newTicket.company_id">
  <option value="">Selecciona compañía...</option>
</select>

<script>
$('#createCompany').select2({
  ajax: {
    url: '/api/companies',
    data: (params) => ({search: params.term}),
    processResults: (data) => ({
      results: data.data.map(c => ({id: c.id, text: c.name}))
    })
  }
});

$('#createCompany').on('change', function() {
  self.newTicket.company_id = $(this).val();  // ❌ Sync manual
});
</script>
```

**Después (jQuery + Select2 estándar AdminLTE):**
```blade
<div class="form-group">
  <label for="createCompany">Compañía</label>
  <select id="createCompany" name="company_id" class="form-control"
          required data-parsley-required="true">
    <option value="">Selecciona una compañía...</option>
  </select>
  <span class="invalid-feedback" id="createCompanyError"></span>
</div>

<script>
$(document).ready(function() {
  // Inicializar Select2
  $('#createCompany').select2({
    theme: 'bootstrap4',
    placeholder: 'Buscar compañía...',
    allowClear: true,
    ajax: {
      url: '/api/companies/minimal',
      dataType: 'json',
      delay: 250,
      data: function (params) {
        return {
          search: params.term,
          per_page: 10
        };
      },
      processResults: function (data) {
        return {
          results: $.map(data.data, function (item) {
            return {
              id: item.id,
              text: item.name
            };
          })
        };
      },
      cache: true  // ✅ Caché habilitado
    }
  });

  // Inicializar Parsley validación
  $('#createTicketForm').parsley({
    excluded: '[type=hidden]'
  });
});
</script>
```

---

### 3. REEMPLAZAR MODALES CON BOOTSTRAP + JQUERY

**Antes (Alpine + x-show):**
```blade
<div class="modal fade"
     :class="{ 'show d-block': showEditModal }"
     x-show="showEditModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <button @click="showEditModal = false" class="close">...</button>
    </div>
  </div>
</div>

<script>
// ❌ Lógica de modal manual en Alpine
const data = {
  showEditModal: false,
  openEditModal() {
    this.showEditModal = true;
    // ❌ Modal no visible correctamente
  }
};
</script>
```

**Después (Bootstrap Modal jQuery estándar):**
```blade
<div class="modal fade" id="editTicketModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Editar Ticket</h5>
        <button type="button" class="close" data-dismiss="modal">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="editTicketForm">
          <div class="form-group">
            <label for="editTitle">Título</label>
            <input type="text" id="editTitle" name="title"
                   class="form-control" required>
          </div>
          <div class="form-group">
            <label for="editCategory">Categoría</label>
            <select id="editCategory" name="category_id" class="form-control"></select>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">
          Cerrar
        </button>
        <button type="button" class="btn btn-primary" id="saveEditBtn">
          Guardar
        </button>
      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
  const modal = new bootstrap.Modal(document.getElementById('editTicketModal'));

  // Abrir modal
  $(document).on('click', '.edit-ticket-btn', function() {
    const ticketId = $(this).data('ticket-id');
    ticketManager.loadTicketForEdit(ticketId);
    modal.show();
  });

  // Guardar cambios
  $('#saveEditBtn').on('click', function() {
    if ($('#editTicketForm').parsley().validate()) {
      ticketManager.updateTicket();
      modal.hide();
    }
  });
});
</script>
```

---

### 4. VALIDACIÓN CENTRALIZADA CON PARSLEY.JS

**Antes (Validación triple):**
```javascript
// ❌ Validación 1: En método JavaScript
createTicket() {
  if (!this.newTicket.title || this.newTicket.title.length < 5) {
    this.showError('Título inválido');
    return;
  }
}

// ❌ Validación 2: En atributo HTML
:disabled="!newTicket.title || newTicket.title.length < 5"

// ❌ Validación 3: En HTML5
<input type="text" required minlength="5">
```

**Después (Parsley.js estándar):**
```blade
<form id="createTicketForm" data-parsley-validate="">
  <div class="form-group">
    <label for="title">Título</label>
    <input type="text" id="title" name="title" class="form-control"
           required
           data-parsley-type="string"
           data-parsley-minlength="5"
           data-parsley-maxlength="255"
           data-parsley-error-message="El título debe tener 5-255 caracteres">
  </div>

  <div class="form-group">
    <label for="description">Descripción</label>
    <textarea id="description" name="description" class="form-control"
              required
              data-parsley-minlength="10"
              data-parsley-maxlength="5000"></textarea>
  </div>

  <div class="form-group">
    <label for="company_id">Compañía</label>
    <select id="company_id" name="company_id" class="form-control"
            required
            data-parsley-required="true">
      <option value="">Selecciona...</option>
    </select>
  </div>

  <div class="form-group">
    <label for="category_id">Categoría</label>
    <select id="category_id" name="category_id" class="form-control"
            required
            data-parsley-required="true">
      <option value="">Selecciona...</option>
    </select>
  </div>

  <button type="submit" class="btn btn-primary">Crear Ticket</button>
</form>

<script>
$(document).ready(function() {
  // Inicializar Parsley
  const form = $('#createTicketForm').parsley({
    excluded: '[type=hidden]',
    errorsWrapper: '<ul class="parsley-error-list"></ul>',
    errorTemplate: '<li>##message##</li>',
    classHandler: function(field) {
      return field.$element.closest('.form-group');
    },
    errorsContainer: function(field) {
      return field.$element.closest('.form-group');
    }
  });

  // Validar antes de enviar
  $('#createTicketForm').on('submit', function(e) {
    e.preventDefault();

    if (form.validate()) {
      // ✅ Enviar datos
      submitCreateTicket();
    }
  });
});
</script>
```

---

### 5. RENDERIZADO DE TABLA CON JQUERY

**Antes (Alpine x-for):**
```blade
<tbody id="ticketsList">
  <template x-for="ticket in filteredTickets" :key="ticket.id">
    <tr @click="selectTicket(ticket.code)">
      <td>
        <i class="fas fa-star" @click.stop="toggleStar(ticket.id)"></i>
      </td>
      <td>
        <h6 x-text="ticket.title"></h6>
        <small x-text="'#' + ticket.code"></small>
      </td>
      <td>
        <span class="badge"
              :class="badgeClass(ticket.status)"
              x-text="ticket.status"></span>
      </td>
    </tr>
  </template>
</tbody>
```

**Después (jQuery renderizado):**
```blade
<tbody id="ticketsList">
  <!-- Generado dinámicamente por jQuery -->
</tbody>

<script>
$(document).ready(function() {
  const ticketsUI = {
    render(tickets) {
      const html = tickets.map(ticket => `
        <tr data-ticket-id="${ticket.id}" data-ticket-code="${ticket.code}">
          <td>
            <i class="fas fa-star toggle-star"
               data-ticket-id="${ticket.id}"
               style="cursor: pointer;"></i>
          </td>
          <td>
            <h6>${ticket.title}</h6>
            <small class="text-muted">#${ticket.code}</small>
          </td>
          <td>
            <span class="badge ${this.getBadgeClass(ticket.status)}">
              ${ticket.status}
            </span>
          </td>
          <td>${ticket.created_at_formatted}</td>
        </tr>
      `).join('');

      $('#ticketsList').html(html);
      this.attachHandlers();
    },

    attachHandlers() {
      // Click en row → abrir detalle
      $('#ticketsList tr').off('click').on('click', (e) => {
        if (!$(e.target).closest('.toggle-star').length) {
          const code = $(e.currentTarget).data('ticket-code');
          ticketDetail.load(code);
        }
      });

      // Click en star → toggle favorito
      $('#ticketsList .toggle-star').off('click').on('click', (e) => {
        e.stopPropagation();
        const ticketId = $(e.target).data('ticket-id');
        ticketsUI.toggleStar(ticketId);
      });
    },

    toggleStar(ticketId) {
      $.ajax({
        url: `/api/tickets/${ticketId}/toggle-star`,
        type: 'POST',
        success: () => {
          $(`[data-ticket-id="${ticketId}"] .toggle-star`).toggleClass('active');
        }
      });
    },

    getBadgeClass(status) {
      const classes = {
        'open': 'badge-success',
        'pending': 'badge-warning',
        'resolved': 'badge-info',
        'closed': 'badge-secondary'
      };
      return classes[status] || 'badge-secondary';
    }
  };
});
</script>
```

---

### 6. CARGA DINÁMICA DE CATEGORÍAS

**Antes (Select2 AJAX sin caché):**
```javascript
$('#createCategory').select2({
  ajax: {
    url: '/api/tickets/categories',
    cache: false  // ❌ Sin caché
  }
});
```

**Después (Select2 con caché):**
```javascript
$(document).ready(function() {
  let categoryCache = {};

  $('#createCategory').select2({
    theme: 'bootstrap4',
    placeholder: 'Buscar categoría...',
    allowClear: true,
    ajax: {
      url: '/api/tickets/categories',
      dataType: 'json',
      delay: 250,
      data: function (params) {
        const companyId = $('#createCompany').val();
        return {
          search: params.term,
          company_id: companyId,
          is_active: 1,
          per_page: 10
        };
      },
      processResults: function (data) {
        return {
          results: $.map(data.data, function (item) {
            return {
              id: item.id,
              text: item.name
            };
          })
        };
      },
      cache: true  // ✅ Caché habilitado
    }
  });

  // Recargar categorías cuando cambia compañía
  $('#createCompany').on('change', function() {
    $('#createCategory').val(null).trigger('change');
    // Limpiar caché para esta compañía
    const companyId = $(this).val();
    delete categoryCache[companyId];
  });
});
```

---

## Plugins jQuery Recomendados

### 📦 Plugins Oficiales AdminLTE v3

| Plugin | Función | Instalación | Uso |
|--------|---------|-------------|-----|
| **Select2** | Dropdowns searchables | CDN o npm | Compañía, Categoría |
| **Parsley.js** | Validación formularios | CDN o npm | Crear/Editar tickets |
| **DataTables** | Tablas avanzadas | CDN o npm | Tabla de tickets (opcional) |
| **Bootstrap Modal** | Modales | Built-in Bootstrap | Editar, Ver detalles |
| **jQuery File Upload** | Upload archivos | npm | Adjuntos |

### 🔌 Stack Recomendado para Tickets

```html
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap Modal (ya incluido en AdminLTE) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Select2 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Select2 Bootstrap Theme -->
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<!-- Parsley.js (Validación) -->
<script src="https://cdn.jsdelivr.net/npm/parsley@2.9.2/dist/parsley.min.js"></script>

<!-- jQuery File Upload (para adjuntos) -->
<link href="https://blueimp.github.io/jQuery-File-Upload/css/jquery.fileupload.css" rel="stylesheet" />
<script src="https://blueimp.github.io/jQuery-File-Upload/js/jquery.fileupload.js"></script>
```

---

## Mapeo de Funcionalidades

### Filtrados y Búsqueda

| Funcionalidad | AdminLTE v3 Componente | Plugin jQuery | Ubicación |
|---|---|---|---|
| Filtro por categoría | Select2 | Select2 | Sidebar |
| Filtro por status | Select (basic) | - | Sidebar |
| Búsqueda por texto | Input text | - | Header |
| Aplicar filtros | Button | - | Sidebar |

**Código ejemplo:**
```javascript
$(document).ready(function() {
  const filters = {
    category_id: null,
    status: null,
    search: '',

    apply() {
      ticketsManager.loadTickets(this);
    }
  };

  // Cambio categoría
  $('#categoryFilter').on('change', function() {
    filters.category_id = $(this).val();
    filters.apply();
  });

  // Cambio status
  $('#statusFilter').on('change', function() {
    filters.status = $(this).val();
    filters.apply();
  });

  // Búsqueda
  $('#searchInput').on('keyup', function() {
    filters.search = $(this).val();
    // Debounce para no sobre-cargar
    clearTimeout(filters.searchTimeout);
    filters.searchTimeout = setTimeout(() => filters.apply(), 500);
  });
});
```

---

### Crear Ticket

| Paso | Componente | Plugin | Acción |
|------|-----------|--------|--------|
| 1. Form | Form AdminLTE | Parsley.js | Validar campos |
| 2. Select Compañía | Select2 | Select2 | AJAX search |
| 3. Select Categoría | Select2 | Select2 | AJAX search |
| 4. Validar | Validación | Parsley.js | Bloquear submit si inválido |
| 5. Enviar | Button | jQuery.ajax | POST /api/tickets |
| 6. Mostrar resultado | Modal/Alert | Bootstrap/SweetAlert | Success/Error |

---

### Detalle Ticket

| Funcionalidad | User | Agent/Admin | Plugin |
|---|---|---|---|
| Ver respuestas (chat) | ✅ | ✅ | Renderizado jQuery |
| Enviar respuesta | ✅ | ✅ | Form + jQuery.ajax |
| Editar título | ✅ (si OPEN) | ✅ | Modal + Parsley |
| Editar categoría | ❌ | ✅ | Select2 |
| Asignar agente | ❌ | ✅ (AGENT only) | Select2 |
| Adjuntar archivo | ✅ (si !CLOSED) | ✅ | jQuery File Upload |
| Ver adjuntos | ✅ | ✅ | Renderizado jQuery |
| Resolver | ❌ | ✅ | Button + confirm |
| Cerrar | ✅ | ✅ | Button + confirm |
| Reabrir | ✅ (30 días) | ✅ | Button + confirm |
| Eliminar | ❌ | ✅ (ADMIN) | Button + confirm |

---

## Plan de Migración

### 🎯 Fase 1: Preparación (Día 1)

**Tarea 1.1:** Agregar jQuery plugins al layout
```blade
<!-- En layout authenticated.blade.php -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-4-theme@1.5.2/dist/select2-bootstrap-4.min.css" rel="stylesheet" />

<script src="https://cdn.jsdelivr.net/npm/parsley@2.9.2/dist/parsley.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/parsley@2.9.2/dist/i18n/es.js"></script>
```

**Tarea 1.2:** Remover Alpine.js del layout
```blade
<!-- ELIMINAR ESTA LÍNEA -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
```

**Tarea 1.3:** Crear archivo JS centralizado para tickets
```javascript
// resources/js/tickets.js
const ticketsManager = {
  // Lógica aquí
};

const ticketDetail = {
  // Detalle ticket aquí
};

const ticketForm = {
  // Crear/Editar aquí
};
```

---

### 🎯 Fase 2: Refactorizar index.blade.php (Día 2-3)

**Tarea 2.1:** Remover Alpine.js del HTML
```blade
<!-- ANTES -->
<div class="card" x-data="ticketsList()" @load="init()">

<!-- DESPUÉS -->
<div class="card" id="ticketsContainer">
```

**Tarea 2.2:** Migrar lógica a jQuery
```javascript
// Quitar funciones Alpine
// Implementar métodos en ticketsManager

ticketsManager = {
  tickets: [],
  filters: {},

  init() {
    this.loadTickets();
    this.attachHandlers();
  },

  loadTickets() {
    $.ajax({
      url: '/api/tickets',
      data: this.filters,
      success: (response) => {
        this.tickets = response.data;
        this.renderTickets();
      }
    });
  },

  renderTickets() {
    // jQuery DOM manipulation
  },

  attachHandlers() {
    // Event bindings
  }
};

$(document).ready(() => ticketsManager.init());
```

**Tarea 2.3:** Migrar Select2 para filtros
```javascript
$('#categoryFilter').select2({
  theme: 'bootstrap4',
  placeholder: 'Todas las categorías',
  allowClear: true
});

$('#categoryFilter').on('change', function() {
  ticketsManager.filters.category_id = $(this).val();
  ticketsManager.loadTickets();
});
```

**Tarea 2.4:** Renderizar tabla con jQuery
```javascript
ticketsManager.renderTickets = function() {
  const html = this.tickets.map(ticket => `
    <tr data-ticket-id="${ticket.id}" class="ticket-row">
      <td><i class="far fa-star"></i></td>
      <td>${ticket.title}<br><small>#${ticket.code}</small></td>
      <td><span class="badge badge-${this.getStatusColor(ticket.status)}">${ticket.status}</span></td>
      <td>${this.formatDate(ticket.created_at)}</td>
    </tr>
  `).join('');

  $('#ticketsList').html(html);
  this.attachRowHandlers();
};

ticketsManager.attachRowHandlers = function() {
  $('.ticket-row').on('click', (e) => {
    const ticketId = $(e.currentTarget).data('ticket-id');
    ticketDetail.load(ticketId);
  });
};
```

---

### 🎯 Fase 3: Refactorizar create-ticket.blade.php (Día 3-4)

**Tarea 3.1:** Agregar validación Parsley.js
```blade
<form id="createTicketForm" data-parsley-validate>
  <div class="form-group">
    <label>Título</label>
    <input type="text" name="title" class="form-control"
           required
           data-parsley-minlength="5"
           data-parsley-maxlength="255">
  </div>

  <div class="form-group">
    <label>Descripción</label>
    <textarea name="description" class="form-control"
              required
              data-parsley-minlength="10"
              data-parsley-maxlength="5000"></textarea>
  </div>

  <div class="form-group">
    <label>Compañía</label>
    <select id="createCompany" name="company_id" class="form-control"
            required data-parsley-required="true"></select>
  </div>

  <div class="form-group">
    <label>Categoría</label>
    <select id="createCategory" name="category_id" class="form-control"
            required data-parsley-required="true"></select>
  </div>

  <button type="submit" class="btn btn-primary">Crear Ticket</button>
</form>
```

**Tarea 3.2:** Inicializar Select2
```javascript
$(document).ready(function() {
  // Select2 Compañía
  $('#createCompany').select2({
    theme: 'bootstrap4',
    placeholder: 'Busca una compañía...',
    ajax: {
      url: '/api/companies/minimal',
      dataType: 'json',
      data: (params) => ({search: params.term, per_page: 10}),
      processResults: (data) => ({
        results: data.data.map(c => ({id: c.id, text: c.name}))
      }),
      cache: true
    }
  });

  // Select2 Categoría (depende de Compañía)
  $('#createCategory').select2({
    theme: 'bootstrap4',
    placeholder: 'Busca una categoría...',
    ajax: {
      url: '/api/tickets/categories',
      dataType: 'json',
      data: (params) => ({
        search: params.term,
        company_id: $('#createCompany').val(),
        is_active: 1,
        per_page: 10
      }),
      processResults: (data) => ({
        results: data.data.map(c => ({id: c.id, text: c.name}))
      }),
      cache: true
    }
  });

  // Actualizar categorías cuando cambia compañía
  $('#createCompany').on('change', function() {
    $('#createCategory').val(null).trigger('change');
  });

  // Inicializar Parsley
  $('#createTicketForm').parsley();

  // Submit
  $('#createTicketForm').on('submit', function(e) {
    e.preventDefault();

    if ($(this).parsley().isValid()) {
      const data = {
        title: $('[name="title"]').val(),
        description: $('[name="description"]').val(),
        company_id: $('#createCompany').val(),
        category_id: $('#createCategory').val()
      };

      createTicket(data);
    }
  });
});
```

---

### 🎯 Fase 4: Refactorizar show-ticket-user.blade.php (Día 4-5)

**Tarea 4.1:** Reemplazar Alpine modales con Bootstrap
```blade
<!-- ANTES: Alpine x-show -->
<div class="modal fade" :class="{ 'show d-block': showEditModal }">

<!-- DESPUÉS: Bootstrap Modal -->
<div class="modal fade" id="editTicketModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Editar Ticket</h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="editTicketForm" data-parsley-validate>
          <div class="form-group">
            <label>Título</label>
            <input type="text" name="title" class="form-control" required>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">
          Cerrar
        </button>
        <button type="button" class="btn btn-primary" id="saveEditBtn">
          Guardar
        </button>
      </div>
    </div>
  </div>
</div>
```

**Tarea 4.2:** Logica jQuery para modal
```javascript
$(document).ready(function() {
  const editModal = new bootstrap.Modal(document.getElementById('editTicketModal'));

  $('.edit-ticket-btn').on('click', function() {
    const code = $(this).data('code');
    ticketDetail.load(code, 'edit');
    editModal.show();
  });

  $('#saveEditBtn').on('click', function() {
    if ($('#editTicketForm').parsley().isValid()) {
      ticketDetail.update();
      editModal.hide();
    }
  });
});
```

**Tarea 4.3:** Renderizar chat con jQuery
```javascript
ticketDetail = {
  ticket: null,

  load(code, mode = 'view') {
    $.ajax({
      url: `/api/tickets/${code}`,
      success: (response) => {
        this.ticket = response.data;
        this.renderDetail(mode);
      }
    });
  },

  renderDetail(mode) {
    // Renderizar respuestas (chat)
    this.renderResponses();

    if (mode === 'edit') {
      $('[name="title"]').val(this.ticket.title);
    }
  },

  renderResponses() {
    const html = this.ticket.responses.map(response => `
      <div class="direct-chat-msg ${response.is_agent ? 'right' : ''}">
        <div class="direct-chat-info clearfix">
          <span class="direct-chat-name">${response.author.name}</span>
          <span class="direct-chat-timestamp">${this.formatDate(response.created_at)}</span>
        </div>
        <img class="direct-chat-img" src="${response.author.avatar}">
        <div class="direct-chat-text">
          ${response.body}
        </div>
      </div>
    `).join('');

    $('#chatMessages').html(html);
  },

  update() {
    const data = {
      title: $('[name="title"]').val(),
      category_id: $('#editCategory').val() // Si aplica
    };

    $.ajax({
      url: `/api/tickets/${this.ticket.code}`,
      type: 'PATCH',
      contentType: 'application/json',
      data: JSON.stringify(data),
      success: () => {
        this.load(this.ticket.code, 'view');
        this.showSuccess('Ticket actualizado');
      }
    });
  }
};
```

---

### 🎯 Fase 5: Refactorizar show-ticket-agent-admin.blade.php (Día 5-6)

**Tarea 5.1:** Agregar funcionalidades admin
```javascript
// Resolver ticket
$('#resolveBtn').on('click', function() {
  if (confirm('¿Marcar como resuelto?')) {
    $.ajax({
      url: `/api/tickets/${ticketDetail.ticket.code}/resolve`,
      type: 'POST',
      success: () => ticketDetail.load(ticketDetail.ticket.code)
    });
  }
});

// Asignar agente
$('#assignAgentBtn').on('click', function() {
  const modal = new bootstrap.Modal(document.getElementById('assignAgentModal'));
  modal.show();
});

$('#saveAssignBtn').on('click', function() {
  const agentId = $('#agentSelect').val();
  $.ajax({
    url: `/api/tickets/${ticketDetail.ticket.code}/assign`,
    type: 'POST',
    contentType: 'application/json',
    data: JSON.stringify({new_agent_id: agentId}),
    success: () => {
      ticketDetail.load(ticketDetail.ticket.code);
      new bootstrap.Modal(document.getElementById('assignAgentModal')).hide();
    }
  });
});

// Eliminar ticket (ADMIN only)
$('#deleteBtn').on('click', function() {
  if (confirm('¿Eliminar ticket permanentemente?')) {
    $.ajax({
      url: `/api/tickets/${ticketDetail.ticket.code}`,
      type: 'DELETE',
      success: () => {
        window.location.href = '/tickets';
      }
    });
  }
});
```

**Tarea 5.2:** Select2 para seleccionar agente
```javascript
$('#agentSelect').select2({
  theme: 'bootstrap4',
  placeholder: 'Selecciona un agente...',
  allowClear: true,
  ajax: {
    url: `/api/companies/${ticketDetail.ticket.company_id}/agents`,
    dataType: 'json',
    data: (params) => ({per_page: 10}),
    processResults: (data) => ({
      results: data.data.map(a => ({id: a.id, text: a.name}))
    })
  }
});
```

---

### 🎯 Fase 6: Migrar tickets-list.blade.php (Día 6)

**Tarea 6.1:** Renderizar tabla con jQuery
```javascript
ticketsManager.renderTickets = function() {
  const isMobile = window.innerWidth < 768;
  const html = this.tickets.map(ticket => {
    const userView = !this.isAgent;
    const columns = userView ?
      `<td><i class="far fa-star"></i></td>
       <td>
         <h6>${ticket.title}</h6>
         <small>#${ticket.code}</small>
       </td>
       <td>${this.formatDate(ticket.created_at)}</td>` :
      `<td><input type="checkbox" class="ticket-checkbox" value="${ticket.id}"></td>
       <td><i class="far fa-star"></i></td>
       <td><img src="${ticket.user.avatar}" class="avatar avatar-sm"></td>
       <td>
         <h6>${ticket.title}</h6>
         <small>por ${ticket.user.name}</small>
         ${ticket.is_new ? '<span class="badge badge-primary">NEW</span>' : ''}
       </td>
       <td>${this.formatDate(ticket.created_at)}</td>`;

    return `
      <tr data-ticket-code="${ticket.code}" class="ticket-row">
        ${columns}
      </tr>
    `;
  }).join('');

  $('#ticketsList').html(html);
  this.attachRowHandlers();
};

// Checkbox toggle
$('.checkbox-toggle').on('click', function() {
  const isChecked = $(this).is(':checked');
  $('input.ticket-checkbox').prop('checked', isChecked);
});

$('input.ticket-checkbox').on('change', function() {
  const selected = $('input.ticket-checkbox:checked').length;
  $('.selected-count').text(selected);
  if (selected > 0) {
    $('.bulk-actions').show();
  } else {
    $('.bulk-actions').hide();
  }
});
```

---

### 🎯 Fase 7: Testing y Cleanup (Día 6-7)

**Tarea 7.1:** Remover completamente Alpine.js
- [ ] Eliminar `x-data` de todos los archivos
- [ ] Eliminar `@change`, `@click`, `x-show`, `x-model`, etc.
- [ ] Remover línea del CDN de Alpine en layout

**Tarea 7.2:** Testing de funcionalidades
- [ ] Crear ticket
- [ ] Filtrar tickets
- [ ] Editar ticket (user)
- [ ] Ver detalle
- [ ] Adjuntar archivo
- [ ] Asignar agente (admin)
- [ ] Resolver ticket (admin)
- [ ] Cerrar/Reabrir ticket

**Tarea 7.3:** Optimización
- [ ] Cachear requests Select2
- [ ] Debounce búsquedas
- [ ] Lazy load imágenes
- [ ] Minificar JavaScript

---

## Código de Ejemplo

### Estructura Base de JavaScript

```javascript
// resources/js/tickets.js

// ===== TICKETS MANAGER =====
const ticketsManager = {
  tickets: [],
  currentPage: 1,
  totalPages: 1,
  perPage: 15,
  filters: {
    search: '',
    category_id: null,
    status: null,
    owner_agent_id: null
  },
  isAgent: false,
  currentFolder: 'all',

  init() {
    this.checkRole();
    this.initializeSelects();
    this.loadTickets();
    this.attachEventHandlers();
    this.initializeSidebar();
  },

  checkRole() {
    // Detectar si es agente/admin
    this.isAgent = document.body.dataset.userRole !== 'user';
  },

  initializeSelects() {
    // Select2 para filtro categoría
    $('#categoryFilter').select2({
      theme: 'bootstrap4',
      placeholder: 'Todas las categorías',
      allowClear: true,
      data: [],
      matcher: $.fn.select2.defaults.set.matcher
    });

    // Cargar categorías
    $.ajax({
      url: '/api/tickets/categories',
      data: {is_active: 1, per_page: 100},
      success: (response) => {
        const data = response.data.map(cat => ({id: cat.id, text: cat.name}));
        $('#categoryFilter').select2('destroy').select2({
          theme: 'bootstrap4',
          placeholder: 'Todas',
          data: data
        });
      }
    });
  },

  attachEventHandlers() {
    // Filtro categoría
    $(document).on('change', '#categoryFilter', () => {
      this.filters.category_id = $('#categoryFilter').val();
      this.currentPage = 1;
      this.loadTickets();
    });

    // Búsqueda
    let searchTimeout;
    $(document).on('keyup', '#searchInput', (e) => {
      clearTimeout(searchTimeout);
      this.filters.search = $(e.target).val();
      searchTimeout = setTimeout(() => {
        this.currentPage = 1;
        this.loadTickets();
      }, 500);
    });

    // Click en row
    $(document).on('click', '.ticket-row', (e) => {
      if (!$(e.target).closest('[data-toggle="star"]').length) {
        const code = $(e.currentTarget).data('ticket-code');
        ticketDetail.load(code);
      }
    });

    // Paginación
    $(document).on('click', '[data-page]', (e) => {
      this.currentPage = $(e.target).data('page');
      this.loadTickets();
    });
  },

  initializeSidebar() {
    // Cargar contadores
    this.loadStats();
  },

  loadStats() {
    $.ajax({
      url: '/api/tickets/stats',
      success: (response) => {
        $('[data-stat="total"]').text(response.total);
        $('[data-stat="open"]').text(response.by_status.open || 0);
        $('[data-stat="pending"]').text(response.by_status.pending || 0);
        // etc...
      }
    });
  },

  loadTickets() {
    $.ajax({
      url: '/api/tickets',
      data: {
        ...this.filters,
        page: this.currentPage,
        per_page: this.perPage
      },
      success: (response) => {
        this.tickets = response.data;
        this.totalPages = response.meta.last_page;
        this.renderTickets();
        this.renderPagination();
      }
    });
  },

  renderTickets() {
    const html = this.tickets.map(ticket => this.ticketRowHtml(ticket)).join('');
    $('#ticketsList').html(html);
  },

  ticketRowHtml(ticket) {
    const statusBadge = `<span class="badge badge-${this.getStatusColor(ticket.status)}">${ticket.status}</span>`;

    if (this.isAgent) {
      return `
        <tr class="ticket-row" data-ticket-code="${ticket.code}">
          <td><input type="checkbox" class="ticket-checkbox" value="${ticket.id}"></td>
          <td><i class="far fa-star" data-toggle="star" data-id="${ticket.id}"></i></td>
          <td><img src="${ticket.user.avatar}" class="avatar avatar-sm" alt="${ticket.user.name}"></td>
          <td>
            <h6>${ticket.title}</h6>
            <small>por ${ticket.user.name}</small>
            ${ticket.is_new ? '<span class="badge badge-primary">NEW</span>' : ''}
          </td>
          <td>${this.formatDate(ticket.created_at)}</td>
        </tr>
      `;
    } else {
      return `
        <tr class="ticket-row" data-ticket-code="${ticket.code}">
          <td><i class="far fa-star" data-toggle="star" data-id="${ticket.id}"></i></td>
          <td>
            <h6>${ticket.title}</h6>
            <small class="text-muted">#${ticket.code}</small>
          </td>
          <td>${statusBadge}</td>
          <td>${this.formatDate(ticket.created_at)}</td>
        </tr>
      `;
    }
  },

  getStatusColor(status) {
    const colors = {
      'open': 'success',
      'pending': 'warning',
      'resolved': 'info',
      'closed': 'secondary'
    };
    return colors[status] || 'secondary';
  },

  formatDate(date) {
    return new Date(date).toLocaleDateString('es-ES', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  },

  renderPagination() {
    let html = '<nav><ul class="pagination">';

    for (let i = 1; i <= this.totalPages; i++) {
      html += `
        <li class="page-item ${i === this.currentPage ? 'active' : ''}">
          <a class="page-link" href="#" data-page="${i}">${i}</a>
        </li>
      `;
    }

    html += '</ul></nav>';
    $('#pagination').html(html);
  }
};

// ===== TICKET DETAIL =====
const ticketDetail = {
  ticket: null,

  load(code) {
    $.ajax({
      url: `/api/tickets/${code}`,
      success: (response) => {
        this.ticket = response.data;
        this.show();
      }
    });
  },

  show() {
    this.renderDetail();
    // Abrir view completa o modal
    window.location.hash = `#ticket/${this.ticket.code}`;
  },

  renderDetail() {
    // Renderizar respuestas
    this.renderResponses();

    // Cargar archivos
    this.loadAttachments();

    // Inicializar acciones disponibles
    this.setupActions();
  },

  renderResponses() {
    const html = this.ticket.responses.map(response => `
      <div class="direct-chat-msg ${response.is_agent ? 'right' : ''}">
        <div class="direct-chat-info clearfix">
          <span class="direct-chat-name">${response.author.name}</span>
          <span class="direct-chat-timestamp">${this.formatDate(response.created_at)}</span>
        </div>
        <img class="direct-chat-img" src="${response.author.avatar}" alt="${response.author.name}">
        <div class="direct-chat-text">
          ${response.body}
        </div>
      </div>
    `).join('');

    $('#chatMessages').html(html);
  },

  loadAttachments() {
    $.ajax({
      url: `/api/tickets/${this.ticket.code}/attachments`,
      success: (response) => {
        const html = response.data.map(attachment => `
          <div class="attachment-item">
            <a href="${attachment.file_url}" target="_blank">
              <i class="fas fa-file"></i> ${attachment.original_filename}
            </a>
            <button class="btn btn-sm btn-danger delete-attachment" data-id="${attachment.id}">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        `).join('');

        $('#attachmentsList').html(html);

        // Evento eliminar
        $('.delete-attachment').on('click', (e) => {
          const id = $(e.currentTarget).data('id');
          if (confirm('¿Eliminar archivo?')) {
            this.deleteAttachment(id);
          }
        });
      }
    });
  },

  deleteAttachment(id) {
    $.ajax({
      url: `/api/tickets/${this.ticket.code}/attachments/${id}`,
      type: 'DELETE',
      success: () => {
        this.loadAttachments();
      }
    });
  },

  setupActions() {
    // Botón editar
    $('#editBtn').off('click').on('click', () => {
      const modal = new bootstrap.Modal(document.getElementById('editTicketModal'));
      $('[name="title"]').val(this.ticket.title);
      modal.show();
    });

    // Botón cerrar
    $('#closeBtn').off('click').on('click', () => {
      if (confirm('¿Cerrar ticket?')) {
        $.ajax({
          url: `/api/tickets/${this.ticket.code}/close`,
          type: 'POST',
          success: () => this.load(this.ticket.code)
        });
      }
    });

    // Botones solo admin
    if (ticketsManager.isAgent) {
      $('#resolveBtn').off('click').on('click', () => {
        if (confirm('¿Marcar como resuelto?')) {
          $.ajax({
            url: `/api/tickets/${this.ticket.code}/resolve`,
            type: 'POST',
            success: () => this.load(this.ticket.code)
          });
        }
      });
    }
  },

  formatDate(date) {
    return new Date(date).toLocaleDateString('es-ES', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  }
};

// ===== INICIALIZACIÓN =====
$(document).ready(function() {
  ticketsManager.init();
});
```

---

## Testing

### Checklist de Testing

```javascript
// Crear ticket
- [ ] Form valida requeridos
- [ ] Título min 5 caracteres
- [ ] Descripción min 10 caracteres
- [ ] Select2 carga compañías
- [ ] Select2 carga categorías según compañía
- [ ] POST a API correctamente
- [ ] Mensaje success muestra
- [ ] Ticket aparece en lista

// Listar tickets
- [ ] Carga inicial sin filtros
- [ ] Paginación funciona
- [ ] Filtro categoría funciona
- [ ] Búsqueda funciona con debounce
- [ ] Contador stats se actualiza
- [ ] Clases badge correctas por status

// Ver detalle
- [ ] Carga respuestas (chat)
- [ ] Muestra adjuntos
- [ ] Buttons disponibles según role
- [ ] Editar abre modal
- [ ] Validación en modal

// Acciones user
- [ ] Puede editar título
- [ ] Puede cerrar ticket
- [ ] Puede reabrir (30 días)
- [ ] NO puede resolver
- [ ] NO puede asignar agente

// Acciones agent/admin
- [ ] Puede resolver
- [ ] Puede asignar agente
- [ ] Select2 carga agentes
- [ ] Puede eliminar (ADMIN, si CLOSED)

// Adjuntos
- [ ] Upload valida extensión
- [ ] Upload valida tamaño
- [ ] Máximo 5 files
- [ ] Delete funciona
```

---

## Conclusión

Con esta migración a **jQuery + AdminLTE v3 estándar**:

✅ **Ganancias:**
- Un único framework (jQuery)
- Sigue documentación oficial AdminLTE v3
- Compatible con todos los plugins AdminLTE
- Código más mantenible
- Mejor performance

❌ **Retos:**
- Más verboso que Alpine.js
- Requiere manejo manual del DOM
- State management más complejo

**Tiempo estimado:** 7-10 días de trabajo

¿Deseas que comience con la implementación?

---

**Documento generado:** 2025-11-22
**Versión:** 2.0 (jQuery + AdminLTE v3)
**Status:** LISTO PARA IMPLEMENTAR ✅
