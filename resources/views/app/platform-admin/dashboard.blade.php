@extends('layouts.authenticated')

@section('title', 'Platform Admin Dashboard')

@section('content_header', 'Platform Admin Dashboard')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Platform Admin Dashboard</li>
@endsection

@section('content')
<div class="row">
    <!-- Total Users -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $stats['total_users'] ?? 0 }}</h3>
                <p>Total Users</p>
            </div>
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
            <a href="/app/admin/users" class="small-box-footer">
                More info <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Total Companies -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $stats['total_companies'] ?? 0 }}</h3>
                <p>Total Companies</p>
            </div>
            <div class="icon">
                <i class="fas fa-building"></i>
            </div>
            <a href="/app/admin/companies" class="small-box-footer">
                More info <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Total Tickets -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $stats['total_tickets'] ?? 0 }}</h3>
                <p>Total Tickets</p>
            </div>
            <div class="icon">
                <i class="fas fa-ticket-alt"></i>
            </div>
            <a href="/app/admin/tickets" class="small-box-footer">
                More info <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Pending Requests -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ $stats['pending_requests'] ?? 0 }}</h3>
                <p>Pending Company Requests</p>
            </div>
            <div class="icon">
                <i class="fas fa-file-invoice"></i>
            </div>
            <a href="/app/admin/company-requests" class="small-box-footer">
                More info <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Company Requests -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Company Requests</h3>
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
                            <th>Company</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Acme Corp</td>
                            <td>john@acme.com</td>
                            <td><span class="badge badge-warning">Pending</span></td>
                            <td><a href="#" class="btn btn-sm btn-primary">Review</a></td>
                        </tr>
                        <tr>
                            <td>TechStart Inc</td>
                            <td>mary@techstart.io</td>
                            <td><span class="badge badge-warning">Pending</span></td>
                            <td><a href="#" class="btn btn-sm btn-primary">Review</a></td>
                        </tr>
                        <tr>
                            <td>Global Solutions</td>
                            <td>admin@global.com</td>
                            <td><span class="badge badge-warning">Pending</span></td>
                            <td><a href="#" class="btn btn-sm btn-primary">Review</a></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- System Health -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">System Health Status</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-box bg-success">
                            <span class="info-box-icon"><i class="fas fa-server"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">API Status</span>
                                <span class="info-box-number">Online</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-box bg-success">
                            <span class="info-box-icon"><i class="fas fa-database"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Database</span>
                                <span class="info-box-number">Connected</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-box bg-success">
                            <span class="info-box-icon"><i class="fas fa-envelope"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Email Service</span>
                                <span class="info-box-number">Active</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-box bg-success">
                            <span class="info-box-icon"><i class="fas fa-cloud"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Storage</span>
                                <span class="info-box-number">65% Used</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Activity Log -->
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Activity</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <ul class="timeline">
                    <li class="time-label">
                        <span class="bg-info">Today</span>
                    </li>
                    <li>
                        <i class="fas fa-building bg-blue"></i>
                        <div class="timeline-item">
                            <span class="time"><i class="fas fa-clock"></i> 10 mins ago</span>
                            <h3 class="timeline-header">New company registered</h3>
                            <div class="timeline-body">
                                TechStart Inc completed registration and is pending approval.
                            </div>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-user bg-green"></i>
                        <div class="timeline-item">
                            <span class="time"><i class="fas fa-clock"></i> 2 hours ago</span>
                            <h3 class="timeline-header">New user registered</h3>
                            <div class="timeline-body">
                                John Doe (john@example.com) created an account.
                            </div>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-check bg-success"></i>
                        <div class="timeline-item">
                            <span class="time"><i class="fas fa-clock"></i> 5 hours ago</span>
                            <h3 class="timeline-header">Company approved</h3>
                            <div class="timeline-body">
                                Acme Corp was approved and activated.
                            </div>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-clock bg-gray"></i>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
