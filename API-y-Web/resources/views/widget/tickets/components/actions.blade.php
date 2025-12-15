<!-- Actions -->
<div class="card card-outline card-success" id="card-ticket-actions">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h3 class="card-title">Acciones</h3>
            <div class="d-flex gap-2 align-items-center">
                <!-- Status Select (Agent/Admin Only) -->
                <select class="form-control form-control-sm d-none" style="width: 140px;" id="action-status-select">
                    <option value="open">Abierto</option>
                    <option value="pending">Pendiente</option>
                    <option value="resolved">Resuelto</option>
                    <option value="closed">Cerrado</option>
                </select>
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>
    </div>
    <div class="card-footer">
        <!-- Cambiar Estado -->
        <div class="mb-3">
            <small class="text-muted d-block mb-2">
                <i class="fas fa-exchange-alt mr-1"></i> Cambiar Estado
            </small>
            <div class="d-flex flex-column flex-sm-row flex-wrap gap-2" id="action-buttons-container">
            <!-- Resolver: Solo OPEN o PENDING (Agent) -->
                <button type="button" class="btn btn-success d-none btn-trigger-confirm" id="btn-action-resolve"
                        data-action="resolve"
                        data-title="Resolver Ticket"
                        data-message="¿Está seguro de marcar este ticket como resuelto?"
                        data-btn-class="btn-success"
                        data-btn-text="Resolver"
                        title="Solo disponible en OPEN o PENDING">
                    <i class="fas fa-check-circle mr-1"></i> Resolver
                </button>

                <!-- Reabrir: Solo RESOLVED o CLOSED (User/Agent) -->
                <button type="button" class="btn btn-warning d-none btn-trigger-confirm" id="btn-action-reopen"
                        data-action="reopen"
                        data-title="Reabrir Ticket"
                        data-message="¿Está seguro de reabrir este ticket?"
                        data-btn-class="btn-warning"
                        data-btn-text="Reabrir"
                        title="Solo disponible en RESOLVED o CLOSED">
                    <i class="fas fa-redo mr-1"></i> Reabrir
                </button>

                <!-- Cerrar: Todos menos CLOSED (User/Agent) -->
                <button type="button" class="btn btn-secondary d-none btn-trigger-confirm" id="btn-action-close"
                        data-action="close"
                        data-title="Cerrar Ticket"
                        data-message="¿Está seguro de cerrar este ticket permanentemente?"
                        data-btn-class="btn-secondary"
                        data-btn-text="Cerrar"
                        title="No disponible en CLOSED">
                    <i class="fas fa-times-circle mr-1"></i> Cerrar
                </button>
            </div>
        </div>

        <!-- Asignación (Agent/Admin Only) -->
        <div class="border-top pt-3 d-none" id="action-section-assign">
            <small class="text-muted d-block mb-2">
                <i class="fas fa-user-tie mr-1"></i> Asignación
            </small>
            <!-- Reasignar: Siempre disponible -->
            <button type="button" class="btn btn-info btn-trigger-assign" id="btn-action-assign">
                <i class="fas fa-user-plus mr-1"></i> <span id="lbl-action-assign">Asignar / Reasignar</span>
            </button>
        </div>

        <!-- Recordatorio (Agent Only) -->
        <div class="border-top pt-3 d-none" id="action-section-remind">
            <small class="text-muted d-block mb-2">
                <i class="fas fa-bell mr-1"></i> Notificación
            </small>
            <!-- Enviar Recordatorio: Solo AGENT -->
            <button type="button" class="btn btn-warning btn-trigger-confirm" id="btn-action-remind"
                    data-action="remind"
                    data-title="Enviar Recordatorio"
                    data-message="¿Enviar recordatorio por email al creador del ticket?"
                    data-btn-class="btn-warning"
                    data-btn-text="Enviar Recordatorio">
                <i class="fas fa-envelope mr-1"></i> Enviar Recordatorio por Email
            </button>
        </div>
    </div>
</div>
