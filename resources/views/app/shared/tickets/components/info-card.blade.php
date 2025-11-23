<!-- Ticket Info -->
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">Información del Ticket</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="card-body p-2">
        <div class="mb-2">
            <strong><i class="fas fa-code mr-1"></i> Código</strong>
            <p class="text-muted mb-0" style="font-size: 0.9rem;" id="t-info-code">...</p>
        </div>
        <hr class="my-1">

        <div class="mb-2">
            <strong><i class="fas fa-hourglass-half mr-1"></i> Estado</strong>
            <p class="mb-0" id="t-info-status">...</p>
        </div>
        <hr class="my-1">

        <!-- Company Info (Visible for Agents/Admins mainly, but structure is here) -->
        <div class="mb-2" id="t-info-company-container">
            <strong><i class="fas fa-building mr-1"></i> Empresa</strong>
            <p class="text-muted mb-0" style="font-size: 0.9rem;" id="t-info-company">...</p>
        </div>
        <hr class="my-1" id="t-info-company-divider">

        <div class="mb-2">
            <strong><i class="fas fa-list mr-1"></i> Categoría</strong>
            <p class="text-muted mb-0" style="font-size: 0.9rem;" id="t-info-category">...</p>
        </div>
        <hr class="my-1">

        <div class="mb-2">
            <strong><i class="fas fa-user-shield mr-1"></i> Agente Asignado</strong>
            <p class="text-muted mb-0" style="font-size: 0.9rem;" id="t-info-agent">...</p>
        </div>
        <hr class="my-1">

        <div class="mb-2">
            <strong><i class="far fa-calendar-alt mr-1"></i> Creado</strong>
            <p class="text-muted mb-0" style="font-size: 0.9rem;" id="t-info-created">...</p>
        </div>
        <hr class="my-1">

        <div class="mb-2">
            <strong><i class="far fa-clock mr-1"></i> Última Actividad</strong>
            <p class="text-muted mb-0" style="font-size: 0.9rem;" id="t-info-updated">...</p>
        </div>
        <hr class="my-1">

        <div class="mb-0">
            <strong><i class="fas fa-comments mr-1"></i> Respuestas</strong>
            <p class="text-muted mb-0" style="font-size: 0.9rem;" id="t-info-responses">...</p>
        </div>
    </div>
</div>
