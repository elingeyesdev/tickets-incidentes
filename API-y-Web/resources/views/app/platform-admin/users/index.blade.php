@extends('layouts.authenticated')

@section('title', 'Gestión de Usuarios - Platform Admin')
@section('content_header', 'Gestión de Usuarios')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="/app/admin/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Usuarios</li>
@endsection

@section('content')

{{-- Statistics Info Boxes --}}
<div class="row mb-3">
    <div class="col-md-3 col-sm-6">
        <div class="info-box bg-light elevation-2" style="cursor:pointer" data-filter="" id="infoBoxAll">
            <span class="info-box-icon bg-primary"><i class="fas fa-users"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total</span>
                <span class="info-box-number" id="totalUsers">0</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="info-box bg-light" style="cursor:pointer" data-filter="active" id="infoBoxActive">
            <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Activos</span>
                <span class="info-box-number" id="activeUsers">0</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="info-box bg-light" style="cursor:pointer" data-filter="suspended" id="infoBoxSuspended">
            <span class="info-box-icon bg-warning"><i class="fas fa-pause-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Suspendidos</span>
                <span class="info-box-number" id="suspendedUsers">0</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="info-box bg-light" style="cursor:pointer" data-filter="deleted" id="infoBoxDeleted">
            <span class="info-box-icon bg-danger"><i class="fas fa-times-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Eliminados</span>
                <span class="info-box-number" id="deletedUsers">0</span>
            </div>
        </div>
    </div>
</div>

{{-- Filters Card --}}
<div class="card card-outline card-secondary collapsed-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter"></i> Filtros Avanzados</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label class="text-sm">Buscar</label>
                    <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Email, código o nombre...">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label class="text-sm">Estado</label>
                    <select class="form-control form-control-sm" id="statusFilter">
                        <option value="">Todos</option>
                        <option value="active" selected>Activos</option>
                        <option value="suspended">Suspendidos</option>
                        <option value="deleted">Eliminados</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label class="text-sm">Rol</label>
                    <select class="form-control form-control-sm" id="roleFilter">
                        <option value="">Todos</option>
                        <option value="USER">Cliente</option>
                        <option value="AGENT">Agente</option>
                        <option value="COMPANY_ADMIN">Admin Empresa</option>
                        <option value="PLATFORM_ADMIN">Admin Plataforma</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label class="text-sm">Email Verificado</label>
                    <select class="form-control form-control-sm" id="emailVerifiedFilter">
                        <option value="">Todos</option>
                        <option value="true">Verificados</option>
                        <option value="false">Sin Verificar</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="text-sm">Empresa</label>
                    <select class="form-control form-control-sm" id="companyFilter">
                        <option value="">Todas</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-2">
                <div class="form-group">
                    <label class="text-sm">Ordenar por</label>
                    <select class="form-control form-control-sm" id="orderByFilter">
                        <option value="created_at">Fecha Creación</option>
                        <option value="email">Email</option>
                        <option value="last_login_at">Último Login</option>
                        <option value="last_activity_at">Última Actividad</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label class="text-sm">Dirección</label>
                    <select class="form-control form-control-sm" id="orderDirFilter">
                        <option value="desc">Descendente</option>
                        <option value="asc">Ascendente</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <div class="custom-control custom-checkbox mt-4">
                        <input type="checkbox" class="custom-control-input" id="recentActivityFilter">
                        <label class="custom-control-label" for="recentActivityFilter">Actividad reciente (7 días)</label>
                    </div>
                </div>
            </div>
            <div class="col-md-6 text-right">
                <label class="text-sm d-block">&nbsp;</label>
                <button type="button" class="btn btn-default btn-sm" id="btnResetFilters"><i class="fas fa-eraser"></i> Limpiar</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnRefresh"><i class="fas fa-sync-alt"></i> Aplicar</button>
            </div>
        </div>
    </div>
</div>

