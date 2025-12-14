<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\PlatformAdminController;
use App\Http\Controllers\Dashboard\CompanyAdminController;
use App\Http\Controllers\Dashboard\AgentController;
use App\Http\Controllers\Dashboard\UserController;
use App\Features\CompanyManagement\Http\Controllers\CompanyRequestAdminController;
use App\Features\Reports\Http\Controllers\PlatformReportController;
use App\Shared\Helpers\JWTHelper;

// ========== TESTING ROUTES (Development Only) ==========
// Remove these routes in production

Route::prefix('test')->group(function () {
    // Alpine.js test
    Route::get('/alpine-test', function () {
        return view('test.alpine-test');
    })->name('test.alpine-test');

    // JWT System testing
    Route::get('/jwt-interactive', function () {
        return view('test.jwt-interactive');
    })->name('test.jwt-interactive');
});

// Visual Examples / Communication Lab (NO AUTHENTICATION REQUIRED)
// CURRENTLY SHOWING: Ticket Design Complete
Route::get('/tests', function () {
    return view('tests.ticket-design-complete');
})->name('tests.index');

// Alternative Design - Design
Route::get('/tests2', function () {
    return view('tests.ticket-design');
})->name('tests.design');

// Announcement Designs
Route::get('/tests/announcements', function () {
    return view('tests.announcements-timeline');
})->name('tests.announcements.timeline');

Route::get('/tests/announcements/cards', function () {
    return view('tests.announcements-cards');
})->name('tests.announcements.cards');

Route::get('/tests/announcements/list', function () {
    return view('tests.announcements-list');
})->name('tests.announcements.list');

Route::get('/tests/announcements/accordion', function () {
    return view('tests.announcements-accordion');
})->name('tests.announcements.accordion');

// Create Ticket Design Demo
Route::get('/tests/create-ticket', function () {
    return view('tests.create-ticket-design');
})->name('tests.create-ticket');

Route::get('/tests/announcements/feed', function () {
    return view('tests.announcements-feed');
})->name('tests.announcements.feed');

Route::get('/tests/announcements/featured', function () {
    return view('tests.announcements-featured');
})->name('tests.announcements.featured');

// Help Center Designs
Route::get('/tests/helpcenter-search-first', function () {
    return view('tests.helpcenter-search-first');
})->name('tests.helpcenter.search-first');

Route::get('/tests/helpcenter-faq-accordion', function () {
    return view('tests.helpcenter-faq-accordion');
})->name('tests.helpcenter.faq-accordion');

Route::get('/tests/helpcenter-knowledge-base', function () {
    return view('tests.helpcenter-knowledge-base');
})->name('tests.helpcenter.knowledge-base');

// User Modals Lab - Design Proposals
Route::get('/tests/user-modals', function () {
    return view('tests.user-modals-lab');
})->name('tests.user-modals');


// ========== PUBLIC ROUTES ==========

// Welcome / Landing page
// ========== PUBLIC ROUTES ==========

// Root Route - The Entry Point "Auth Loader"
// This page loads first, checks auth status via AJAX, syncs localStorage, and redirects appropriately.
Route::get('/', function () {
    return view('auth.loading');
})->name('root');

// Auth Check Endpoint (Called by auth.loading view)
Route::get('/auth/check-status', [\App\Http\Controllers\Auth\CheckAuthStatusController::class, 'check'])
    ->name('auth.check-status');

// Welcome / Landing page (Redirected to if guest)
Route::get('/welcome', function () {
    return view('public.welcome');
})->middleware('jwt.guest')->name('welcome');

// Company Request / Solicitud de Empresa
Route::get('/solicitud-empresa', function () {
    return view('public.company-request');
})->middleware('jwt.guest')->name('company.request');

// ========== AUTHENTICATION ROUTES (Blade) ==========

// Public auth pages
Route::get('/login', function () {
    return view('public.login');
})->middleware('jwt.guest')->name('login');

Route::get('/register', function () {
    return view('public.register');
})->middleware('jwt.guest')->name('register');

Route::get('/forgot-password', function () {
    return view('public.forgot-password');
})->name('password.request');

// Email verification (public - token/code identifies user)
Route::get('/verify-email', function () {
    return view('public.verify-email');
})->name('verification.notice');

// Confirm password (requires authentication)
Route::get('/confirm-password', function () {
    return view('public.confirm-password');
})->middleware('jwt.require')->name('password.confirm');

