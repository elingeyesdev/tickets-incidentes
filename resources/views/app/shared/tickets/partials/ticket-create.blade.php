{{-- Ticket Creation Card - AdminLTE v3 --}}
<div class="col-md-9">
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-plus mr-2"></i>Crear Nuevo Ticket
            </h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>

        <form @submit.prevent="createTicket()">
            <div class="card-body">
                {{-- Title Field --}}
                <div class="form-group">
                    <label for="ticketTitle">
                        <i class="fas fa-heading mr-2"></i>Título <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                           class="form-control"
                           id="ticketTitle"
                           x-model="newTicket.title"
                           placeholder="Ej: Error al exportar reporte"
                           minlength="5"
                           maxlength="255"
                           required>
                    <small class="form-text text-muted">Mínimo 5 caracteres, máximo 255</small>
                </div>

                {{-- Category Field --}}
                <div class="form-group">
                    <label for="ticketCategory">
                        <i class="fas fa-tag mr-2"></i>Categoría <span class="text-danger">*</span>
                    </label>
                    <select class="form-control select2"
                            id="ticketCategory"
                            x-model="newTicket.category_id"
                            style="width: 100%;"
                            required>
                        <option value="">Selecciona una categoría...</option>
                    </select>
                </div>

                {{-- Description Field --}}
                <div class="form-group">
                    <label for="ticketDescription">
                        <i class="fas fa-align-left mr-2"></i>Descripción <span class="text-danger">*</span>
                    </label>
                    <textarea class="form-control"
                              id="ticketDescription"
                              x-model="newTicket.description"
                              rows="5"
                              placeholder="Describe tu problema en detalle..."
                              minlength="10"
                              required></textarea>
                    <small class="form-text text-muted">Mínimo 10 caracteres. Se lo más específico posible.</small>
                </div>

                {{-- Attachments Field --}}
                <div class="form-group">
                    <label for="ticketAttachment">
                        <i class="fas fa-paperclip mr-2"></i>Adjuntos (Opcional)
                    </label>
                    <div class="custom-file">
                        <input type="file"
                               class="custom-file-input"
                               id="ticketAttachment"
                               @change="handleTicketFiles"
                               multiple>
                        <label class="custom-file-label" for="ticketAttachment">Seleccionar archivos...</label>
                    </div>
                    <small class="form-text text-muted d-block mt-2">
                        Máximo 5 archivos, 10 MB cada uno. Tipos: PDF, TXT, DOC, DOCX, XLS, XLSX, CSV, JPG, PNG, GIF, MP4
                    </small>

                    {{-- Files List --}}
                    <template x-if="newTicket.files.length > 0">
                        <ul class="list-unstyled mt-3">
                            <template x-for="(file, index) in newTicket.files" :key="index">
                                <li class="text-sm py-2 border-bottom">
                                    <i class="fas fa-file mr-2 text-muted"></i>
                                    <span x-text="file.name" class="font-weight-500"></span>
                                    <span class="text-muted ml-2" x-text="'(' + formatFileSize(file.size) + ')'"></span>
                                    <a href="#"
                                       class="text-danger float-right"
                                       @click.prevent="removeTicketFile(index)">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </li>
                            </template>
                        </ul>
                    </template>
                </div>
            </div>

            {{-- Card Footer with Actions --}}
            <div class="card-footer">
                <button type="button"
                        class="btn btn-secondary"
                        @click="resetNewTicket()">
                    <i class="fas fa-times mr-2"></i>Limpiar
                </button>
                <button type="submit"
                        class="btn btn-primary float-right"
                        :disabled="isCreating">
                    <template x-if="isCreating">
                        <span>
                            <i class="fas fa-spinner fa-spin mr-2"></i>Creando...
                        </span>
                    </template>
                    <template x-if="!isCreating">
                        <span>
                            <i class="fas fa-check mr-2"></i>Crear Ticket
                        </span>
                    </template>
                </button>
            </div>
        </form>
    </div>
</div>
