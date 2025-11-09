@extends('layouts.authenticated')

@section('title', 'User Dashboard')

@section('content_header', 'My Dashboard')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')
<div class="row">
    <!-- Open Tickets -->
    <div class="col-lg-4 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $stats['open_tickets'] ?? 0 }}</h3>
                <p>Open Tickets</p>
            </div>
            <div class="icon">
                <i class="fas fa-folder-open"></i>
            </div>
            <a href="/app/user/tickets?status=open" class="small-box-footer">
                View Tickets <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- In Progress -->
    <div class="col-lg-4 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $stats['in_progress_tickets'] ?? 0 }}</h3>
                <p>In Progress</p>
            </div>
            <div class="icon">
                <i class="fas fa-tasks"></i>
            </div>
            <a href="/app/user/tickets?status=in_progress" class="small-box-footer">
                View Tickets <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Closed Tickets -->
    <div class="col-lg-4 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $stats['closed_tickets'] ?? 0 }}</h3>
                <p>Closed Tickets</p>
            </div>
            <div class="icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <a href="/app/user/tickets?status=closed" class="small-box-footer">
                View History <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
</div>

<div class="row">
    <!-- My Tickets -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">My Recent Tickets</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-sm btn-success" onclick="window.location.href='/app/user/tickets/create'">
                        <i class="fas fa-plus mr-1"></i> New Ticket
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Last Updated</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>#T-5001</td>
                            <td>Cannot reset password</td>
                            <td><span class="badge badge-info">In Progress</span></td>
                            <td><span class="badge badge-danger">High</span></td>
                            <td>1 hour ago</td>
                            <td><a href="#" class="btn btn-sm btn-primary">View</a></td>
                        </tr>
                        <tr>
                            <td>#T-5002</td>
                            <td>Question about billing</td>
                            <td><span class="badge badge-warning">Open</span></td>
                            <td><span class="badge badge-warning">Medium</span></td>
                            <td>2 days ago</td>
                            <td><a href="#" class="btn btn-sm btn-primary">View</a></td>
                        </tr>
                        <tr>
                            <td>#T-4999</td>
                            <td>Feature request</td>
                            <td><span class="badge badge-success">Closed</span></td>
                            <td><span class="badge badge-success">Low</span></td>
                            <td>1 week ago</td>
                            <td><a href="#" class="btn btn-sm btn-secondary">View</a></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                <a href="/app/user/tickets" class="btn btn-primary">View All My Tickets</a>
            </div>
        </div>

        <!-- Recent Activity -->
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
                <div class="timeline">
                    <div class="time-label">
                        <span class="bg-info">Today</span>
                    </div>
                    <div>
                        <i class="fas fa-comment bg-blue"></i>
                        <div class="timeline-item">
                            <span class="time"><i class="fas fa-clock"></i> 1 hour ago</span>
                            <h3 class="timeline-header">Agent Sarah replied to your ticket #T-5001</h3>
                            <div class="timeline-body">
                                "I've sent you a password reset link to your email. Please check your inbox and spam folder."
                            </div>
                        </div>
                    </div>
                    <div>
                        <i class="fas fa-ticket-alt bg-green"></i>
                        <div class="timeline-item">
                            <span class="time"><i class="fas fa-clock"></i> 2 days ago</span>
                            <h3 class="timeline-header">You created ticket #T-5002</h3>
                            <div class="timeline-body">
                                Question about billing cycle and payment methods.
                            </div>
                        </div>
                    </div>
                    <div>
                        <i class="fas fa-check bg-success"></i>
                        <div class="timeline-item">
                            <span class="time"><i class="fas fa-clock"></i> 1 week ago</span>
                            <h3 class="timeline-header">Ticket #T-4999 was resolved</h3>
                            <div class="timeline-body">
                                Your feature request has been added to our roadmap. Thank you for your suggestion!
                            </div>
                        </div>
                    </div>
                    <div>
                        <i class="fas fa-clock bg-gray"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions & Help -->
    <div class="col-md-4">
        <!-- Quick Actions -->
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Quick Actions</h3>
            </div>
            <div class="card-body">
                <a href="/app/user/tickets/create" class="btn btn-block btn-success mb-2">
                    <i class="fas fa-plus mr-2"></i> Create New Ticket
                </a>
                <a href="/app/user/tickets" class="btn btn-block btn-primary mb-2">
                    <i class="fas fa-list mr-2"></i> View All My Tickets
                </a>
                <a href="/app/user/profile" class="btn btn-block btn-info">
                    <i class="fas fa-user mr-2"></i> Edit My Profile
                </a>
            </div>
        </div>

        <!-- Help Center -->
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title">Help Center</h3>
            </div>
            <div class="card-body">
                <p class="text-muted">Find answers to common questions:</p>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item p-2">
                        <a href="#">
                            <i class="fas fa-question-circle mr-2 text-primary"></i>
                            How to create a ticket
                        </a>
                    </li>
                    <li class="list-group-item p-2">
                        <a href="#">
                            <i class="fas fa-question-circle mr-2 text-info"></i>
                            Password reset guide
                        </a>
                    </li>
                    <li class="list-group-item p-2">
                        <a href="#">
                            <i class="fas fa-question-circle mr-2 text-success"></i>
                            Billing FAQs
                        </a>
                    </li>
                    <li class="list-group-item p-2">
                        <a href="#">
                            <i class="fas fa-question-circle mr-2 text-warning"></i>
                            Account settings
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-footer">
                <a href="/app/user/help-center" class="btn btn-sm btn-success">Browse All Articles</a>
            </div>
        </div>

        <!-- Contact Support -->
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">Need Help?</h3>
            </div>
            <div class="card-body text-center">
                <i class="fas fa-headset fa-3x text-info mb-3"></i>
                <p>Our support team is here to help you.</p>
                <a href="/app/user/tickets/create" class="btn btn-primary">
                    <i class="fas fa-envelope mr-2"></i> Contact Support
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