// ========== PROTECTED ROUTES (Requires JWT) ==========

Route::middleware('jwt.require')->group(function () {
    // Legacy dashboard route (kept for backward compatibility)
    Route::get('/profile', function () {
        return view('app.profile.index');
    })->name('profile');

    // User Profile Page
    Route::get('/app/profile', function () {
        return view('app.profile.index');
    })->name('app.profile');

    // Ticket Chat Export (TXT)
    Route::get('/app/tickets/{ticketCode}/export-chat', [\App\Features\Reports\Http\Controllers\TicketChatExportController::class, 'exportTxt'])
        ->name('tickets.export-chat');
});

// ========== AUTH-FLOW ROUTES (Role Selection, Onboarding) ==========

// Prepare web route - establishes JWT cookie and redirects
Route::get('/auth/prepare-web', function () {
    $token = request()->query('token');
    $redirect = request()->query('redirect', '/app/dashboard');

    if (!$token) {
        return redirect('/login')->with('error', 'Token no proporcionado');
    }

    try {
        // Validate token on backend to ensure it's valid
        $tokenService = app(App\Features\Authentication\Services\TokenService::class);
        $payload = $tokenService->validateAccessToken($token);

        // Get user to generate refresh token
        $user = \App\Features\UserManagement\Models\User::findOrFail($payload->user_id);

        // Generate Refresh Token
        $deviceInfo = \App\Shared\Helpers\DeviceInfoParser::fromRequest(request());
        $refreshTokenData = $tokenService->createRefreshToken($user, $deviceInfo);

        // Token is valid, establish cookies and redirect
        $cookieLifetimeMinutes = (int) config('jwt.ttl'); // Access token TTL in minutes
        $refreshCookieLifetimeMinutes = (int) config('jwt.refresh_ttl'); // Refresh token TTL in minutes
        $secure = config('app.env') === 'production';

        return redirect($redirect)
            // Access Token Cookie (Not HttpOnly - JS needs to read it)
            ->cookie(
                'jwt_token',                    // name
                $token,                         // value
                $cookieLifetimeMinutes,        // minutes
                '/',                            // path
                null,                           // domain
                $secure,                        // secure
                false,                          // httpOnly (false so JS can read it)
                false,                          // raw
                'lax'                           // sameSite
            )
            // Refresh Token Cookie (HttpOnly for security)
            ->cookie(
                'refresh_token',                // name
                $refreshTokenData['token'],     // value
                $refreshCookieLifetimeMinutes,  // minutes
                '/',                            // path
                null,                           // domain
                $secure,                        // secure
                true,                           // httpOnly (true for security)
                false,                          // raw
                'lax'                           // sameSite
            );
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('[AUTH PREPARE-WEB] Token validation failed', [
            'error' => $e->getMessage(),
            'token_preview' => $token ? substr($token, 0, 20) . '...' : null,
        ]);
        return redirect('/login')->with('error', 'Token inválido: ' . $e->getMessage());
    }
})->name('auth.prepare-web');

Route::middleware('jwt.require')->prefix('auth-flow')->group(function () {
    // Role selector - shown when user has multiple roles and needs to select one
    Route::get('/role-selector', function () {
        return view('auth-flow.role-selector');
    })->name('auth-flow.role-selector');
});

// ========== DASHBOARD ROUTES (Role-Based) ==========

