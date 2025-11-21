<!-- Mensaje si ticket está cerrado -->
<div class="card" x-show="ticket.status === 'closed'" x-cloak>
    <div class="card-body bg-light text-center p-4">
        <i class="fas fa-lock fa-3x text-muted mb-3"></i>
        <h5>Este ticket está cerrado</h5>
        <p class="text-muted">
            No se pueden agregar más respuestas a tickets cerrados.
            @if($role === 'USER')
            Si necesitas reabrir este ticket, puedes hacerlo dentro de los 30 días posteriores al cierre.
            @endif
        </p>
    </div>
</div>