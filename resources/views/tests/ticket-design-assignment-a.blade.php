@extends('layouts.authenticated')

@section('title', 'Ticket #2025-001 - Design Option A')

@section('content_header', 'Assignment Design - Option A')

@section('breadcrumbs')
    <li class="breadcrumb-item"><i class="fas fa-ticket-alt mr-2"></i>Design Tests</li>
    <li class="breadcrumb-item active">Option A: Agent Info + Button</li>
@endsection

@section('content')

<div class="row">
    <!-- LEFT COLUMN: Sidebar -->
    <div class="col-md-3">
        <!-- Back to Index -->
        <div class="mb-3">
            <a href="{{ route('tests.assignment-index') }}" class="btn btn-secondary btn-block">
                <i class="fas fa-arrow-left mr-2"></i> Back to All Options
            </a>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-layer-group mr-2"></i>Design Variants
                </h3>
            </div>
            <div class="card-body p-0">
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item">
                        <a href="{{ route('tests.assignment-variant', 1) }}" class="nav-link active">
                            <i class="fas fa-check-circle mr-2 text-success"></i> Option A
                            <small class="d-block text-muted ml-4">Agent Info + Button</small>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('tests.assignment-variant', 2) }}" class="nav-link">
                            <i class="fas fa-cogs mr-2 text-info"></i> Option B
                            <small class="d-block text-muted ml-4">Agent Badge + Buttons</small>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('tests.assignment-variant', 3) }}" class="nav-link">
                            <i class="fas fa-star mr-2 text-warning"></i> Option C
                            <small class="d-block text-muted ml-4">Agent Card (Recommended)</small>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Features Card -->
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Option A Features</h3>
            </div>
            <div class="card-body p-3" style="font-size: 0.9rem;">
                <ul class="list-unstyled">
                    <li class="mb-2"><i class="fas fa-check text-success mr-2"></i> Shows current agent</li>
                    <li class="mb-2"><i class="fas fa-check text-success mr-2"></i> Avatar visual</li>
                    <li class="mb-2"><i class="fas fa-check text-success mr-2"></i> Name & email</li>
                    <li class="text-danger"><i class="fas fa-times mr-2"></i> Takes more space</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- RIGHT COLUMN: Main Content -->
    <div class="col-md-9">
        <!-- Ticket Header -->
        <div class="card card-primary card-outline mb-3">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-ticket-alt mr-2"></i> #2025-001
                </h3>
                <span class="badge badge-warning p-2 float-right">PENDING</span>
            </div>
            <div class="card-body">
                <h5>Asunto: Error en la facturación del mes de Noviembre</h5>
                <h6>De: Kylie De la quintana (kylie@example.com)
                    <span class="float-right text-muted">15 Nov, 2025 11:03 PM</span>
                </h6>
            </div>
        </div>

        <!-- Demo: Assignment Section (Option A) -->
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-cogs mr-2"></i> Assignment Section
                </h3>
            </div>
            <div class="card-footer">
                <!-- Cambiar Estado (for context) -->
                <div class="mb-3">
                    <small class="text-muted d-block mb-2">
                        <i class="fas fa-exchange-alt mr-1"></i> Change Status
                    </small>
                    <div class="d-flex flex-column flex-sm-row flex-wrap gap-2">
                        <button type="button" class="btn btn-success">
                            <i class="fas fa-check-circle mr-1"></i> Resolve
                        </button>
                        <button type="button" class="btn btn-warning">
                            <i class="fas fa-redo mr-1"></i> Reopen
                        </button>
                        <button type="button" class="btn btn-secondary">
                            <i class="fas fa-times-circle mr-1"></i> Close
                        </button>
                    </div>
                </div>

                <!-- ===== OPTION A: Agent Info + Button ===== -->
                <div class="border-top pt-3">
                    <small class="text-muted d-block mb-3">
                        <i class="fas fa-user-tie mr-1"></i> Assigned Agent
                    </small>
                    <!-- Agent Info Row -->
                    <div class="d-flex align-items-center mb-3">
                        <img src="https://ui-avatars.com/api/?name=Juan+Support&background=007bff&color=fff&size=40"
                             alt="Juan"
                             class="img-circle mr-2"
                             style="width: 40px; height: 40px;">
                        <div>
                            <div class="font-weight-bold">Juan Support</div>
                            <small class="text-muted">juan@example.com</small>
                        </div>
                    </div>
                    <!-- Reassign Button -->
                    <button type="button" class="btn btn-info btn-block">
                        <i class="fas fa-exchange-alt mr-1"></i> Reassign
                    </button>
                </div>
            </div>
        </div>

        <!-- Description -->
        <div class="card mt-4">
            <div class="card-header">
                <h3 class="card-title">Description</h3>
            </div>
            <div class="card-body">
                <p><strong>What's good:</strong></p>
                <ul>
                    <li>Simple and direct layout</li>
                    <li>Shows agent information clearly with avatar</li>
                    <li>Integrates naturally in the Actions card</li>
                    <li>Good for quick agent reference</li>
                </ul>
                <p><strong>When to use:</strong></p>
                <ul>
                    <li>When space is limited</li>
                    <li>When you want minimal visual hierarchy</li>
                    <li>Best for compact layouts</li>
                </ul>
            </div>
        </div>

    </div>
</div>

@endsection

@section('js')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endsection