Route::middleware('jwt.require')->prefix('app')->group(function () {
    // Fallback dashboard redirect (if frontend doesn't handle role detection)
    // Normally frontend redirects directly to role-specific dashboard
    Route::get('/dashboard', [DashboardController::class, 'redirect'])->name('dashboard');

    // Platform Admin Dashboard (PLATFORM_ADMIN role)
    // Usa spatie.active_role: verifica rol en Spatie + active_role en JWT
    Route::middleware('spatie.active_role:PLATFORM_ADMIN')->prefix('admin')->group(function () {
        Route::get('/dashboard', [PlatformAdminController::class, 'dashboard'])
            ->name('dashboard.platform-admin');

        // Company Requests Management
        Route::get('/company-requests', [CompanyRequestAdminController::class, 'index'])
            ->name('admin.requests.index');

        // Companies Management
        Route::get('/companies', function () {
            return view('app.platform-admin.companies.index');
        })->name('admin.companies.index');

        // Users Management
        Route::get('/users', function () {
            return view('app.platform-admin.users.index');
        })->name('admin.users.index');

        // API Keys Management
        Route::get('/api-keys', [\App\Features\ExternalIntegration\Http\Controllers\ApiKeyAdminController::class, 'index'])
            ->name('admin.api-keys.index');

        // =================================================================
        // REPORTS CENTER - 4 Separate Views
        // =================================================================

        // Legacy index redirect
        Route::get('/reports', [PlatformReportController::class, 'index'])
            ->name('admin.reports.index');

        // Companies Report
        Route::get('/reports/companies', [PlatformReportController::class, 'companies'])
            ->name('admin.reports.companies');
        Route::get('/reports/companies/excel', [PlatformReportController::class, 'companiesExcel'])
            ->name('admin.reports.companies.excel');
        Route::get('/reports/companies/pdf', [PlatformReportController::class, 'companiesPdf'])
            ->name('admin.reports.companies.pdf');

        // Growth Report
        Route::get('/reports/growth', [PlatformReportController::class, 'growth'])
            ->name('admin.reports.growth');
        Route::get('/reports/growth/excel', [PlatformReportController::class, 'growthExcel'])
            ->name('admin.reports.growth.excel');
        Route::get('/reports/growth/pdf', [PlatformReportController::class, 'growthPdf'])
            ->name('admin.reports.growth.pdf');

        // Requests Report
        Route::get('/reports/requests', [PlatformReportController::class, 'requests'])
            ->name('admin.reports.requests');
        Route::get('/reports/requests/excel', [PlatformReportController::class, 'requestsExcel'])
            ->name('admin.reports.requests.excel');
        Route::get('/reports/requests/pdf', [PlatformReportController::class, 'requestsPdf'])
            ->name('admin.reports.requests.pdf');

        // API Keys Report (NEW)
        Route::get('/reports/apikeys', [PlatformReportController::class, 'apikeys'])
            ->name('admin.reports.apikeys');
        Route::get('/reports/apikeys/excel', [PlatformReportController::class, 'apikeysExcel'])
            ->name('admin.reports.apikeys.excel');
        Route::get('/reports/apikeys/pdf', [PlatformReportController::class, 'apikeysPdf'])
            ->name('admin.reports.apikeys.pdf');
    });

    // Company Admin Dashboard (COMPANY_ADMIN role)
    // Usa spatie.active_role: verifica rol en Spatie + active_role en JWT
    Route::middleware('spatie.active_role:COMPANY_ADMIN')->prefix('company')->group(function () {
        Route::get('/dashboard', [CompanyAdminController::class, 'dashboard'])
            ->name('dashboard.company-admin');

        // Categories Management
        Route::get('/categories', function () {
            $user = JWTHelper::getAuthenticatedUser();
            $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');
            return view('app.company-admin.categories.index', [
                'user' => $user,
                'companyId' => $companyId
            ]);
        })->name('company.categories.index');

        // Areas Management
        Route::get('/areas', function () {
            $user = JWTHelper::getAuthenticatedUser();
            $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');
            return view('app.company-admin.areas.index', [
                'user' => $user,
                'companyId' => $companyId
            ]);
        })->name('company.areas.index');

        // Settings (Company Configuration)
        Route::get('/settings', function () {
            $user = JWTHelper::getAuthenticatedUser();
            $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');
            return view('app.company-admin.settings.index', [
                'user' => $user,
                'companyId' => $companyId
            ]);
        })->name('company.settings.index');

        // Articles Management (Help Center)
        Route::get('/articles', function () {
            $user = JWTHelper::getAuthenticatedUser();
            $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');
            return view('app.company-admin.articles.index', [
                'user' => $user,
                'companyId' => $companyId
            ]);
        })->name('company.articles.index');

        // Announcements Management
        Route::get('/announcements', function () {
            $user = JWTHelper::getAuthenticatedUser();
            $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');
            return view('app.company-admin.announcements.index', [
                'user' => $user,
                'companyId' => $companyId
            ]);
        })->name('company.announcements.index');

        Route::get('/announcements/manage', function () {
            $user = JWTHelper::getAuthenticatedUser();
            $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');
            return view('app.company-admin.announcements.manage', [
                'user' => $user,
                'companyId' => $companyId
            ]);
        })->name('company.announcements.manage');

        // Tickets Management (Company Admin)
        Route::get('/tickets', function () {
            $user = JWTHelper::getAuthenticatedUser();
            $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');
            return view('app.shared.tickets.index', [
                'user' => $user,
                'companyId' => $companyId,
                'role' => 'COMPANY_ADMIN'
            ]);
        })->name('company.tickets.index');

        Route::get('/tickets/manage', function () {
            $user = JWTHelper::getAuthenticatedUser();
            $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');
            return view('app.shared.tickets.manage', [
                'user' => $user,
                'companyId' => $companyId,
                'role' => 'COMPANY_ADMIN'
            ]);
        })->name('company.tickets.manage');

        Route::get('/tickets/{any}', function () {
            $user = JWTHelper::getAuthenticatedUser();
            $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');
            return view('app.shared.tickets.index', [
                'user' => $user,
                'companyId' => $companyId,
                'role' => 'COMPANY_ADMIN'
            ]);
        })->where('any', '.*')->name('company.tickets.wildcard');

        // Company Admin Reports - Views
        Route::get('/reports/tickets', function () {
            $user = JWTHelper::getAuthenticatedUser();
            $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');

            // Load tickets with relationships
            $tickets = \App\Features\TicketManagement\Models\Ticket::with(['creator.profile', 'ownerAgent.profile', 'category'])
                ->where('company_id', $companyId)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            // Calculate KPIs
            $ticketStats = \App\Features\TicketManagement\Models\Ticket::where('company_id', $companyId)
                ->select('status', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status')
                ->toArray();

            $kpis = [
                'total' => array_sum($ticketStats),
                'open' => $ticketStats['open'] ?? 0,
                'pending' => $ticketStats['pending'] ?? 0,
                'resolved' => ($ticketStats['resolved'] ?? 0) + ($ticketStats['closed'] ?? 0),
            ];

            // Priority distribution
            $priorityStats = \App\Features\TicketManagement\Models\Ticket::where('company_id', $companyId)
                ->select('priority', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
                ->groupBy('priority')
                ->pluck('total', 'priority')
                ->toArray();

            // Load filter options
            $categories = \App\Features\TicketManagement\Models\Category::where('company_id', $companyId)
                ->orderBy('name')
                ->get();

            $agents = \App\Features\UserManagement\Models\User::with('profile')
                ->whereHas('userRoles', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId)->where('role_code', 'AGENT');
                })
                ->get();

            $areas = \App\Features\CompanyManagement\Models\Area::where('company_id', $companyId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            // Monthly trend (last 6 months)
            $monthlyData = [];
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $monthKey = $date->format('Y-m');
                $monthLabel = $date->locale('es')->isoFormat('MMM YY');

                $count = \App\Features\TicketManagement\Models\Ticket::where('company_id', $companyId)
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count();

                $monthlyData[] = ['label' => $monthLabel, 'count' => $count];
            }

            // Top categories
            $topCategories = \App\Features\TicketManagement\Models\Ticket::where('company_id', $companyId)
                ->select('category_id', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
                ->whereNotNull('category_id')
                ->groupBy('category_id')
                ->orderByDesc('total')
                ->limit(5)
                ->with('category')
                ->get()
                ->map(fn($t) => ['name' => $t->category?->name ?? 'Sin categoría', 'count' => $t->total]);

            return view('app.company-admin.reports.tickets', [
                'user' => $user,
                'companyId' => $companyId,
                'tickets' => $tickets,
                'kpis' => $kpis,
                'priorityStats' => $priorityStats,
                'categories' => $categories,
                'agents' => $agents,
                'areas' => $areas,
                'monthlyData' => $monthlyData,
                'topCategories' => $topCategories,
            ]);
        })->name('company.reports.tickets');

        Route::get('/reports/agents', function () {
            $user = JWTHelper::getAuthenticatedUser();
            $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');

            // Load agents with ticket stats
            $agents = \App\Features\UserManagement\Models\User::with('profile')
                ->whereHas('userRoles', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId)->where('role_code', 'AGENT');
                })
                ->get()
                ->map(function ($agent) use ($companyId) {
                    $assigned = \App\Features\TicketManagement\Models\Ticket::where('company_id', $companyId)
                        ->where('owner_agent_id', $agent->id)->count();
                    $resolved = \App\Features\TicketManagement\Models\Ticket::where('company_id', $companyId)
                        ->where('owner_agent_id', $agent->id)
                        ->whereIn('status', ['resolved', 'closed'])->count();

                    return [
                        'id' => $agent->id,
                        'name' => $agent->profile?->display_name ?? $agent->email,
                        'email' => $agent->email,
                        'assigned' => $assigned,
                        'resolved' => $resolved,
                        'active' => $assigned - $resolved,
                        'rate' => $assigned > 0 ? round(($resolved / $assigned) * 100) : 0,
                    ];
                });

            // KPIs
            $totalAgents = $agents->count();
            $totalAssigned = $agents->sum('assigned');
            $avgTicketsPerAgent = $totalAgents > 0 ? round($totalAssigned / $totalAgents) : 0;
            $avgResolutionRate = $totalAgents > 0 ? round($agents->avg('rate')) : 0;
            $bestAgent = $agents->sortByDesc('resolved')->first();

            return view('app.company-admin.reports.agents', [
                'user' => $user,
                'companyId' => $companyId,
                'agents' => $agents,
                'kpis' => [
                    'total' => $totalAgents,
                    'avgTickets' => $avgTicketsPerAgent,
                    'avgRate' => $avgResolutionRate,
                    'bestAgent' => $bestAgent['name'] ?? '-',
                ],
            ]);
        })->name('company.reports.agents');



        Route::get('/reports/company', function () {
            $user = JWTHelper::getAuthenticatedUser();
            $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');
            
            // Load company info
            $company = \App\Features\CompanyManagement\Models\Company::with('industry')->find($companyId);
            
            // Load team members
            $admins = \App\Features\UserManagement\Models\User::with('profile')
                ->whereHas('userRoles', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId)->where('role_code', 'COMPANY_ADMIN');
                })
                ->get()
                ->map(fn($u) => [
                    'name' => $u->profile?->display_name ?? $u->email,
                    'email' => $u->email,
                    'avatar' => $u->profile?->avatar_url,
                ]);
            
            $agents = \App\Features\UserManagement\Models\User::with('profile')
                ->whereHas('userRoles', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId)->where('role_code', 'AGENT');
                })
                ->get()
                ->map(function ($u) use ($companyId) {
                    $assigned = \App\Features\TicketManagement\Models\Ticket::where('company_id', $companyId)
                        ->where('owner_agent_id', $u->id)->count();
                    $resolved = \App\Features\TicketManagement\Models\Ticket::where('company_id', $companyId)
                        ->where('owner_agent_id', $u->id)
                        ->whereIn('status', ['resolved', 'closed'])->count();
                    
                    return [
                        'name' => $u->profile?->display_name ?? $u->email,
                        'email' => $u->email,
                        'avatar' => $u->profile?->avatar_url,
                        'assigned' => $assigned,
                        'resolved' => $resolved,
                        'rate' => $assigned > 0 ? round(($resolved / $assigned) * 100) : 0,
                    ];
                });
            
            // Load areas and categories
            $areas = \App\Features\CompanyManagement\Models\Area::where('company_id', $companyId)->get();
            $categories = \App\Features\TicketManagement\Models\Category::where('company_id', $companyId)->get();
            
            // General stats
            $totalTickets = \App\Features\TicketManagement\Models\Ticket::where('company_id', $companyId)->count();
            $totalAnnouncements = \App\Features\ContentManagement\Models\Announcement::where('company_id', $companyId)->count();
            $totalArticles = \App\Features\ContentManagement\Models\HelpCenterArticle::where('company_id', $companyId)->count();
            
            return view('app.company-admin.reports.company', [
                'user' => $user,
                'companyId' => $companyId,
                'company' => $company,
                'admins' => $admins,
                'agents' => $agents,
                'areas' => $areas,
                'categories' => $categories,
                'stats' => [
                    'totalAdmins' => $admins->count(),
                    'totalAgents' => $agents->count(),
                    'totalAreas' => $areas->count(),
                    'totalCategories' => $categories->count(),
                    'totalTickets' => $totalTickets,
                    'totalAnnouncements' => $totalAnnouncements,
                    'totalArticles' => $totalArticles,
                ],
            ]);
        })->name('company.reports.company');

        // Announcements Report View
        Route::get('/reports/announcements', function () {
            $user = JWTHelper::getAuthenticatedUser();
            $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');

            // Load announcements
            $announcements = \App\Features\ContentManagement\Models\Announcement::with(['author.profile'])
                ->where('company_id', $companyId)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            // Stats by status
            $statusStats = \App\Features\ContentManagement\Models\Announcement::where('company_id', $companyId)
                ->select('status', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status')
                ->toArray();

            // Stats by type
            $typeStats = \App\Features\ContentManagement\Models\Announcement::where('company_id', $companyId)
                ->select('type', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
                ->groupBy('type')
                ->pluck('total', 'type')
                ->toArray();

            $total = array_sum($statusStats);

            return view('app.company-admin.reports.announcements', [
                'user' => $user,
                'companyId' => $companyId,
                'announcements' => $announcements,
                'kpis' => [
                    'total' => $total,
                    'published' => $statusStats['published'] ?? 0,
                    'draft' => $statusStats['draft'] ?? 0,
                    'archived' => $statusStats['archived'] ?? 0,
                ],
                'typeStats' => $typeStats,
                'statusStats' => $statusStats,
            ]);
        })->name('company.reports.announcements');

        // Articles Report View
        Route::get('/reports/articles', function () {
            $user = JWTHelper::getAuthenticatedUser();
            $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');

            // Load articles
            $articles = \App\Features\ContentManagement\Models\HelpCenterArticle::with(['author.profile', 'category'])
                ->where('company_id', $companyId)
                ->orderBy('views_count', 'desc')
                ->limit(10)
                ->get();

            // Stats by status
            $statusStats = \App\Features\ContentManagement\Models\HelpCenterArticle::where('company_id', $companyId)
                ->select('status', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status')
                ->toArray();

            // Stats by category
            $categoryStats = \App\Features\ContentManagement\Models\HelpCenterArticle::where('company_id', $companyId)
                ->whereNotNull('category_id')
                ->select('category_id', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
                ->groupBy('category_id')
                ->with('category')
                ->get()
                ->map(fn($a) => ['name' => $a->category?->name ?? 'Sin categoría', 'count' => $a->total]);

            // Load categories for filter (Global)
            $categories = \App\Features\ContentManagement\Models\ArticleCategory::orderBy('name')
                ->get();

            $totalViews = \App\Features\ContentManagement\Models\HelpCenterArticle::where('company_id', $companyId)->sum('views_count');
            $total = array_sum($statusStats);

            return view('app.company-admin.reports.articles', [
                'user' => $user,
                'companyId' => $companyId,
                'articles' => $articles,
                'categories' => $categories,
                'kpis' => [
                    'total' => $total,
                    'published' => $statusStats['published'] ?? 0,
                    'draft' => $statusStats['draft'] ?? 0,
                    'totalViews' => $totalViews,
                ],
                'categoryStats' => $categoryStats,
                'statusStats' => $statusStats,
            ]);
        })->name('company.reports.articles');

        // Company Admin Reports - Downloads
        Route::get('/reports/tickets/excel', [\App\Features\Reports\Http\Controllers\CompanyReportController::class, 'ticketsExcel'])
            ->name('company.reports.tickets.excel');
        Route::get('/reports/tickets/pdf', [\App\Features\Reports\Http\Controllers\CompanyReportController::class, 'ticketsPdf'])
            ->name('company.reports.tickets.pdf');
        Route::get('/reports/agents/excel', [\App\Features\Reports\Http\Controllers\CompanyReportController::class, 'agentsExcel'])
            ->name('company.reports.agents.excel');
        Route::get('/reports/agents/pdf', [\App\Features\Reports\Http\Controllers\CompanyReportController::class, 'agentsPdf'])
            ->name('company.reports.agents.pdf');
        Route::get('/reports/summary/pdf', [\App\Features\Reports\Http\Controllers\CompanyReportController::class, 'summaryPdf'])
            ->name('company.reports.summary.pdf');
        Route::get('/reports/company/pdf', [\App\Features\Reports\Http\Controllers\CompanyReportController::class, 'companyPdf'])
            ->name('company.reports.company.pdf');
        Route::get('/reports/announcements/pdf', [\App\Features\Reports\Http\Controllers\CompanyReportController::class, 'announcementsPdf'])
            ->name('company.reports.announcements.pdf');
        Route::get('/reports/articles/pdf', [\App\Features\Reports\Http\Controllers\CompanyReportController::class, 'articlesPdf'])
            ->name('company.reports.articles.pdf');

        // Agents Management
        Route::get('/agents', function () {
            $user = JWTHelper::getAuthenticatedUser();
            $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');
            return view('app.company-admin.agents.index', [
                'user' => $user,
                'companyId' => $companyId
            ]);
        })->name('company.agents.index');
    });

    // Agent Dashboard (AGENT role)
    // Usa spatie.active_role: verifica rol en Spatie + active_role en JWT
    Route::middleware('spatie.active_role:AGENT')->prefix('agent')->group(function () {
        Route::get('/dashboard', [AgentController::class, 'dashboard'])
            ->name('dashboard.agent');

        // Tickets (Agent)
        Route::get('/tickets', function () {
            $user = JWTHelper::getAuthenticatedUser();
            $companyId = JWTHelper::getCompanyIdFromJWT('AGENT');
            return view('app.shared.tickets.index', [
                'user' => $user,
                'companyId' => $companyId,
                'role' => 'AGENT'
            ]);
        })->name('agent.tickets.index');

        Route::get('/tickets/manage', function () {
            $user = JWTHelper::getAuthenticatedUser();
            $companyId = JWTHelper::getCompanyIdFromJWT('AGENT');
            return view('app.shared.tickets.manage', [
                'user' => $user,
                'companyId' => $companyId,
                'role' => 'AGENT'
            ]);
        })->name('agent.tickets.manage');

        Route::get('/tickets/{any}', function () {
            $user = JWTHelper::getAuthenticatedUser();
            $companyId = JWTHelper::getCompanyIdFromJWT('AGENT');
            return view('app.shared.tickets.index', [
                'user' => $user,
                'companyId' => $companyId,
                'role' => 'AGENT'
            ]);
        })->where('any', '.*')->name('agent.tickets.wildcard');

        // Agent Reports - Views
        Route::get('/reports/tickets', function () {
            $user = JWTHelper::getAuthenticatedUser();
            return view('app.agent.reports.tickets', ['user' => $user]);
        })->name('agent.reports.tickets');

        Route::get('/reports/performance', function () {
            $user = JWTHelper::getAuthenticatedUser();
            return view('app.agent.reports.performance', ['user' => $user]);
        })->name('agent.reports.performance');

        // Agent Reports - Downloads
        Route::get('/reports/tickets/excel', [\App\Features\Reports\Http\Controllers\AgentReportController::class, 'ticketsExcel'])
            ->name('agent.reports.tickets.excel');
        Route::get('/reports/tickets/pdf', [\App\Features\Reports\Http\Controllers\AgentReportController::class, 'ticketsPdf'])
            ->name('agent.reports.tickets.pdf');
        Route::get('/reports/performance/pdf', [\App\Features\Reports\Http\Controllers\AgentReportController::class, 'performancePdf'])
            ->name('agent.reports.performance.pdf');
    });

    // User Dashboard (USER role)
    // Usa spatie.active_role: verifica rol en Spatie + active_role en JWT
    Route::middleware('spatie.active_role:USER')->prefix('user')->group(function () {
        Route::get('/dashboard', [UserController::class, 'dashboard'])
            ->name('dashboard.user');

        // Tickets (User)
        Route::get('/tickets', function () {
            $user = JWTHelper::getAuthenticatedUser();
            $companyId = JWTHelper::getCompanyIdFromJWT('USER');
            return view('app.shared.tickets.index', [
                'user' => $user,
                'companyId' => $companyId,
                'role' => 'USER'
            ]);
        })->name('user.tickets.index');

        Route::get('/tickets/manage', function () {
            $user = JWTHelper::getAuthenticatedUser();
            $companyId = JWTHelper::getCompanyIdFromJWT('USER');
            return view('app.shared.tickets.manage', [
                'user' => $user,
                'companyId' => $companyId,
                'role' => 'USER'
            ]);
        })->name('user.tickets.manage');

        Route::get('/tickets/{any}', function () {
            $user = JWTHelper::getAuthenticatedUser();
            $companyId = JWTHelper::getCompanyIdFromJWT('USER');
            return view('app.shared.tickets.index', [
                'user' => $user,
                'companyId' => $companyId,
                'role' => 'USER'
            ]);
        })->where('any', '.*')->name('user.tickets.wildcard');

        Route::get('/announcements', function () {
            $user = JWTHelper::getAuthenticatedUser();
            return view('app.user.announcements.index', [
                'user' => $user,
            ]);
        })->name('user.announcements.index');

        Route::get('/help-center', function () {
            $user = JWTHelper::getAuthenticatedUser();
            return view('app.user.help-center.index', [
                'user' => $user,
            ]);
        })->name('user.help-center.index');

        Route::get('/companies', function () {
            $user = JWTHelper::getAuthenticatedUser();
            return view('app.user.companies.index', [
                'user' => $user,
            ]);
        })->name('user.companies.index');

        Route::get('/tickets/manage', function () {
            $user = JWTHelper::getAuthenticatedUser();
            $companyId = JWTHelper::getCompanyIdFromJWT('USER');
            return view('app.shared.tickets.manage', [
                'user' => $user,
                'companyId' => $companyId,
                'role' => 'USER'
            ]);
        })->name('user.tickets.manage');

        // User Reports
        Route::get('/reports/tickets', function () {
            $user = JWTHelper::getAuthenticatedUser();

            // Get companies user has interacted with (via tickets)
            $companies = \App\Features\TicketManagement\Models\Ticket::where('created_by_user_id', $user->id)
                ->join('business.companies', 'tickets.company_id', '=', 'business.companies.id')
                ->select('business.companies.id', 'business.companies.name')
                ->distinct()
                ->orderBy('business.companies.name')
                ->get();

            // Get all categories used in user's tickets (categories are in ticketing schema)
            $categories = \App\Features\TicketManagement\Models\Ticket::where('created_by_user_id', $user->id)
                ->join('ticketing.categories', 'tickets.category_id', '=', 'ticketing.categories.id')
                ->select('ticketing.categories.id', 'ticketing.categories.name', 'ticketing.categories.company_id')
                ->distinct()
                ->orderBy('ticketing.categories.name')
                ->get();

            return view('app.user.reports.tickets', [
                'user' => $user,
                'companies' => $companies,
                'categories' => $categories
            ]);
        })->name('user.reports.tickets');

        Route::get('/reports/activity', function () {
            $user = JWTHelper::getAuthenticatedUser();
            return view('app.user.reports.activity', [
                'user' => $user,
            ]);
        })->name('user.reports.activity');

        // User Reports Downloads
        Route::get('/reports/tickets/excel', [\App\Features\Reports\Http\Controllers\UserReportController::class, 'ticketsExcel'])
            ->name('user.reports.tickets.excel');
        Route::get('/reports/tickets/pdf', [\App\Features\Reports\Http\Controllers\UserReportController::class, 'ticketsPdf'])
            ->name('user.reports.tickets.pdf');
        Route::get('/reports/activity/excel', [\App\Features\Reports\Http\Controllers\UserReportController::class, 'activityExcel'])
            ->name('user.reports.activity.excel');
        Route::get('/reports/activity/pdf', [\App\Features\Reports\Http\Controllers\UserReportController::class, 'activityPdf'])
            ->name('user.reports.activity.pdf');
    });

    // Generic Tickets Routes (will redirect to role-specific route)
    Route::get('/tickets', function () {
        $user = JWTHelper::getAuthenticatedUser();
        $activeRole = $user['activeRole']['code'] ?? null;

        if ($activeRole === 'COMPANY_ADMIN') {
            return redirect()->route('company.tickets.index');
        } elseif ($activeRole === 'AGENT') {
            return redirect()->route('agent.tickets.index');
        } elseif ($activeRole === 'USER') {
            return redirect()->route('user.tickets.index');
        }

        return redirect()->route('dashboard');
    })->name('app.tickets.index');

    Route::get('/tickets/manage', function () {
        $user = JWTHelper::getAuthenticatedUser();
        $activeRole = $user['activeRole']['code'] ?? null;

        if ($activeRole === 'COMPANY_ADMIN') {
            return redirect()->route('company.tickets.manage');
        } elseif ($activeRole === 'AGENT') {
            return redirect()->route('agent.tickets.manage');
        }

        return redirect()->route('dashboard');
    })->name('tickets.manage');
});

// ========== WIDGET EMBEBIBLE (Acceso desde proyectos externos) ==========
// Estas rutas son públicas pero requieren un token JWT válido en la URL

use App\Features\ExternalIntegration\Http\Controllers\WidgetController;

Route::prefix('widget')->group(function () {
    // Vista principal del widget (flujo de autenticación)
    Route::get('/', [WidgetController::class, 'index'])->name('widget.index');

    // Vista de tickets (requiere token en URL)
    Route::get('/tickets', [WidgetController::class, 'tickets'])->name('widget.tickets');
});

