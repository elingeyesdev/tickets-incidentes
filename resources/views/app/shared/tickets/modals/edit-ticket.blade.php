<!-- Modal: Editar Ticket (AGENT/COMPANY_ADMIN) -->
<div class="modal fade" id="editTicketModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white">
                    <i class="fas fa-edit mr-2"></i>Editar Ticket
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editTicketForm">
                    <div class="form-group">
                        <label for="edit-title">
                            <i class="fas fa-heading mr-2"></i>Título <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               class="form-control"
                               id="edit-title"
                               x-model="editModal.title"
                               minlength="5"
                               maxlength="255"
                               required>
                        <small class="form-text text-muted">
                            Mínimo 5 caracteres, máximo 255
                        </small>
                    </div>
                    <div class="form-group">
                        <label for="edit-category">
                            <i class="fas fa-tag mr-2"></i>Categoría <span class="text-danger">*</span>
                        </label>
                        <select class="form-control select2"
                                id="edit-category"
                                x-model="editModal.category_id"
                                style="width: 100%;"
                                required>
                            <option value="">Selecciona una categoría...</option>
                            <!-- Categories will be loaded dynamically via API -->
                        </select>
                        <small class="form-text text-muted">
                            La categoría debe estar activa
                        </small>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Nota:</strong> Solo puedes editar el título y la categoría del ticket.
                        La descripción y otros campos no pueden ser modificados después de la creación.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-2"></i>Cancelar
                </button>
                <button type="button"
                        class="btn btn-primary"
                        @click="executeEdit()">
                    <i class="fas fa-save mr-2"></i>Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize Select2 on modal show
    $('#editTicketModal').on('shown.bs.modal', function () {
        if (!$('#edit-category').hasClass('select2-hidden-accessible')) {
            $('#edit-category').select2({
                theme: 'bootstrap4',
                dropdownParent: $('#editTicketModal'),
                placeholder: 'Selecciona una categoría...',
                allowClear: false,
                ajax: {
                    url: '/api/tickets/categories',
                    headers: {
                        'Authorization': 'Bearer ' + window.tokenManager.getAccessToken()
                    },
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            company_id: '{{ $companyId ?? "" }}',
                            is_active: 'true',
                            per_page: 100
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: (data.data || []).map(function(category) {
                                return {
                                    id: category.id,
                                    text: category.name
                                };
                            })
                        };
                    },
                    cache: true
                }
            });
        }
    });
});
</script>
@endpush
