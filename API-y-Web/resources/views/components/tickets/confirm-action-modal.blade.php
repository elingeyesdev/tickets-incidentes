<div class="modal fade" id="modal-confirm-action" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="confirm-modal-title">Confirmar Acción</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="confirm-modal-body">¿Está seguro de realizar esta acción?</p>
                <input type="hidden" id="confirm-action-type">
                <input type="hidden" id="confirm-ticket-code">
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-confirm-action">Confirmar</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        const $modalConfirm = $('#modal-confirm-action');
        const $btnConfirm = $('#btn-confirm-action');
        
        // Trigger Event
        $(document).on('click', '.btn-trigger-confirm', function(e) {
            e.preventDefault();
            const action = $(this).data('action');
            const ticketCode = $(this).data('ticket-code');
            const title = $(this).data('title') || 'Confirmar Acción';
            const message = $(this).data('message') || '¿Está seguro?';
            const btnClass = $(this).data('btn-class') || 'btn-primary';
            const btnText = $(this).data('btn-text') || 'Confirmar';

            $('#confirm-modal-title').text(title);
            $('#confirm-modal-body').text(message);
            $('#confirm-action-type').val(action);
            $('#confirm-ticket-code').val(ticketCode);
            
            $btnConfirm.removeClass('btn-primary btn-danger btn-warning btn-success')
                       .addClass(btnClass)
                       .text(btnText);

            $modalConfirm.modal('show');
        });

        // Confirm Action
        $btnConfirm.on('click', function() {
            const action = $('#confirm-action-type').val();
            const ticketCode = $('#confirm-ticket-code').val();
            
            $modalConfirm.modal('hide');
            
            // Trigger specific event based on action
            $(document).trigger(`tickets:action-confirmed:${action}`, [ticketCode]);
        });
    });
</script>
@endpush
