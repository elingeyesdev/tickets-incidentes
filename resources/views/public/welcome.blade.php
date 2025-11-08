@extends('layouts.guest')

@section('title', 'Bienvenido - Helpdesk')

@section('content')

<!-- Hero Section -->
<section class="py-5 py-md-6">
    <div class="container">
        <div class="text-center">
            <!-- Badge -->
            <div class="mb-3">
                <span class="badge bg-primary">
                    <i class="fas fa-star me-2"></i> Sistema de Soporte Profesional
                </span>
            </div>

            <!-- Title -->
            <h1 class="display-3 fw-bold mb-4" style="color: #1f2937;">
                Gestiona Tus <span style="color: #2563eb;">Tickets</span> <br>
                de Forma Profesional
            </h1>

            <!-- Subtitle -->
            <p class="h5 text-muted mb-5" style="max-width: 600px; margin: 0 auto;">
                Plataforma integral de helpdesk diseñada para empresas modernas.
                Administra solicitudes, colabora con tu equipo y resuelve problemas rápidamente.
            </p>

            <!-- CTA Buttons -->
            <div class="d-flex gap-3 justify-content-center flex-wrap mb-5">
                <a href="{{ route('register') }}" class="btn btn-primary btn-lg">
                    <i class="fas fa-building me-2"></i> Solicitar Empresa
                    <i class="fas fa-arrow-right ms-2"></i>
                </a>
                <a href="{{ route('login') }}" class="btn btn-outline-primary btn-lg">
                    <i class="fas fa-sign-in-alt me-2"></i> Ingresar
                </a>
                <a href="{{ route('register') }}" class="btn btn-secondary btn-lg">
                    <i class="fas fa-user-plus me-2"></i> Crear Cuenta
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-5" style="background-color: #f9fafb;">
    <div class="container">
        <!-- Section Header -->
        <div class="text-center mb-5">
            <h2 class="h2 fw-bold mb-3">Características Principales</h2>
            <p class="h6 text-muted" style="max-width: 500px; margin: 0 auto;">
                Todo lo que necesitas para gestionar soporte eficientemente
            </p>
        </div>

        <!-- Features Grid -->
        <div class="row g-4">
            <!-- Feature 1: Gestión Segura -->
            <div class="col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-inline-flex align-items-center justify-content-center"
                                 style="width: 50px; height: 50px; background-color: #dbeafe; border-radius: 0.5rem;">
                                <i class="fas fa-shield-alt fa-lg" style="color: #2563eb;"></i>
                            </div>
                        </div>
                        <h5 class="card-title fw-bold mb-2">Gestión Segura</h5>
                        <p class="card-text text-muted">
                            Autenticación JWT avanzada con encriptación de datos.
                            Protección de privacidad en cada nivel.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Feature 2: Respuesta Rápida -->
            <div class="col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-inline-flex align-items-center justify-content-center"
                                 style="width: 50px; height: 50px; background-color: #dcfce7; border-radius: 0.5rem;">
                                <i class="fas fa-bolt fa-lg" style="color: #16a34a;"></i>
                            </div>
                        </div>
                        <h5 class="card-title fw-bold mb-2">Respuesta Rápida</h5>
                        <p class="card-text text-muted">
                            Sistema optimizado para atención inmediata.
                            Notificaciones en tiempo real de nuevos tickets.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Feature 3: Multi-empresa -->
            <div class="col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-inline-flex align-items-center justify-content-center"
                                 style="width: 50px; height: 50px; background-color: #f3e8ff; border-radius: 0.5rem;">
                                <i class="fas fa-users fa-lg" style="color: #a855f7;"></i>
                            </div>
                        </div>
                        <h5 class="card-title fw-bold mb-2">Multi-Empresa</h5>
                        <p class="card-text text-muted">
                            Soporte para múltiples empresas y equipos.
                            Escalabilidad sin límites.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Benefits Section -->
