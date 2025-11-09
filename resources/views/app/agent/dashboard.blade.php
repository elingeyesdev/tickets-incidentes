@extends('layouts.authenticated')

@section('title', 'Agent Dashboard')

@section('content_header', 'Agent Dashboard')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Agent Dashboard</li>
@endsection

@section('content')
<div class="row">
    <!-- Assigned Tickets -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $stats['assigned_tickets'] ?? 0 }}</h3>
                <p>Assigned Tickets</p>
            </div>
            <div class="icon">
                <i class="fas fa-inbox"></i>
            </div>
            <a href="/app/agent/tickets" class="small-box-footer">
                View Tickets <i class="fas fa-arrow-circle-right"></i>
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
            <a href="/app/agent/tickets?status=resolved" class="small-box-footer">
                More info <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Avg Response Time -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $stats['avg_response_time'] ?? 'N/A' }}</h3>
                <p>Avg Response Time</p>
            </div>
            <div class="icon">
                <i class="fas fa-clock"></i>
            </div>
            <a href="/app/agent/analytics" class="small-box-footer">
                More info <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Satisfaction Rate -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $stats['satisfaction_rate'] ?? 0 }}%</h3>
                <p>Satisfaction Rate</p>
            </div>
            <div class="icon">
                <i class="fas fa-smile"></i>
            </div>
            <a href="/app/agent/feedback" class="small-box-footer">
                More info <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
</div>

<div class="row">
    <!-- My Ticket Queue -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">My Ticket Queue</h3>
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
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>#T-1234</td>
                            <td>Cannot access dashboard</td>
                            <td>John Doe</td>
                            <td><span class="badge badge-danger">High</span></td>
                            <td><span class="badge badge-warning">Open</span></td>
                            <td>10 mins ago</td>
                            <td><a href="#" class="btn btn-sm btn-primary">Reply</a></td>
                        </tr>
                        <tr>
                            <td>#T-1235</td>
                            <td>Password reset not working</td>
                            <td>Jane Smith</td>
                            <td><span class="badge badge-warning">Medium</span></td>
                            <td><span class="badge badge-info">In Progress</span></td>
                            <td>1 hour ago</td>
                            <td><a href="#" class="btn btn-sm btn-primary">Reply</a></td>
                        </tr>
                        <tr>
                            <td>#T-1236</td>
                            <td>Feature inquiry</td>
                            <td>Bob Wilson</td>
                            <td><span class="badge badge-success">Low</span></td>
                            <td><span class="badge badge-secondary">New</span></td>
                            <td>2 hours ago</td>
                            <td><a href="#" class="btn btn-sm btn-primary">Reply</a></td>
                        </tr>
                        <tr>
                            <td>#T-1237</td>
                            <td>Billing question</td>
                            <td>Alice Brown</td>
                            <td><span class="badge badge-warning">Medium</span></td>
                            <td><span class="badge badge-warning">Open</span></td>
                            <td>3 hours ago</td>
                            <td><a href="#" class="btn btn-sm btn-primary">Reply</a></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                <a href="/app/agent/tickets" class="btn btn-primary">View All My Tickets</a>
            </div>
        </div>
    </div>

    <!-- Performance & Quick Actions -->
    <div class="col-md-4">
        <!-- Performance Card -->
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">My Performance</h3>
            </div>
            <div class="card-body">
                <div class="chart">
                    <canvas id="performanceChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title">Quick Actions</h3>
            </div>
            <div class="card-body">
                <a href="/app/agent/tickets?filter=unassigned" class="btn btn-block btn-outline-primary mb-2">
                    <i class="fas fa-inbox mr-2"></i> View Unassigned Tickets
                </a>
                <a href="/app/agent/notes/create" class="btn btn-block btn-outline-info mb-2">
                    <i class="fas fa-sticky-note mr-2"></i> Create Internal Note
                </a>
                <a href="/app/agent/help-center" class="btn btn-block btn-outline-success">
                    <i class="fas fa-question-circle mr-2"></i> Browse Help Center
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Team Notes -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Team Notes</h3>
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
                        <i class="fas fa-sticky-note bg-blue"></i>
                        <div class="timeline-item">
                            <span class="time"><i class="fas fa-clock"></i> 30 mins ago</span>
                            <h3 class="timeline-header">Sarah Agent shared a note</h3>
                            <div class="timeline-body">
                                New billing workflow implemented. Check the updated guidelines in Help Center.
                            </div>
                        </div>
                    </div>
                    <div>
                        <i class="fas fa-info-circle bg-green"></i>
                        <div class="timeline-item">
                            <span class="time"><i class="fas fa-clock"></i> 2 hours ago</span>
                            <h3 class="timeline-header">Mike Agent posted an update</h3>
                            <div class="timeline-body">
                                Reminder: Team meeting tomorrow at 10 AM to discuss new features.
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

    <!-- Help Center Quick Access -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Help Center Quick Access</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="#">
                            <i class="fas fa-book mr-2 text-primary"></i>
                            How to handle escalated tickets
                        </a>
                    </li>
                    <li class="list-group-item">
                        <a href="#">
                            <i class="fas fa-book mr-2 text-info"></i>
                            Best practices for customer communication
                        </a>
                    </li>
                    <li class="list-group-item">
                        <a href="#">
                            <i class="fas fa-book mr-2 text-success"></i>
                            Using macros effectively
                        </a>
                    </li>
                    <li class="list-group-item">
                        <a href="#">
                            <i class="fas fa-book mr-2 text-warning"></i>
                            Ticket priority guidelines
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-footer">
                <a href="/app/agent/help-center" class="btn btn-sm btn-primary">Browse All Articles</a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('vendor/adminlte/plugins/chart.js/Chart.min.js') }}"></script>
<script>
    // Performance Chart
    var ctx = document.getElementById('performanceChart').getContext('2d');
    var performanceChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Resolved', 'In Progress', 'Open'],
            datasets: [{
                data: [60, 25, 15],
                backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
            }]
        },
        options: {
            maintainAspectRatio: false,
            responsive: true,
            legend: {
                position: 'bottom',
            }
        }
    });
</script>
@endsection

@section('css')
<link rel="stylesheet" href="{{ asset('vendor/adminlte/plugins/chart.js/Chart.min.css') }}">
@endsection
