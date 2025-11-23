@push('css')
<style>
    /* Select2 height consistency with form-control */
    .select2-container--bootstrap4 .select2-selection--single {
        height: 38px !important;
        min-height: 38px !important;
        display: flex !important;
        align-items: center !important;
        padding: 0 !important;
    }

    .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
        line-height: 38px !important;
        padding-left: 0.75rem !important;
        padding-right: 0 !important;
        font-size: 1rem !important;
    }

    .select2-container--bootstrap4 .select2-selection--single .select2-selection__placeholder {
        color: #6c757d !important;
    }

    .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
        top: 1px !important;
        right: 0.75rem !important;
    }

    .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow b {
        margin-top: 0 !important;
    }


    /* Archivo list styling */
    .ticket-file-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem;
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
        margin-bottom: 0.5rem;
        background-color: #f8f9fa;
    }

    .ticket-file-item:hover {
        background-color: #e9ecef;
    }

    .ticket-file-info {
        display: flex;
        align-items: center;
        flex: 1;
    }

    .ticket-file-icon {
        font-size: 1.25rem;
        margin-right: 0.75rem;
        min-width: 1.5rem;
        text-align: center;
    }

    .ticket-file-details {
        flex: 1;
    }

    .ticket-file-name {
        display: block;
        font-weight: 500;
        color: #212529;
        word-break: break-word;
    }

    .ticket-file-size {
        display: block;
        font-size: 0.875rem;
        color: #6c757d;
        margin-top: 0.25rem;
    }
</style>
@endpush

