<!-- Modal: Confirmar Acción (Resolver/Cerrar/Reabrir) -->
<div class="modal fade" id="confirmActionModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header"
                 :class="{
                     'bg-success': actionModal.action === 'resolve',
                     'bg-danger': actionModal.action === 'close',
                     'bg-info': actionModal.action === 'reopen'
                 }">
                <h5 class="modal-title text-white">
                    <i class="fas fa-question-circle mr-2"></i>
                    <span x-text="actionModal.title">Confirmar Acción</span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p x-text="actionModal.message">¿Estás seguro de realizar esta acción?</p>

                <div class="form-group mt-3">
                    <label for="action-note">
                        <i class="fas fa-sticky-note mr-2"></i>Nota
                        <template x-if="actionModal.action === 'reopen'">
                            <span class="text-danger">*</span>
                        </template>
                        <template x-if="actionModal.action !== 'reopen'">
                            <span class="text-muted">(Opcional)</span>
                        </template>
                    </label>
                    <textarea class="form-control"
                              id="action-note"
                              x-model="actionModal.note"
                              rows="3"
                              maxlength="5000"
                              :placeholder="actionModal.action === 'resolve' ? 'Ej: Problema resuelto actualizando la configuración del servidor...' :
                                           actionModal.action === 'close' ? 'Ej: Cerrando ticket tras confirmación del usuario...' :
                                           'Ej: El problema volvió a ocurrir después de la actualización...'"
                              :required="actionModal.action === 'reopen'"></textarea>
                    <small class="form-text text-muted">
                        <template x-if="actionModal.action === 'reopen'">
                            <span class="text-danger">Es obligatorio proporcionar una razón para reabrir un ticket</span>
                        </template>
                        <template x-if="actionModal.action !== 'reopen'">
                            <span>Máximo 5000 caracteres</span>
                        </template>
                    </small>
                </div>

                <!-- Información adicional según la acción -->
                <template x-if="actionModal.action === 'resolve'">
                    <div class="alert alert-success">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Resolver ticket:</strong> El ticket cambiará su estado a "Resuelto".
                        El usuario podrá cerrarlo si está conforme, o reabrirlo dentro de los 30 días si el problema persiste.
                    </div>
                </template>

                <template x-if="actionModal.action === 'close'">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Cerrar ticket:</strong> El ticket cambiará su estado a "Cerrado".
                        No se podrán agregar más respuestas. El usuario puede reabrirlo dentro de los 30 días.
                    </div>
                </template>

                <template x-if="actionModal.action === 'reopen'">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Reabrir ticket:</strong> El ticket cambiará su estado a "Pendiente".
                        Se podrán agregar nuevas respuestas y continuar trabajando en el problema.
                    </div>
                </template>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-2"></i>Cancelar
                </button>
                <button type="button"
                        class="btn"
                        :class="{
                            'btn-success': actionModal.action === 'resolve',
                            'btn-danger': actionModal.action === 'close',
                            'btn-info': actionModal.action === 'reopen'
                        }"
                        @click="executeAction()"
                        :disabled="actionModal.action === 'reopen' && !actionModal.note.trim()">
                    <i class="fas fa-check mr-2"></i>Confirmar
                </button>
            </div>
        </div>
    </div>
</div>