{{-- Users Table Card --}}
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-users"></i> Usuarios del Sistema</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
            <button type="button" class="btn btn-tool" data-card-widget="maximize"><i class="fas fa-expand"></i></button>
        </div>
    </div>
    <div class="card-body p-0">
        <div id="loadingSpinner" class="text-center py-5">
            <div class="spinner-border text-primary" role="status"><span class="sr-only">Cargando...</span></div>
            <p class="text-muted mt-3">Cargando usuarios...</p>
        </div>
        <div id="tableContainer" style="display:none">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width:10%">Código</th>
                            <th style="width:18%">Email</th>
                            <th style="width:14%">Nombre</th>
                            <th style="width:12%">Rol Principal</th>
                            <th style="width:8%">Estado</th>
                            <th style="width:8%">Email</th>
                            <th style="width:30%">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody"></tbody>
                </table>
            </div>
        </div>
        <div id="errorMessage" class="alert alert-danger m-3" style="display:none">
            <i class="fas fa-exclamation-circle"></i> <span id="errorText"></span>
        </div>
    </div>
    <div class="card-footer border-top py-3">
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted" id="paginationInfo">Mostrando 0 de 0</small>
            <nav><ul class="pagination pagination-sm mb-0" id="paginationControls"></ul></nav>
        </div>
    </div>
</div>

{{-- Modal: View User Details (New Component) --}}
@include('app.platform-admin.users.partials.view-user-modal')

{{-- Modal: Change Status --}}
<div class="modal fade" id="changeStatusModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-warning"><h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Cambiar Estado</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
    <form id="changeStatusForm">
        <div class="modal-body">
            <div id="statusErrorAlert" class="alert alert-danger" style="display:none"><span id="statusErrorMessage"></span></div>
            <div class="callout callout-info"><i class="fas fa-info-circle"></i> Usuario: <strong id="statusUserName">-</strong></div>
            <div class="form-group">
                <label>Nuevo Estado: <span class="text-danger">*</span></label>
                <select id="newStatus" name="status" class="form-control" required>
                    <option value="">Seleccionar...</option>
                    <option value="active">Activo</option>
                    <option value="suspended">Suspendido</option>
                </select>
            </div>
            <div class="form-group">
                <label>Razón:</label>
                <textarea id="statusReason" name="reason" class="form-control" rows="3" maxlength="500" placeholder="Requerida si suspende..."></textarea>
            </div>
            <input type="hidden" id="statusUserId">
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline-dark" data-dismiss="modal"><i class="fas fa-times"></i> Cancelar</button>
            <button type="submit" id="btnConfirmStatus" class="btn btn-warning"><i class="fas fa-check"></i> Confirmar</button>
        </div>
    </form>
</div></div></div>

{{-- Modal: Delete User --}}
<div class="modal fade" id="deleteUserModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-danger text-white"><h5 class="modal-title"><i class="fas fa-trash"></i> Eliminar Usuario</h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button></div>
    <div class="modal-body">
        <div class="callout callout-danger"><strong>¿Eliminar a <span id="deleteUserName">-</span>?</strong></div>
        <p class="text-muted">Esta acción es irreversible (soft delete).</p>
        <div class="form-group">
            <label>Razón (opcional):</label>
            <textarea id="deleteReason" class="form-control" rows="2" maxlength="500"></textarea>
        </div>
        <input type="hidden" id="deleteUserId">
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-outline-dark" data-dismiss="modal"><i class="fas fa-times"></i> Cancelar</button>
        <button type="button" id="btnConfirmDelete" class="btn btn-danger"><i class="fas fa-trash"></i> Eliminar</button>
    </div>
</div></div></div>

{{-- Include Roles Modal Partial --}}
{{-- Roles Modal Partial Removed - Integrated into View Modal --}}

@endsection