<div class="card card-primary card-outline">
    <!-- Card Header -->
    <div class="card-header with-border">
        <h3 class="card-title">Crear Nuevo Ticket</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" @click="showCreateForm = false" title="Volver a lista">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <!-- Card Body -->
    <form @submit.prevent="createTicket()" novalidate>
        <div class="card-body">

            <!-- Row 1: Company & Category -->
            <div class="row">
                <!-- Compañía -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="createCompany">Compañía <span class="text-danger">*</span></label>
                        <select id="createCompany"
                                class="form-control select2"
                                x-model="newTicket.company_id"
                                data-placeholder="Selecciona una compañía..."
                                required>
                            <option value="">Selecciona una compañía...</option>
                            <template x-for="company in companies" :key="company.id">
                                <option :value="company.id" x-text="company.name"></option>
                            </template>
                        </select>
                        <small class="form-text text-muted d-block mt-1">
                            Selecciona la compañía para la que deseas crear el ticket
                        </small>
                    </div>
                </div>

                <!-- Categoría -->
                <div class="col-md-6">
                    <template x-if="!newTicket.company_id">
                        <div class="form-group">
                            <label for="createCategory">Categoría <span class="text-danger">*</span></label>
                            <div class="form-control" style="background-color: #e9ecef; color: #6c757d; cursor: not-allowed;">
                                Selecciona una compañía primero
                            </div>
                            <small class="form-text text-muted d-block mt-1">
                                Debes seleccionar una compañía antes de elegir categoría
                            </small>
                        </div>
                    </template>

                    <template x-if="newTicket.company_id">
                        <div class="form-group">
                            <label for="createCategory">Categoría <span class="text-danger">*</span></label>
                            <select id="createCategory"
                                    class="form-control select2"
                                    x-model="newTicket.category_id"
                                    data-placeholder="Selecciona una categoría..."
                                    required>
                                <option></option>
                            </select>
                            <small class="form-text text-muted d-block mt-1">
                                Selecciona la categoría que mejor describe tu problema
                            </small>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Asunto -->
            <div class="form-group">
                <label for="createTitle">
                    Asunto <span class="text-danger">*</span>
                    <span class="float-right">
                        <small x-text="newTicket.title.length + '/255'" class="text-muted"></small>
                    </span>
                </label>
                <input type="text"
                       id="createTitle"
                       class="form-control"
                       x-model="newTicket.title"
                       placeholder="Describe brevemente el problema"
                       maxlength="255"
                       autocomplete="off"
                       required>
                <small class="form-text text-muted d-block mt-1">
                    Mínimo 5, máximo 255 caracteres
                </small>
                <template x-if="newTicket.title.trim().length > 0 && newTicket.title.trim().length < 5">
                    <small class="form-text text-danger d-block mt-1">
                        El asunto debe tener al menos 5 caracteres
                    </small>
                </template>
            </div>

            <!-- Descripción -->
            <div class="form-group">
                <label for="createDescription">
                    Descripción <span class="text-danger">*</span>
                    <span class="float-right">
                        <small x-text="newTicket.description.length + '/5000'" :class="newTicket.description.length < 10 ? 'text-danger' : 'text-muted'"></small>
                    </span>
                </label>
                <textarea id="createDescription"
                          class="form-control"
                          x-model="newTicket.description"
                          placeholder="Proporciona todos los detalles necesarios..."
                          rows="6"
                          maxlength="5000"
                          required
                          style="resize: vertical;"></textarea>
                <small class="form-text text-muted d-block mt-1">
                    Mínimo 10, máximo 5000 caracteres
                </small>
                <template x-if="newTicket.description.trim().length > 0 && newTicket.description.trim().length < 10">
                    <small class="form-text text-danger d-block mt-1">
                        La descripción debe tener al menos 10 caracteres (tienes <span x-text="newTicket.description.trim().length"></span>)
                    </small>
                </template>
            </div>

            <!-- Archivos Adjuntos -->
            <div class="form-group">
                <label for="createAttachment">
                    Archivos Adjuntos
                    <span class="badge" :class="newTicket.files.length >= 5 ? 'badge-danger' : 'badge-secondary'" x-text="newTicket.files.length + '/5'"></span>
                </label>
                <div class="custom-file">
                    <input type="file"
                           id="createAttachment"
                           class="custom-file-input"
                           @change="handleTicketFiles($event)"
                           multiple
                           :disabled="newTicket.files.length >= 5"
                           accept=".pdf,.txt,.log,.doc,.docx,.xls,.xlsx,.csv,.jpg,.jpeg,.png,.gif,.bmp,.webp,.svg,.mp4">
                    <label class="custom-file-label text-truncate" for="createAttachment">
                        <span x-show="newTicket.files.length === 0">Selecciona archivos (máx 5, 10MB c/u)</span>
                        <span x-show="newTicket.files.length > 0" x-text="newTicket.files.length + ' archivo(s) seleccionado(s)'"></span>
                    </label>
                </div>
                <small class="form-text text-muted d-block mt-1">
                    Máximo 5 archivos de 10MB cada uno. Formatos: PDF, TXT, LOG, DOC, DOCX, XLS, XLSX, CSV, JPG, JPEG, PNG, GIF, BMP, WEBP, SVG, MP4
                </small>
                <template x-if="newTicket.files.length >= 5">
                    <small class="form-text text-warning d-block mt-1">
                        Límite máximo de archivos alcanzado
                    </small>
                </template>
            </div>

            <!-- Lista de Archivos -->
            <template x-if="newTicket.files.length > 0">
                <div class="form-group">
                    <label class="d-block mb-2">Archivos Seleccionados</label>
                    <template x-for="(file, index) in newTicket.files" :key="index">
                        <div class="ticket-file-item">
                            <div class="ticket-file-info">
                                <div class="ticket-file-icon" :class="{
                                    'text-danger': file.name.endsWith('.pdf'),
                                    'text-info': file.name.endsWith('.doc') || file.name.endsWith('.docx'),
                                    'text-success': file.name.endsWith('.xlsx') || file.name.endsWith('.xls'),
                                    'text-warning': file.name.endsWith('.jpg') || file.name.endsWith('.jpeg') || file.name.endsWith('.png') || file.name.endsWith('.gif'),
                                    'text-primary': file.name.endsWith('.txt') || file.name.endsWith('.log'),
                                    'text-secondary': !file.name.endsWith('.pdf') && !file.name.endsWith('.doc') && !file.name.endsWith('.docx') && !file.name.endsWith('.xlsx') && !file.name.endsWith('.xls') && !file.name.endsWith('.jpg') && !file.name.endsWith('.jpeg') && !file.name.endsWith('.png') && !file.name.endsWith('.gif') && !file.name.endsWith('.txt') && !file.name.endsWith('.log')
                                }">
                                    <i class="fas fa-file"></i>
                                </div>
                                <div class="ticket-file-details">
                                    <span class="ticket-file-name" x-text="file.name"></span>
                                    <span class="ticket-file-size" x-text="formatFileSize(file.size)"></span>
                                </div>
                            </div>
                            <button type="button"
                                    class="btn btn-sm btn-danger ml-2"
                                    @click="removeTicketFile(index)"
                                    title="Eliminar archivo">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </template>
                </div>
            </template>

        </div>

        <!-- Card Footer -->
        <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
                <button type="button"
                        class="btn btn-secondary"
                        @click="showCreateForm = false">
                    <i class="fas fa-times mr-2"></i>Cancelar
                </button>
                <button type="submit"
                        class="btn btn-primary"
                        :disabled="isCreating || !newTicket.company_id || newTicket.title.trim().length < 5 || newTicket.description.trim().length < 10 || !newTicket.category_id"
                        x-cloak>
                    <template x-if="!isCreating">
                        <span><i class="fas fa-paper-plane mr-2"></i>Enviar Ticket</span>
                    </template>
                    <template x-if="isCreating">
                        <span>
                            <span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span>
                            Enviando...
                        </span>
                    </template>
                </button>
            </div>
        </div>
    </form>
</div>
