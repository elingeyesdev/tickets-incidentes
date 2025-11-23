@extends('adminlte::page')

@section('title', 'Ticket #2025-001 - Option B Design')

@section('content_header')
    <h1>Ticket Assignment - Option B</h1>
@stop

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-gradient-info">
                        <h3 class="card-title">
                            <i class="fas fa-cogs mr-2 text-info"></i> OPTION B: Agent Badge + Buttons
                        </h3>
                    </div>
                    <div class="card-body">
                        <p class="mb-4">Highlighted agent with status badge, avatar, and quick profile view button.</p>

                        <!-- ===== OPTION B DEMO ===== -->
                        <div class="card card-outline card-info p-4">
                            <div class="border-top pt-3">
                                <small class="text-muted d-block mb-3">
                                    <i class="fas fa-user-tie mr-1"></i> Current Agent
                                </small>
                                <!-- Agent Badge Box -->
                                <div class="mb-3 p-3 bg-light rounded" style="border-left: 4px solid #17a2b8;">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <img src="https://ui-avatars.com/api/?name=Juan+Support&background=007bff&color=fff&size=32"
                                                 alt="Juan"
                                                 class="img-circle mr-2"
                                                 style="width: 32px; height: 32px;">
                                            <div>
                                                <span class="font-weight-bold">Juan Support</span>
                                                <br>
                                                <small class="text-muted">juan@example.com</small>
                                            </div>
                                        </div>
                                        <span class="badge badge-success">Online</span>
                                    </div>
                                </div>
                                <!-- Action Buttons -->
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-info flex-grow-1">
                                        <i class="fas fa-exchange-alt mr-1"></i> Reassign
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary">
                                        <i class="fas fa-user-circle"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h5 class="mb-3">Features:</h5>
                        <ul>
                            <li><i class="fas fa-check text-success mr-2"></i> <strong>Avatar highlighted</strong> - More visual emphasis</li>
                            <li><i class="fas fa-check text-success mr-2"></i> <strong>Status visible</strong> - Badge shows if online</li>
                            <li><i class="fas fa-check text-success mr-2"></i> <strong>Quick profile access</strong> - Secondary button for profile</li>
                            <li><i class="fas fa-times text-danger mr-2"></i> <strong>Two buttons confusing</strong> - Not always clear which to click</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop
