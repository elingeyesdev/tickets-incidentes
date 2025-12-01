<div class="modal fade" id="modal-assign-agent" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Asignar Agente</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="form-assign-agent">
                <div class="modal-body">
                    <input type="hidden" id="assign-ticket-code" name="ticket_code">
                    
                    <div class="form-group">
                        <label for="assign-agent-select">Agente <span class="text-danger">*</span></label>
                        <select class="form-control select2" id="assign-agent-select" name="new_agent_id" style="width: 100%;" required>
                            <option value="">Cargando agentes...</option>
                        </select>
                        <div class="invalid-feedback">Por favor seleccione un agente.</div>
                    </div>

                    <div class="form-group">
                        <label for="assign-note">Nota de Asignación (Opcional)</label>
                        <textarea class="form-control" id="assign-note" name="assignment_note" rows="3" placeholder="Motivo de la asignación..."></textarea>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btn-submit-assign">
                        <i class="fas fa-user-check mr-1"></i> Asignar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        const $modalAssign = $('#modal-assign-agent');
        const $formAssign = $('#form-assign-agent');
        const $agentSelect = $('#assign-agent-select');
        const $btnSubmit = $('#btn-submit-assign');
        let agentsLoaded = false;

        // Initialize Select2
        $agentSelect.select2({
            theme: 'bootstrap4',
            dropdownParent: $modalAssign,
            placeholder: 'Seleccione un agente'
        });

        // Load Agents Function
        async function loadAgents() {
            try {
                const token = window.tokenManager.getAccessToken();
                // Fetch users with role AGENT
                // Note: The backend now allows AGENT role to list users, scoped to their company
                const response = await fetch('/api/users?role=AGENT&per_page=100', {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) throw new Error('Error al cargar agentes');

                const data = await response.json();
                const agents = data.data;

                $agentSelect.empty();
                $agentSelect.append('<option value="">Seleccione un agente</option>');

                agents.forEach(agent => {
                    let name = agent.email; // Default to email
                    if (agent.profile && agent.profile.first_name) {
                        name = `${agent.profile.first_name} ${agent.profile.last_name || ''}`.trim();
                    } else if (agent.name) {
                        name = agent.name;
                    }
                    $agentSelect.append(`<option value="${agent.id}">${name}</option>`);
                });

                agentsLoaded = true;

            } catch (error) {
                console.error('Error loading agents:', error);
                $(document).Toasts('create', {
                    class: 'bg-danger',
                    title: 'Error',
                    body: 'No se pudieron cargar los agentes. Por favor intente nuevamente.'
                });
            }
        }

        // Open Modal Event
        $(document).on('click', '.btn-trigger-assign', function(e) {
            e.preventDefault();
            const ticketCode = $(this).data('ticket-code');
            const currentAgentId = $(this).data('current-agent-id'); // Optional: to preselect or exclude

            $('#assign-ticket-code').val(ticketCode);
            $('#assign-note').val(''); // Clear note
            
            // Load agents if not loaded
            if (!agentsLoaded) {
                loadAgents().then(() => {
                     if (currentAgentId) {
                        $agentSelect.val(currentAgentId).trigger('change');
                    } else {
                        $agentSelect.val('').trigger('change');
                    }
                });
            } else {
                 if (currentAgentId) {
                    $agentSelect.val(currentAgentId).trigger('change');
                } else {
                    $agentSelect.val('').trigger('change');
                }
            }

            $modalAssign.modal('show');
        });

        // Submit Form
        $formAssign.on('submit', async function(e) {
            e.preventDefault();

            // Basic Validation
            if (!$agentSelect.val()) {
                $(document).Toasts('create', {
                    class: 'bg-warning',
                    title: 'Atención',
                    body: 'Debe seleccionar un agente.'
                });
                return;
            }

            const ticketCode = $('#assign-ticket-code').val();
            const formData = {
                new_agent_id: $agentSelect.val(),
                assignment_note: $('#assign-note').val()
            };

            // Loading State
            const originalBtnText = $btnSubmit.html();
            $btnSubmit.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Asignando...');

            try {
                const token = window.tokenManager.getAccessToken();
                const response = await fetch(`/api/tickets/${ticketCode}/assign`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || 'Error al asignar el ticket');
                }

                // Success
                $modalAssign.modal('hide');
                $(document).Toasts('create', {
                    class: 'bg-success',
                    title: 'Éxito',
                    body: 'Agente asignado correctamente.'
                });

                // Refresh List
                $(document).trigger('tickets:refresh-list');
                $(document).trigger('tickets:stats-update-required');

            } catch (error) {
                console.error('Error assigning ticket:', error);
                $(document).Toasts('create', {
                    class: 'bg-danger',
                    title: 'Error',
                    body: error.message
                });
            } finally {
                $btnSubmit.prop('disabled', false).html(originalBtnText);
            }
        });
    });
</script>
@endpush
