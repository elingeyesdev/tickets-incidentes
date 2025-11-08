<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <div class="container-fluid">
        <!-- Brand -->
        <a href="/" class="navbar-brand">
            <img src="{{ asset('logo.png') }}" alt="Helpdesk" class="brand-image" height="33" onerror="this.style.display='none'">
            <span class="brand-text font-weight-light">Helpdesk</span>
        </a>

        <!-- Toggle button for mobile -->
        <button class="navbar-toggler order-1" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse order-3" id="navbarCollapse">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a href="/" class="nav-link">
                        <i class="fas fa-home"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/tickets" class="nav-link">
                        <i class="fas fa-ticket-alt"></i> Tickets
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/companies" class="nav-link">
                        <i class="fas fa-building"></i> Companies
                    </a>
                </li>
            </ul>
        </div>

        <!-- Right navbar links -->
        <ul class="order-1 order-md-3 navbar-nav navbar-no-expand ml-auto">
            <!-- Notifications Dropdown -->
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-bell"></i>
                    <span class="badge badge-warning navbar-badge">3</span>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <span class="dropdown-item dropdown-header">3 Notifications</span>
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item">
                        <i class="fas fa-envelope mr-2"></i> New ticket assigned
                        <span class="float-right text-muted text-sm">3 mins</span>
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item dropdown-footer">See All Notifications</a>
                </div>
            </li>

            <!-- User Dropdown -->
            <li class="nav-item dropdown user-menu">
                <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
                    <img src="{{ auth()->user()?->profile?->avatar_url ?? asset('avatar-placeholder.png') }}"
                         class="user-image img-circle elevation-2"
                         alt="{{ auth()->user()?->email }}"
                         onerror="this.src='{{ asset('vendor/adminlte/dist/img/user2-160x160.jpg') }}'">
                    <span class="d-none d-md-inline">{{ auth()->user()?->profile?->first_name ?? 'User' }}</span>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <!-- User image -->
                    <li class="user-header bg-primary">
                        <img src="{{ auth()->user()?->profile?->avatar_url ?? asset('avatar-placeholder.png') }}"
                             class="img-circle elevation-2"
                             alt="User Image"
                             onerror="this.src='{{ asset('vendor/adminlte/dist/img/user2-160x160.jpg') }}'">
                        <p>
                            {{ auth()->user()?->profile?->first_name ?? 'User' }} {{ auth()->user()?->profile?->last_name ?? '' }}
                            <small>Member since {{ auth()->user()?->created_at?->format('M. Y') ?? 'N/A' }}</small>
                        </p>
                    </li>

                    <!-- Menu Body -->
                    <li class="user-body">
                        <div class="row">
                            <div class="col-6 text-center">
                                <a href="/my-tickets">My Tickets</a>
                            </div>
                            <div class="col-6 text-center">
                                <a href="/my-companies">My Companies</a>
                            </div>
                        </div>
                    </li>

                    <!-- Menu Footer-->
                    <li class="user-footer">
                        <a href="/profile" class="btn btn-default btn-flat">
                            <i class="fas fa-user mr-1"></i> Profile
                        </a>
                        <a href="/preferences" class="btn btn-default btn-flat">
                            <i class="fas fa-cog mr-1"></i> Settings
                        </a>
                        <a @click.prevent="logout()" href="#" class="btn btn-default btn-flat float-right">
                            <i class="fas fa-sign-out-alt mr-1"></i> Logout
                        </a>
                    </li>
                </div>
            </li>
        </ul>
    </div>
</nav>
