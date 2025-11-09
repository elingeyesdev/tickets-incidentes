@extends('layouts.authenticated')

@section('title', 'Company Admin Dashboard')

@section('content_header', 'Company Admin Dashboard')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Company Admin Dashboard</li>
@endsection

@section('content')
<div class="row">
    <!-- Total Agents -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $stats['total_agents'] ?? 0 }}</h3>
                <p>Total Agents</p>
            </div>
            <div class="icon">
                <i class="fas fa-user-tie"></i>
            </div>
            <a href="/app/company/agents" class="small-box-footer">
                More info <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Online Agents -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $stats['online_agents'] ?? 0 }}</h3>
                <p>Online Agents</p>
            </div>
            <div class="icon">
                <i class="fas fa-circle text-success"></i>
            </div>
            <a href="/app/company/agents" class="small-box-footer">
                More info <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Open Tickets -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $stats['open_tickets'] ?? 0 }}</h3>
                <p>Open Tickets</p>
            </div>
            <div class="icon">
                <i class="fas fa-ticket-alt"></i>
            </div>
            <a href="/app/company/tickets" class="small-box-footer">
                More info <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Resolved Today -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $stats['resolved_today'] ?? 0 }}</h3>
                <p>Resolved Today</p>
            </div>
            <div class="icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <a href="/app/company/tickets" class="small-box-footer">
                More info <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
</div>

<div class="row">
    <!-- Company Information -->
    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-body box-profile">
                <div class="text-center">
                    <i class="fas fa-building fa-5x text-primary mb-3"></i>
                </div>
                <h3 class="profile-username text-center">Your Company</h3>
                <p class="text-muted text-center">Enterprise Plan</p>
                <ul class="list-group list-group-unbordered mb-3">
                    <li class="list-group-item">
                        <b>Total Members</b> <a class="float-right">15</a>
                    </li>
                    <li class="list-group-item">
                        <b>Agents</b> <a class="float-right">{{ $stats['total_agents'] ?? 0 }}</a>
                    </li>
                    <li class="list-group-item">
                        <b>Plan Status</b> <a class="float-right"><span class="badge badge-success">Active</span></a>
                    </li>
                </ul>
                <a href="/app/company/settings" class="btn btn-primary btn-block"><b>Manage Company</b></a>
            </div>
        </div>
    </div>

    <!-- Performance Metrics -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Performance Metrics</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-box bg-gradient-info">
                            <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Avg Response Time</span>
                                <span class="info-box-number">{{ $stats['avg_response_time'] ?? 'N/A' }}</span>
                                <div class="progress">
                                    <div class="progress-bar" style="width: 70%"></div>
                                </div>
                                <span class="progress-description">
                                    30% better than last month
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-box bg-gradient-success">
                            <span class="info-box-icon"><i class="fas fa-smile"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Customer Satisfaction</span>
                                <span class="info-box-number">92%</span>
                                <div class="progress">
                                    <div class="progress-bar" style="width: 92%"></div>
                                </div>
                                <span class="progress-description">
                                    5% increase from last month
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="card card-outline card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Quick Stats</h3>
                            </div>
                            <div class="card-body p-0">
                                <table class="table">
                                    <tbody>
                                        <tr>
                                            <td>Total Tickets (All Time)</td>
                                            <td><span class="badge badge-info">234</span></td>
                                        </tr>
                                        <tr>
                                            <td>Average Resolution Time</td>
                                            <td><span class="badge badge-success">4.5 hours</span></td>
                                        </tr>
                                        <tr>
                                            <td>First Response Time</td>
                                            <td><span class="badge badge-warning">45 minutes</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Tickets -->
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Tickets</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Subject</th>
                            <th>Customer</th>
                            <th>Agent</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>#T-1001</td>
                            <td>Login issue with mobile app</td>
                            <td>John Doe</td>
                            <td>Sarah Agent</td>
                            <td><span class="badge badge-warning">Open</span></td>
                            <td><span class="badge badge-danger">High</span></td>
                            <td>2 hours ago</td>
                        </tr>
                        <tr>
                            <td>#T-1002</td>
                            <td>Payment processing error</td>
                            <td>Jane Smith</td>
                            <td>Mike Agent</td>
                            <td><span class="badge badge-info">In Progress</span></td>
                            <td><span class="badge badge-warning">Medium</span></td>
                            <td>4 hours ago</td>
                        </tr>
                        <tr>
                            <td>#T-1003</td>
                            <td>Feature request: Dark mode</td>
                            <td>Bob Wilson</td>
                            <td>Unassigned</td>
                            <td><span class="badge badge-secondary">New</span></td>
                            <td><span class="badge badge-success">Low</span></td>
                            <td>1 day ago</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                <a href="/app/company/tickets" class="btn btn-primary">View All Tickets</a>
            </div>
        </div>
    </div>
</div>
@endsection
