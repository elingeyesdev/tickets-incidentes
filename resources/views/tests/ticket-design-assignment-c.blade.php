@extends('layouts.authenticated')

@section('title', 'Ticket #2025-001 - Design Option C')

@section('content_header', 'Assignment Design - Option C (Recommended)')

@section('breadcrumbs')
    <li class="breadcrumb-item"><i class="fas fa-ticket-alt mr-2"></i>Design Tests</li>
    <li class="breadcrumb-item active">Option C: Agent Card</li>
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
                        <a href="{{ route('tests.assignment-variant', 1) }}" class="nav-link">
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
                        <a href="{{ route('tests.assignment-variant', 3) }}" class="nav-link active">
                            <i class="fas fa-star mr-2 text-warning"></i> Option C
                            <small class="d-block text-muted ml-4">Agent Card (Recommended)</small>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Features Card -->
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-crown mr-2 text-warning"></i> Option C Features
                </h3>
            </div>
            <div class="card-body p-3" style="font-size: 0.9rem;">
                <ul class="list-unstyled">
                    <li class="mb-2"><i class="fas fa-check text-success mr-2"></i> Consistent with AdminLTE</li>
                    <li class="mb-2"><i class="fas fa-check text-success mr-2"></i> Visual and clear</li>
                    <li class="mb-2"><i class="fas fa-check text-success mr-2"></i> Professional design</li>
                    <li class="mb-2"><i class="fas fa-check text-success mr-2"></i> Symmetric layout</li>
                    <li class="mb-2"><i class="fas fa-check text-success mr-2"></i> Easy modal integration</li>
                    <li class="text-warning"><i class="fas fa-info-circle mr-2"></i> Takes more space</li>
                </ul>
                <div class="alert alert-success mt-3 mb-0" style="font-size: 0.85rem;">
                    <i class="fas fa-thumbs-up mr-1"></i> Most professional approach
                </div>
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

        <!-- Content Row: Actions + Agent Card -->
        <div class="row">
            <!-- Left: Action Card -->
            <div class="col-md-6">
                <div class="card card-outline card-success">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-cogs mr-2"></i> Actions
                        </h3>
                    </div>
                    <div class="card-footer">
                        <!-- Cambiar Estado -->
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
                    </div>
                </div>

                <!-- Ticket Info Card (for comparison) -->
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Ticket Info</h3>
                    </div>
                    <div class="card-body p-2" style="font-size: 0.9rem;">
                        <div class="mb-2">
                            <strong><i class="fas fa-code mr-1"></i> Code</strong>
                            <p class="text-muted mb-0">TKT-2025-00001</p>
                        </div>
                        <hr class="my-1">
                        <div class="mb-2">
                            <strong><i class="fas fa-hourglass-half mr-1"></i> Status</strong>
                            <p class="mb-0"><span class="badge badge-warning">PENDING</span></p>
                        </div>
                        <hr class="my-1">
                        <div class="mb-2">
                            <strong><i class="fas fa-building mr-1"></i> Company</strong>
                            <p class="text-muted mb-0">Acme Corporation</p>
                        </div>
                        <hr class="my-1">
                        <div class="mb-0">
                            <strong><i class="fas fa-comments mr-1"></i> Responses</strong>
                            <p class="text-muted mb-0">3</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Agent Card (Option C) -->
            <div class="col-md-6">
                <!-- ===== OPTION C: Separate Agent Card ===== -->
                <div class="card card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user-tie mr-1"></i> Assigned Agent
                        </h3>
                    </div>
                    <div class="card-body text-center">
                        <!-- Avatar -->
                        <img src="https://ui-avatars.com/api/?name=Juan+Support&background=007bff&color=fff&size=80"
                             alt="Juan"
                             class="img-circle elevation-2 mb-3"
                             style="width: 80px; height: 80px;">
                        <!-- Name -->
                        <h5 class="mb-1 font-weight-bold">Juan Support</h5>
                        <!-- Status Badge -->
                        <p class="text-muted mb-3" style="font-size: 0.85rem;">
                            <span class="badge badge-success">
                                <i class="fas fa-circle mr-1"></i> Online
                            </span>
                        </p>
                        <!-- Email -->
                        <small class="text-muted d-block mb-3">juan@example.com</small>
                        <!-- Reassign Button -->
                        <button type="button" class="btn btn-info btn-block">
                            <i class="fas fa-exchange-alt mr-1"></i> Reassign Agent
                        </button>
                    </div>
                </div>

                <!-- Requester Card (for symmetry comparison) -->
                <div class="card card-outline card-warning">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user mr-1"></i> Requester
                        </h3>
                    </div>
                    <div class="card-body text-center">
                        <img src="https://ui-avatars.com/api/?name=Kylie+De+la+quintana&background=ffc107&color=000&size=80"
                             alt="Kylie"
                             class="img-circle elevation-2 mb-3"
                             style="width: 80px; height: 80px;">
                        <h5 class="mb-1 font-weight-bold">Kylie De la quintana</h5>
                        <p class="text-muted mb-3" style="font-size: 0.85rem;">
                            <span class="badge badge-primary">VIP Client</span>
                        </p>
                        <small class="text-muted d-block">kylie@example.com</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Description -->
        <div class="card mt-4">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-crown mr-2 text-warning"></i> Why Option C is Recommended
                </h3>
            </div>
            <div class="card-body">
                <p><strong>Advantages:</strong></p>
                <ul>
                    <li><strong>AdminLTE v3 Standard:</strong> Matches the design pattern of "Ticket Info" card</li>
                    <li><strong>Visual Clarity:</strong> Clear separation of concerns (Agent card vs. Action card)</li>
                    <li><strong>Professional:</strong> Centered avatar, status badge, and action button look polished</li>
                    <li><strong>Symmetric Layout:</strong> Agent Card mirrors Requester Card perfectly</li>
                    <li><strong>Scalability:</strong> Easy to add more agent info (response time, tickets handled, etc.)</li>
                    <li><strong>Modal Integration:</strong> Perfect foundation for reassignment modal/form</li>
                </ul>

                <p><strong>Layout Structure:</strong></p>
                <ul>
                    <li>Left column (50%): Actions + Ticket Info</li>
                    <li>Right column (50%): Agent Card + Requester Card</li>
                    <li>Creates natural visual balance and hierarchy</li>
                </ul>

                <p><strong>When to use this design:</strong></p>
                <ul>
                    <li>✓ Professional support systems</li>
                    <li>✓ When agent information is equally important as actions</li>
                    <li>✓ When you need to show agent status/availability</li>
                    <li>✓ For reassignment workflows with modal forms</li>
                </ul>
            </div>
        </div>

    </div>
</div>

@endsection

@section('js')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endsection