@push('scripts')
<script>
(function() {
    'use strict';
    console.log('[Users] Initializing...');

    const CONFIG = { API_BASE: '/api', TOAST_DELAY: 3000, DEBOUNCE_DELAY: 400, PER_PAGE: 15 };
    const state = { 
        users: [], 
        currentUser: null, 
        currentPage: 1, 
        filters: { 
            status: 'active', 
            role: '', 
            emailVerified: '', 
            companyId: '', 
            search: '', 
            recentActivity: false, 
            orderBy: 'created_at', 
            orderDir: 'desc' 
        }, 
        isLoading: false, 
        isOperating: false, 
        showBackToViewModal: false,
        meta: null, 
        links: null 
    };

    const Utils = {
        getToken() { return window.tokenManager?.getAccessToken() || localStorage.getItem('access_token'); },
        escapeHtml(t) { if(!t)return''; const d=document.createElement('div'); d.textContent=t; return d.innerHTML; },
        formatDate(d) { if(!d)return'N/A'; return new Date(d).toLocaleDateString('es-ES',{year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit'}); },
        translateError(e) { const s=e.status,d=e.data; if(s===401)return'Sesión expirada.'; if(s===403)return d?.message||'Sin permiso.'; if(s===404)return'No encontrado.'; if(s===422&&d?.errors)return Object.values(d.errors).flat().join('. '); return d?.message||'Error al procesar.'; },
        getStatusBadge(s) { const l=s?.toLowerCase(); return{active:'<span class="badge badge-success">Activo</span>',suspended:'<span class="badge badge-warning">Suspendido</span>',deleted:'<span class="badge badge-danger">Eliminado</span>'}[l]||'<span class="badge badge-secondary">-</span>'; },
        getRoleDisplayName(c) { return{USER:'Cliente',AGENT:'Agente',COMPANY_ADMIN:'Admin Empresa',PLATFORM_ADMIN:'Admin Plataforma'}[c]||c; }
    };

    const Toast = {
        success(m,t='Éxito') { $(document).Toasts('create',{class:'bg-success',title:t,body:m,autohide:true,delay:CONFIG.TOAST_DELAY,icon:'fas fa-check-circle'}); },
        error(m,t='Error') { $(document).Toasts('create',{class:'bg-danger',title:t,body:m,autohide:true,delay:CONFIG.TOAST_DELAY+2000,icon:'fas fa-exclamation-circle'}); }
    };

    const API = {
        async loadUsers() {
            if(state.isLoading)return; state.isLoading=true; UI.showLoading();
            try {
                const p = new URLSearchParams({page:state.currentPage,per_page:CONFIG.PER_PAGE});
                if(state.filters.status)p.append('status',state.filters.status);
                if(state.filters.role)p.append('role',state.filters.role);
                if(state.filters.emailVerified!=='')p.append('emailVerified',state.filters.emailVerified);
                if(state.filters.companyId)p.append('companyId',state.filters.companyId);
                if(state.filters.search)p.append('search',state.filters.search);
                if(state.filters.recentActivity)p.append('recentActivity','true');
                if(state.filters.orderBy)p.append('order_by',state.filters.orderBy);
                if(state.filters.orderDir)p.append('order_direction',state.filters.orderDir);
                const r=await fetch(`${CONFIG.API_BASE}/users?${p}`,{headers:{'Authorization':`Bearer ${Utils.getToken()}`,'Accept':'application/json'}});
                const d=await r.json();
                if(!r.ok)throw{status:r.status,data:d};
                state.users=d.data||[]; state.meta=d.meta; state.links=d.links;
                UI.hideLoading(); UI.renderTable(); UI.updatePagination(); this.loadStatistics();
            } catch(e) { UI.showError(Utils.translateError(e)); } finally { state.isLoading=false; }
        },
        async loadStatistics() {
            try {
                const h={headers:{'Authorization':`Bearer ${Utils.getToken()}`,'Accept':'application/json'}};
                const [rTotal,rActive,rSusp,rDel]=await Promise.all([
                    fetch(`${CONFIG.API_BASE}/users?per_page=1`,h),
                    fetch(`${CONFIG.API_BASE}/users?per_page=1&status=active`,h),
                    fetch(`${CONFIG.API_BASE}/users?per_page=1&status=suspended`,h),
                    fetch(`${CONFIG.API_BASE}/users?per_page=1&status=deleted`,h)
                ]);
                const [dTotal,dActive,dSusp,dDel]=await Promise.all([rTotal.json(),rActive.json(),rSusp.json(),rDel.json()]);
                $('#totalUsers').text(dTotal.meta?.total||0);
                $('#activeUsers').text(dActive.meta?.total||0);
                $('#suspendedUsers').text(dSusp.meta?.total||0);
                $('#deletedUsers').text(dDel.meta?.total||0);
            } catch(e) { console.error('[Users] Stats error:',e); }
        },
        async loadCompanies() {
            try {
                const r=await fetch(`${CONFIG.API_BASE}/companies/minimal?per_page=50`,{headers:{'Accept':'application/json'}});
                if(!r.ok)return; const d=await r.json();
                const s=$('#companyFilter'); s.html('<option value="">Todas</option>');
                (d.data||[]).forEach(c=>s.append(`<option value="${c.id}">${Utils.escapeHtml(c.name)}</option>`));
            } catch(e) { console.error('[Users] Companies error:',e); }
        },
        async changeStatus(id,status,reason) {
            const r=await fetch(`${CONFIG.API_BASE}/users/${id}/status`,{method:'PUT',headers:{'Authorization':`Bearer ${Utils.getToken()}`,'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({status,reason:reason||undefined})});
            const d=await r.json(); if(!r.ok)throw{status:r.status,data:d}; return{success:true};
        },
        async deleteUser(id,reason) {
            let u=`${CONFIG.API_BASE}/users/${id}`; if(reason)u+=`?reason=${encodeURIComponent(reason)}`;
            const r=await fetch(u,{method:'DELETE',headers:{'Authorization':`Bearer ${Utils.getToken()}`,'Accept':'application/json'}});
            const d=await r.json(); if(!r.ok)throw{status:r.status,data:d}; return{success:true};
        }
    };

    const UI = {
        showLoading() { $('#loadingSpinner').show(); $('#tableContainer,#errorMessage').hide(); },
        hideLoading() { $('#loadingSpinner').hide(); $('#tableContainer').show(); },
        showError(m) { $('#loadingSpinner,#tableContainer').hide(); $('#errorText').text(m); $('#errorMessage').show(); },
        renderTable() {

            const tb=$('#usersTableBody'); if(!state.users.length){tb.html('<tr><td colspan="7" class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x mb-2"></i><p>No hay usuarios</p></td></tr>');return;}
            tb.html(state.users.map(u=>{
                const name=u.profile?`${u.profile.firstName||''} ${u.profile.lastName||''}`.trim():'N/A';
                const role=u.roleContexts?.length?Utils.getRoleDisplayName(u.roleContexts[0].roleCode):'Sin Rol';
                const ev=u.emailVerified?'<span class="badge badge-success">Sí</span>':'<span class="badge badge-secondary">No</span>';
                return`<tr data-id="${u.id}"><td><code>${Utils.escapeHtml(u.userCode||'N/A')}</code></td><td>${Utils.escapeHtml(u.email)}</td><td>${Utils.escapeHtml(name)}</td><td><span class="badge badge-info">${role}</span></td><td>${Utils.getStatusBadge(u.status)}</td><td>${ev}</td><td class="text-nowrap"><button class="btn btn-sm btn-primary btn-view" data-id="${u.id}" title="Ver"><i class="fas fa-eye"></i></button> <button class="btn btn-sm btn-indigo btn-roles" data-id="${u.id}" title="Roles"><i class="fas fa-shield-alt"></i></button> <button class="btn btn-sm btn-warning btn-status" data-id="${u.id}" title="Estado"><i class="fas fa-ban"></i></button> <button class="btn btn-sm btn-danger btn-delete" data-id="${u.id}" title="Eliminar"><i class="fas fa-trash"></i></button></td></tr>`;
            }).join('')); this.attachRowEvents();
        },
        attachRowEvents() {
            $('.btn-view').off('click').on('click',function(){Modals.openView($(this).data('id'));});
            $('.btn-roles').off('click').on('click',function(){Modals.openRoles($(this).data('id'));});
            $('.btn-status').off('click').on('click',function(){Modals.openStatus($(this).data('id'));});
            $('.btn-delete').off('click').on('click',function(){Modals.openDelete($(this).data('id'));});
        },
        updatePagination() {
            const m=state.meta,l=state.links;
            // Calculate from/to since API doesn't provide them
            const from = m && m.total > 0 ? ((m.current_page - 1) * m.per_page) + 1 : 0;
            const to = m ? Math.min(m.current_page * m.per_page, m.total) : 0;
            $('#paginationInfo').text(`Mostrando ${from} a ${to} de ${m?.total||0}`);
            const c=$('#paginationControls'); c.empty(); if(!m||m.last_page<=1)return;
            c.append(`<li class="page-item ${!l?.prev?'disabled':''}"><a class="page-link" href="#" data-action="prev"><i class="fas fa-chevron-left"></i></a></li>`);
            for(let i=1;i<=m.last_page;i++) c.append(`<li class="page-item ${i===m.current_page?'active':''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`);
            c.append(`<li class="page-item ${!l?.next?'disabled':''}"><a class="page-link" href="#" data-action="next"><i class="fas fa-chevron-right"></i></a></li>`);
            c.find('a').on('click',function(e){e.preventDefault();const p=$(this).data('page'),a=$(this).data('action');if(p)state.currentPage=p;else if(a==='prev'&&state.currentPage>1)state.currentPage--;else if(a==='next'&&state.currentPage<m.last_page)state.currentPage++;API.loadUsers();});
        },
        updateFilterSelection() { $('.info-box').removeClass('elevation-2'); $(`#infoBox${state.filters.status?state.filters.status.charAt(0).toUpperCase()+state.filters.status.slice(1):'All'}`).addClass('elevation-2'); }
    };

    const Modals = {
        openView(id) {
            const u=state.users.find(x=>x.id===id); if(!u){Toast.error('Usuario no encontrado');return;} state.currentUser=u;
            // Use the new ViewUserModal component
            if(typeof ViewUserModal !== 'undefined') {
                ViewUserModal.open(u);
            } else {
                console.error('[Users] ViewUserModal component not found');
                Toast.error('Error al abrir modal');
            }
        },
        openRoles(id) {
            const u=state.users.find(x=>x.id===id); if(!u){Toast.error('Usuario no encontrado');return;} state.currentUser=u;
            // Use new ViewUserModal with Roles tab
            if(typeof ViewUserModal !== 'undefined') {
                ViewUserModal.openRolesTab(u);
            } else {
                console.error('[Users] ViewUserModal component not found');
                Toast.error('Error al abrir modal');
            }
        },
        openStatus(id) {
            const u=state.users.find(x=>x.id===id); if(!u){Toast.error('Usuario no encontrado');return;} state.currentUser=u;
            $('#viewUserModal').modal('hide'); $('#changeStatusForm')[0].reset(); $('#statusErrorAlert').hide();
            $('#statusUserId').val(id); $('#statusUserName').text(u.email); $('#btnConfirmStatus').prop('disabled',false).html('<i class="fas fa-check"></i> Confirmar');
            $('#changeStatusModal').modal('show');
        },
        async handleStatus(e) {
            e.preventDefault(); const id=$('#statusUserId').val(),st=$('#newStatus').val(),re=$('#statusReason').val().trim();
            if(!st){Toast.error('Selecciona un estado');return;} if(st==='suspended'&&re.length<10){Toast.error('Razón mínima 10 caracteres');return;}
            if(state.isOperating)return; state.isOperating=true;
            $('#btnConfirmStatus').prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');
            try { await API.changeStatus(id,st,re); Toast.success('Estado actualizado'); $('#changeStatusModal').modal('hide'); API.loadUsers(); }
            catch(e) { $('#statusErrorMessage').text(Utils.translateError(e)); $('#statusErrorAlert').show(); }
            finally { $('#btnConfirmStatus').prop('disabled',false).html('<i class="fas fa-check"></i> Confirmar'); state.isOperating=false; }
        },
        openDelete(id) {
            const u=state.users.find(x=>x.id===id); if(!u){Toast.error('Usuario no encontrado');return;} state.currentUser=u;
            $('#viewUserModal').modal('hide'); $('#deleteUserId').val(id); $('#deleteUserName').text(u.email); $('#deleteReason').val('');
            $('#btnConfirmDelete').prop('disabled',false).html('<i class="fas fa-trash"></i> Eliminar');
            $('#deleteUserModal').modal('show');
        },
        async handleDelete() {
            const id=$('#deleteUserId').val(),re=$('#deleteReason').val().trim();
            if(state.isOperating)return; state.isOperating=true;
            $('#btnConfirmDelete').prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i> Eliminando...');
            try { await API.deleteUser(id,re); Toast.success('Usuario eliminado'); $('#deleteUserModal').modal('hide'); API.loadUsers(); }
            catch(e) { Toast.error(Utils.translateError(e)); }
            finally { $('#btnConfirmDelete').prop('disabled',false).html('<i class="fas fa-trash"></i> Eliminar'); state.isOperating=false; }
        }
    };

    function initEvents() {
        let st; $('#searchInput').on('input',function(){clearTimeout(st);const v=$(this).val().trim();st=setTimeout(()=>{state.filters.search=v;state.currentPage=1;API.loadUsers();},CONFIG.DEBOUNCE_DELAY);});
        $('#statusFilter').on('change',function(){state.filters.status=$(this).val();state.currentPage=1;UI.updateFilterSelection();API.loadUsers();});
        $('#roleFilter').on('change',function(){state.filters.role=$(this).val();state.currentPage=1;API.loadUsers();});
        $('#emailVerifiedFilter').on('change',function(){state.filters.emailVerified=$(this).val();state.currentPage=1;API.loadUsers();});
        $('#companyFilter').on('change',function(){state.filters.companyId=$(this).val();state.currentPage=1;API.loadUsers();});
        $('#orderByFilter').on('change',function(){state.filters.orderBy=$(this).val();API.loadUsers();});
        $('#orderDirFilter').on('change',function(){state.filters.orderDir=$(this).val();API.loadUsers();});
        $('#recentActivityFilter').on('change',function(){state.filters.recentActivity=$(this).is(':checked');state.currentPage=1;API.loadUsers();});
        $('#btnResetFilters').on('click',function(){state.filters={status:'active'};state.currentPage=1;$('#searchInput').val('');$('#statusFilter').val('active');$('#roleFilter,#emailVerifiedFilter,#companyFilter').val('');$('#orderByFilter').val('created_at');$('#orderDirFilter').val('desc');$('#recentActivityFilter').prop('checked',false);UI.updateFilterSelection();API.loadUsers();});
        $('#btnRefresh').on('click',()=>API.loadUsers());
        $('#infoBoxAll').on('click',()=>{state.filters.status='';state.currentPage=1;$('#statusFilter').val('');UI.updateFilterSelection();API.loadUsers();});
        $('#infoBoxActive').on('click',()=>{state.filters.status='active';state.currentPage=1;$('#statusFilter').val('active');UI.updateFilterSelection();API.loadUsers();});
        $('#infoBoxSuspended').on('click',()=>{state.filters.status='suspended';state.currentPage=1;$('#statusFilter').val('suspended');UI.updateFilterSelection();API.loadUsers();});
        $('#infoBoxDeleted').on('click',()=>{state.filters.status='deleted';state.currentPage=1;$('#statusFilter').val('deleted');UI.updateFilterSelection();API.loadUsers();});
        $('#btnModalRoles').on('click',()=>{if(state.currentUser)Modals.openRoles(state.currentUser.id);});
        $('#btnModalChangeStatus').on('click',()=>{if(state.currentUser)Modals.openStatus(state.currentUser.id);});
        $('#changeStatusForm').on('submit',e=>Modals.handleStatus(e));
        $('#btnConfirmDelete').on('click',()=>Modals.handleDelete());
        window.onRolesUpdated=()=>API.loadUsers();
        
        // Listen for events from ViewUserModal component
        $(document).on('openStatusModal', function(e, userId, userData, showBack) {
            state.currentUser = userData;
            state.showBackToViewModal = showBack || false;
            Modals.openStatus(userId);
        });
        $(document).on('openDeleteModal', function(e, userId, userData, showBack) {
            state.currentUser = userData;
            state.showBackToViewModal = showBack || false;
            Modals.openDelete(userId);
        });
        $(document).on('userRolesChanged', function(e, userId) {
            API.loadUsers();
        });
        
        // Handle "back to view" from status/delete modals
        $('#changeStatusModal').on('hidden.bs.modal', function() {
            if (state.showBackToViewModal && state.currentUser && !state.isOperating) {
                setTimeout(() => ViewUserModal.open(state.currentUser), 200);
            }
            state.showBackToViewModal = false;
        });
        $('#deleteUserModal').on('hidden.bs.modal', function() {
            if (state.showBackToViewModal && state.currentUser && !state.isOperating) {
                setTimeout(() => ViewUserModal.open(state.currentUser), 200);
            }
            state.showBackToViewModal = false;
        });
        
        // Expose showToast globally for components
        window.showToast = function(type, message) {
            if (type === 'success') Toast.success(message);
            else if (type === 'error') Toast.error(message);
            else if (type === 'warning') Toast.error(message, 'Advertencia');
            else Toast.success(message);
        };
    }

    async function init() {
        if(!Utils.getToken()){Toast.error('Token no encontrado');UI.showError('Error de autenticación');return;}
        initEvents(); UI.updateFilterSelection(); await API.loadCompanies(); await API.loadUsers();
        console.log('[Users] ✓ Initialized');
    }

    $(document).ready(init);
})();
</script>
@endpush