<section class="py-5">
    <div class="container">
        <div class="row g-5 align-items-center">
            <!-- Left Column: Benefits List -->
            <div class="col-lg-6">
                <h2 class="h2 fw-bold mb-4">¿Por Qué Elegirnos?</h2>

                <!-- Benefit Item 1 -->
                <div class="d-flex gap-3 mb-4">
                    <div>
                        <i class="fas fa-check-circle fa-lg" style="color: #16a34a;"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1">Sistema de Tickets Avanzado</h5>
                        <p class="text-muted mb-0">
                            Crea, asigna y resuelve tickets de forma eficiente.
                            Categorización automática y priorización inteligente.
                        </p>
                    </div>
                </div>

                <!-- Benefit Item 2 -->
                <div class="d-flex gap-3 mb-4">
                    <div>
                        <i class="fas fa-check-circle fa-lg" style="color: #16a34a;"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1">Seguimiento en Tiempo Real</h5>
                        <p class="text-muted mb-0">
                            Monitorea el progreso de cada ticket.
                            Historial completo de cambios y comentarios.
                        </p>
                    </div>
                </div>

                <!-- Benefit Item 3 -->
                <div class="d-flex gap-3">
                    <div>
                        <i class="fas fa-check-circle fa-lg" style="color: #16a34a;"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1">Escalabilidad Garantizada</h5>
                        <p class="text-muted mb-0">
                            Infraestructura diseñada para crecer con tu negocio.
                            Rendimiento consistente bajo cualquier carga.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Right Column: CTA Card -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-lg p-4" style="background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);">
                    <div class="card-body text-white text-center">
                        <div class="mb-3">
                            <i class="fas fa-headset fa-4x mb-3" style="opacity: 0.9;"></i>
                        </div>
                        <h4 class="h4 fw-bold mb-2">¡Empezar Ahora es Fácil!</h4>
                        <p class="mb-4" style="font-size: 1.1rem;">
                            Registra tu empresa en minutos y comienza a gestionar tickets profesionalmente.
                        </p>
                        <div class="d-grid gap-2">
                            <a href="{{ route('register') }}" class="btn btn-light btn-lg fw-bold">
                                <i class="fas fa-building me-2"></i> Solicitar Empresa
                            </a>
                            <a href="{{ route('login') }}" class="btn btn-outline-light btn-lg fw-bold">
                                <i class="fas fa-sign-in-alt me-2"></i> Ya Tengo Cuenta
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Final Section -->
<section class="py-5" style="background-color: #f3f4f6;">
    <div class="container text-center">
        <h2 class="h2 fw-bold mb-3">¿Listo Para Mejorar Tu Soporte?</h2>
        <p class="h6 text-muted mb-4" style="max-width: 600px; margin: 0 auto;">
            Únete a cientos de empresas que ya confían en nuestro sistema para gestionar
            su servicio al cliente de forma profesional.
        </p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="{{ route('register') }}" class="btn btn-primary btn-lg">
                <i class="fas fa-check me-2"></i> Comenzar Ahora
            </a>
            <a href="#features" class="btn btn-outline-primary btn-lg">
                <i class="fas fa-info-circle me-2"></i> Conocer Más
            </a>
        </div>
    </div>
</section>

@endsection

@section('css')
<style>
    /* Hero adjustments */
    .display-3 {
        line-height: 1.2;
    }

    /* Card hover effect */
    .card {
        transition: all 0.3s ease;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1) !important;
    }

    /* Badge styling */
    .badge {
        padding: 0.5rem 1rem;
        font-size: 0.95rem;
        font-weight: 500;
    }

    /* Button improvements */
    .btn-lg {
        padding: 0.75rem 2rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .display-3 {
            font-size: 2rem;
        }

        .h5 {
            font-size: 1rem;
        }

        .btn-lg {
            padding: 0.6rem 1.5rem;
            font-size: 0.95rem;
        }
    }

    /* Dark mode support (if needed) */
    @media (prefers-color-scheme: dark) {
        body {
            background-color: #111827;
            color: #f3f4f6;
        }

        .card {
            background-color: #1f2937;
            color: #f3f4f6;
        }

        h1, h2, h3, h4, h5, h6 {
            color: #f3f4f6;
        }
    }
</style>
@endsection

@section('js')
<script>
    // Smooth scroll para links internos
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });

    // Log para debugging
    console.log('[Welcome] Page loaded');
</script>
@endsection
