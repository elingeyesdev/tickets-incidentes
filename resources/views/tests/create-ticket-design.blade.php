@extends('adminlte::page')

@section('title', 'Create Ticket - Design Demo')

@section('content_header')
    <h1>Crear Ticket - Diseño</h1>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Navigation Buttons -->
    <div class="row mb-3">
        <div class="col-12">
            <a href="{{ route('tests.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i> Volver a Tests
            </a>
        </div>
    </div>

    <!-- Create Ticket Form -->
    <div class="row">
        <div class="col-12">
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

                /* File list styling */
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
                        <a href="{{ route('tests.index') }}" class="btn btn-tool" title="Volver a lista">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>

                <!-- Card Body -->
                <form>
                    <div class="card-body">

                        <!-- Row 1: Company & Category -->
                        <div class="row">
                            <!-- Compañía -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="createCompany">Compañía <span class="text-danger">*</span></label>
                                    <select id="createCompany" class="form-control" required>
                                        <option value="">Selecciona una compañía...</option>
                                        <option value="1">Acme Corporation</option>
                                        <option value="2">Tech Solutions Inc.</option>
                                        <option value="3">Global Enterprises</option>
                                    </select>
                                    <small class="form-text text-muted d-block mt-1">
                                        Selecciona la compañía para la que deseas crear el ticket
                                    </small>
                                </div>
                            </div>

                            <!-- Categoría -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="createCategory">Categoría <span class="text-danger">*</span></label>
                                    <select id="createCategory" class="form-control" required>
                                        <option value="">Selecciona una categoría...</option>
                                        <option value="1">Technical Support</option>
                                        <option value="2">Billing</option>
                                        <option value="3">Feature Request</option>
                                        <option value="4">General Inquiry</option>
                                    </select>
                                    <small class="form-text text-muted d-block mt-1">
                                        Selecciona la categoría que mejor describe tu problema
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Asunto -->
                        <div class="form-group">
                            <label for="createTitle">
                                Asunto <span class="text-danger">*</span>
                                <span class="float-right">
                                    <small class="text-muted">0/255</small>
                                </span>
                            </label>
                            <input type="text"
                                   id="createTitle"
                                   class="form-control"
                                   placeholder="Describe brevemente el problema"
                                   maxlength="255"
                                   autocomplete="off"
                                   required>
                            <small class="form-text text-muted d-block mt-1">
                                Mínimo 5, máximo 255 caracteres
                            </small>
                        </div>

                        <!-- Descripción -->
                        <div class="form-group">
                            <label for="createDescription">
                                Descripción <span class="text-danger">*</span>
                                <span class="float-right">
                                    <small class="text-muted">0/5000</small>
                                </span>
                            </label>
                            <textarea id="createDescription"
                                      class="form-control"
                                      placeholder="Proporciona todos los detalles necesarios..."
                                      rows="6"
                                      maxlength="5000"
                                      required
                                      style="resize: vertical;"></textarea>
                            <small class="form-text text-muted d-block mt-1">
                                Mínimo 10, máximo 5000 caracteres
                            </small>
                        </div>

                        <!-- Archivos Adjuntos -->
                        <div class="form-group">
                            <label for="createAttachment">
                                Archivos Adjuntos
                                <span class="badge badge-secondary">0/5</span>
                            </label>
                            <div class="custom-file">
                                <input type="file"
                                       id="createAttachment"
                                       class="custom-file-input"
                                       multiple
                                       accept=".pdf,.txt,.log,.doc,.docx,.xls,.xlsx,.csv,.jpg,.jpeg,.png,.gif,.bmp,.webp,.svg,.mp4">
                                <label class="custom-file-label text-truncate" for="createAttachment">
                                    Selecciona archivos (máx 5, 10MB c/u)
                                </label>
                            </div>
                            <small class="form-text text-muted d-block mt-1">
                                Máximo 5 archivos de 10MB cada uno. Formatos: PDF, TXT, LOG, DOC, DOCX, XLS, XLSX, CSV, JPG, JPEG, PNG, GIF, BMP, WEBP, SVG, MP4
                            </small>
                        </div>

                    </div>

                    <!-- Card Footer -->
                    <div class="card-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('tests.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times mr-2"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary" disabled>
                                <i class="fas fa-paper-plane mr-2"></i>Enviar Ticket
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
