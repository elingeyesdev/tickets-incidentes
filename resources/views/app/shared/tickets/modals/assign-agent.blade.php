<!-- Modal: Asignar Agente (AGENT/COMPANY_ADMIN) -->
<div class="modal fade" id="assignAgentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus mr-2"></i>Asignar Ticket a Agente
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="assignAgentForm">
                    <div class="form-group">
                        <label for="agent-select">
                            <i class="fas fa-user-tie mr-2"></i>Seleccionar Agente
                        </label>
                        <select class="form-control select2"
                                id="agent-select"
                                x-model="assignModal.agent_id"
                                style="width: 100%;"
                                required>
                            <option value="">Selecciona un agente...</option>
                            <!-- Agents will be loaded dynamically via API -->
                            <!-- This is a placeholder structure, in production you'd load agents from API -->
                        </select>
                        <small class="form-text text-muted">
                            El agente seleccionado recibirá una notificación
                        </small>
                    </div>
                    <div class="form-group">
                        <label for="assignment-note">
                            <i class="fas fa-sticky-note mr-2"></i>Nota (Opcional)
                        </label>
                        <textarea class="form-control"
                                  id="assignment-note"
                                  x-model="assignModal.note"
                                  rows="3"
                                  maxlength="5000"
                                  placeholder="Ej: Asignando a este agente por su experiencia en módulos similares..."></textarea>
                        <small class="form-text text-muted">
                            Máximo 5000 caracteres
                        </small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-2"></i>Cancelar
                </button>
                <button type="button"
                        class="btn btn-warning"
                        @click="executeAssign()">
                    <i class="fas fa-check mr-2"></i>Asignar
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize Select2 on modal show
    $('#assignAgentModal').on('shown.bs.modal', function () {
        if (!$('#agent-select').hasClass('select2-hidden-accessible')) {
            $('#agent-select').select2({
                theme: 'bootstrap4',
                dropdownParent: $('#assignAgentModal'),
                placeholder: 'Selecciona un agente...',
                allowClear: true,
                ajax: {
                    url: '/api/users',
                    headers: {
                        'Authorization': 'Bearer ' + window.tokenManager.getAccessToken()
                    },
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            search: params.term,
                            role: 'AGENT',
                            company_id: '{{ $companyId ?? "" }}',
                            per_page: 20
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: (data.data || []).map(function(user) {
                                return {
                                    id: user.id,
                                    text: user.name + ' (' + user.email + ')'
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