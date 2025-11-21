<!-- Card: Formulario de Nueva Respuesta -->
<div class="card card-primary" x-show="ticket.status !== 'closed'" x-cloak>
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-reply mr-2"></i>Agregar Respuesta
        </h3>
    </div>
    <form @submit.prevent="submitResponse()">
        <div class="card-body">
            <div class="form-group">
                <label for="response-content">
                    <i class="fas fa-comment-dots mr-2"></i>Tu respuesta
                </label>
                <textarea class="form-control"
                          id="response-content"
                          rows="5"
                          x-model="newResponse.content"
                          placeholder="Escribe tu respuesta aquí..."
                          required></textarea>
                <small class="form-text text-muted">
                    Máximo 5000 caracteres
                </small>
            </div>

            <div class="form-group">
                <label>
                    <i class="fas fa-paperclip mr-2"></i>Adjuntar archivos (Opcional)
                </label>
                <div class="custom-file">
                    <input type="file"
                           class="custom-file-input"
                           id="response-files"
                           @change="handleFileSelection"
                           multiple>
                    <label class="custom-file-label" for="response-files">
                        Seleccionar archivos...
                    </label>
                </div>
                <small class="form-text text-muted">
                    Máximo 5 archivos, 10 MB cada uno.
                    Tipos permitidos: PDF, TXT, DOC, DOCX, XLS, XLSX, CSV, JPG, PNG, GIF, MP4
                </small>

                <!-- Lista de archivos seleccionados -->
                <template x-if="newResponse.files.length > 0">
                    <ul class="list-unstyled mt-2">
                        <template x-for="(file, index) in newResponse.files" :key="index">
                            <li class="text-sm">
                                <i class="fas fa-file mr-2"></i>
                                <span x-text="file.name"></span>
                                (<span x-text="formatFileSize(file.size)"></span>)
                                <a href="#"
                                   class="text-danger ml-2"
                                   @click.prevent="removeFile(index)">
                                    <i class="fas fa-times"></i>
                                </a>
                            </li>
                        </template>
                    </ul>
                </template>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit"
                    class="btn btn-primary"
                    :disabled="isSubmitting || !newResponse.content.trim()">
                <template x-if="isSubmitting">
                    <span>
                        <i class="fas fa-spinner fa-spin mr-2"></i>Enviando...
                    </span>
                </template>
                <template x-if="!isSubmitting">
                    <span>
                        <i class="fas fa-paper-plane mr-2"></i>Enviar Respuesta
                    </span>
                </template>
            </button>
            <button type="button"
                    class="btn btn-default ml-2"
                    @click="resetResponseForm()">
                <i class="fas fa-times mr-2"></i>Cancelar
            </button>
        </div>
    </form>
</div>