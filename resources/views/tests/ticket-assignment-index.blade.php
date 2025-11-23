@extends('layouts.authenticated')

@section('title', 'Ticket Assignment Design Options')

@section('content_header', 'Design Comparison: Assignment Section')

@section('breadcrumbs')
    <li class="breadcrumb-item"><i class="fas fa-ticket-alt mr-2"></i>Design Tests</li>
    <li class="breadcrumb-item active">Assignment Options</li>
@endsection

@section('content')

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-layer-group mr-2"></i> Choose an Assignment Design
                    </h3>
                    <p class="text-muted mt-2">Click on any option below to see the detailed design</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <!-- Option A -->
        <div class="col-md-4">
            <div class="card card-hover h-100" style="cursor: pointer; transition: all 0.3s ease;"
                 onclick="window.location.href='{{ route('tests.assignment-variant', 1) }}'">
                <div class="card-header bg-gradient-primary">
                    <h3 class="card-title text-white">
                        <i class="fas fa-check-circle mr-2"></i> Option A
                    </h3>
                </div>
                <div class="card-body">
                    <h6 class="mb-3 font-weight-bold">Agent Info + Button</h6>
                    <p class="text-muted small mb-3">
                        Simple and direct layout with agent avatar, name, email, and a reassign button.
                    </p>

                    <div class="card bg-light p-3 mb-3">
                        <div class="d-flex align-items-center">
                            <img src="https://ui-avatars.com/api/?name=Juan+Support&background=007bff&color=fff&size=32"
                                 alt="Juan" class="img-circle mr-2" style="width: 32px; height: 32px;">
                            <div style="font-size: 0.85rem;">
                                <div class="font-weight-bold">Juan Support</div>
                                <small class="text-muted">juan@example.com</small>
                            </div>
                        </div>
                    </div>

                    <ul class="list-unstyled small">
                        <li class="mb-1"><i class="fas fa-check text-success mr-2"></i> Compact layout</li>
                        <li class="mb-1"><i class="fas fa-check text-success mr-2"></i> Shows agent info</li>
                        <li class="text-danger"><i class="fas fa-times mr-2"></i> Takes less space</li>
                    </ul>
                </div>
                <div class="card-footer bg-white">
                    <button class="btn btn-primary btn-block btn-sm" onclick="event.stopPropagation();">
                        <i class="fas fa-eye mr-1"></i> View Design
                    </button>
                </div>
            </div>
        </div>

        <!-- Option B -->
        <div class="col-md-4">
            <div class="card card-hover h-100" style="cursor: pointer; transition: all 0.3s ease;"
                 onclick="window.location.href='{{ route('tests.assignment-variant', 2) }}'">
                <div class="card-header bg-gradient-info">
                    <h3 class="card-title text-white">
                        <i class="fas fa-cogs mr-2"></i> Option B
                    </h3>
                </div>
                <div class="card-body">
                    <h6 class="mb-3 font-weight-bold">Agent Badge + Buttons</h6>
                    <p class="text-muted small mb-3">
                        Highlighted agent with status badge, avatar, and quick profile view button.
                    </p>

                    <div class="card bg-light p-3 mb-3" style="border-left: 4px solid #17a2b8;">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <img src="https://ui-avatars.com/api/?name=Juan+Support&background=007bff&color=fff&size=28"
                                     alt="Juan" class="img-circle mr-2" style="width: 28px; height: 28px;">
                                <small class="font-weight-bold">Juan</small>
                            </div>
                            <span class="badge badge-success" style="font-size: 0.7rem;">Online</span>
                        </div>
                    </div>

                    <ul class="list-unstyled small">
                        <li class="mb-1"><i class="fas fa-check text-success mr-2"></i> Status visible</li>
                        <li class="mb-1"><i class="fas fa-check text-success mr-2"></i> Avatar prominent</li>
                        <li class="text-warning"><i class="fas fa-exclamation mr-2"></i> 2 buttons may confuse</li>
                    </ul>
                </div>
                <div class="card-footer bg-white">
                    <button class="btn btn-info btn-block btn-sm" onclick="event.stopPropagation();">
                        <i class="fas fa-eye mr-1"></i> View Design
                    </button>
                </div>
            </div>
        </div>

        <!-- Option C -->
        <div class="col-md-4">
            <div class="card card-hover h-100" style="cursor: pointer; transition: all 0.3s ease; border: 2px solid #ffc107;"
                 onclick="window.location.href='{{ route('tests.assignment-variant', 3) }}'">
                <div class="card-header bg-gradient-success">
                    <h3 class="card-title text-white">
                        <i class="fas fa-crown mr-2"></i> Option C
                    </h3>
                    <span class="badge badge-warning float-right" style="margin-top: 5px;">RECOMMENDED</span>
                </div>
                <div class="card-body">
                    <h6 class="mb-3 font-weight-bold">Separate Agent Card</h6>
                    <p class="text-muted small mb-3">
                        Professional card design consistent with AdminLTE. Perfect for modal integration.
                    </p>

                    <div class="card bg-light p-3 mb-3 text-center">
                        <img src="https://ui-avatars.com/api/?name=Juan+Support&background=007bff&color=fff&size=40"
                             alt="Juan" class="img-circle mb-2" style="width: 40px; height: 40px;">
                        <div style="font-size: 0.85rem;">
                            <div class="font-weight-bold">Juan Support</div>
                            <span class="badge badge-success badge-sm">Online</span>
                        </div>
                    </div>

                    <ul class="list-unstyled small">
                        <li class="mb-1"><i class="fas fa-check text-success mr-2"></i> AdminLTE standard</li>
                        <li class="mb-1"><i class="fas fa-check text-success mr-2"></i> Professional</li>
                        <li class="mb-1"><i class="fas fa-check text-success mr-2"></i> Scalable design</li>
                    </ul>
                </div>
                <div class="card-footer bg-white">
                    <button class="btn btn-success btn-block btn-sm" onclick="event.stopPropagation();">
                        <i class="fas fa-eye mr-1"></i> View Design
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection

@section('css')
<style>
    .card-hover {
        border: 1px solid #e9ecef;
    }
    .card-hover:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        transform: translateY(-2px);
    }
</style>
@endsection
