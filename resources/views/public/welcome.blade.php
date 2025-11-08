@extends('layouts.guest')

@section('title', 'Inicio - Sistema de Gestión de Incidentes')

@section('css')
    <style>
        /* AdminLTE v3 Custom Enhancements */

        /* Layout ajustes */
        .content-wrapper {
            margin-left: 0 !important;
            background: #fff;
        }

        body {
            font-family: 'Source Sans Pro', sans-serif;
            overflow-x: hidden;
        }

        main {
            overflow: hidden;
            width: 100%;
            padding: 0 !important;
        }

        /* Hero Section - usando degradados de AdminLTE */
        .hero-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 100px 15px 80px;
            text-align: center;
            margin: 0;
            position: relative;
            overflow: hidden;
            width: 100%;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.5)" d="M0,96L48,112C96,128,192,160,288,170.7C384,181,480,171,576,160C672,149,768,139,864,144C960,149,1056,171,1152,176C1248,181,1344,171,1392,165.3L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
            background-size: cover;
            opacity: 0.3;
        }

        .hero-section > * {
            position: relative;
            z-index: 1;
        }

        .badge-lg {
            padding: 10px 24px;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: 50px;
            display: inline-block;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.15);
        }

        /* Feature Cards - AdminLTE Card Style */
        .card {
            border: 0;
            box-shadow: 0 0 1px rgba(0, 0, 0, 0.125);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .card-hover {
            transition: all 0.3s ease;
            border-top: 4px solid transparent;
        }

        .card-hover:hover {
            transform: translateY(-6px);
            box-shadow: 0 8px 24px rgba(0,0,0,.12) !important;
        }

        .card-hover.border-top-primary {
            border-top-color: #007bff !important;
        }

        .card-hover.border-top-success {
            border-top-color: #28a745 !important;
        }

        .card-hover.border-top-purple {
            border-top-color: #6f42c1 !important;
        }

        .card-hover:hover.border-top-primary {
            box-shadow: 0 8px 24px rgba(0, 123, 255, 0.15) !important;
        }

        .card-hover:hover.border-top-success {
            box-shadow: 0 8px 24px rgba(40, 167, 69, 0.15) !important;
        }

        .card-hover:hover.border-top-purple {
            box-shadow: 0 8px 24px rgba(111, 66, 193, 0.15) !important;
        }

        .feature-icon {
            width: 100px;
            height: 100px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        /* AdminLTE Light backgrounds */
        .bg-primary-light {
            background: rgba(0, 123, 255, 0.1);
        }

        .bg-success-light {
            background: rgba(40, 167, 69, 0.1);
        }

        .bg-purple-light {
            background: rgba(111, 66, 193, 0.1);
        }

        .text-primary { color: #007bff; }
        .text-success { color: #28a745; }
        .text-purple { color: #6f42c1; }

        /* Info styling - AdminLTE pattern */
        .info-box {
            box-shadow: 0 0 1px rgba(0, 0, 0, 0.125);
            border-radius: 0.28rem;
            background-color: #fff;
            display: flex;
            margin-bottom: 1.5rem;
            min-height: 90px;
            padding: 1rem;
            position: relative;
            transition: all 0.3s ease;
        }

        .info-box:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .info-box-icon {
            border-radius: 0.28rem;
            -webkit-box-align: center;
            align-items: center;
            display: flex;
            -webkit-box-pack: center;
            justify-content: center;
            width: 90px;
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            font-size: 1.8rem;
        }

        .info-box-icon.bg-primary {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
        }

        .info-box-icon.bg-success {
            background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
            color: white;
        }

        .info-box-icon.bg-warning {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: white;
        }

        .info-box-icon.bg-danger {
            background: linear-gradient(135deg, #dc3545 0%, #bd2130 100%);
            color: white;
        }

        .info-box-content {
            display: flex;
            -webkit-box-orient: vertical;
            -webkit-box-direction: normal;
            flex-direction: column;
            -webkit-box-pack: center;
            justify-content: center;
            line-height: 1.8;
            margin-left: 15px;
            width: 100%;
        }

        .info-box-text {
            font-size: 0.875rem;
            font-weight: 600;
            margin: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: #6c757d;
        }

        .info-box-number {
            display: block;
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0;
            color: #2c3e50;
        }

        /* Button enhancements - AdminLTE v3 Style */
        .btn {
            border-radius: 0.28rem;
            font-weight: 600;
            border: 1px solid transparent;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            cursor: pointer;
        }

        .btn-lg {
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
        }

        /* Primary Button - AdminLTE Standard */
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            color: white;
        }

        .btn-primary:hover,
        .btn-primary:focus,
        .btn-primary:active {
            background-color: #0069d9;
            border-color: #0062cc;
            color: white;
        }

        /* Outline Secondary Button - AdminLTE Alternative */
        .btn-outline-secondary {
            color: #6c757d;
            border-color: #6c757d;
            background-color: white;
        }

        .btn-outline-secondary:hover,
        .btn-outline-secondary:focus,
        .btn-outline-secondary:active {
            color: white;
            background-color: #6c757d;
            border-color: #6c757d;
        }

        /* Dark Button - AdminLTE Standard */
        .btn-dark {
            background-color: #343a40;
            border-color: #343a40;
            color: white;
        }

        .btn-dark:hover,
        .btn-dark:focus,
        .btn-dark:active {
            background-color: #23272b;
            border-color: #1d2124;
            color: white;
        }

        /* Block Button */
        .btn-block {
            display: block;
            width: 100%;
        }

        /* Sections */
        .features-section,
        .optimization-section {
            margin: 0;
            width: 100%;
        }

        .features-section {
            background-color: #ffffff;
        }

        .optimization-section {
            background-color: #f8f9fa;
        }

        /* Gradient backgrounds - AdminLTE */
        .bg-gradient-primary {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;
            color: white;
        }

        /* Check icons styling */
        .check-circle {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background-color: #28a745;
            color: white;
            font-weight: bold;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-section {
                padding: 60px 15px 40px;
            }

            .hero-section h1 {
                font-size: 2rem;
            }

            .hero-section .lead {
                font-size: 1rem;
            }

            .card-hover {
                margin-bottom: 15px;
            }
        }
    </style>
@endsection

@section('content')
    {{-- Hero Section --}}
    <div class="hero-section">
        <div class="container">
            <span class="badge badge-primary badge-lg mb-3">
                <i class="fas fa-ticket-alt mr-1"></i> Sistema de Gestión de Incidentes
            </span>

            <h1 class="display-4 font-weight-bold mb-3">
                Gestiona el soporte de tu empresa de manera
                <span class="text-primary">profesional</span>
            </h1>

            <p class="lead text-muted mb-4" style="max-width: 700px; margin-left: auto; margin-right: auto;">
                Plataforma completa de helpdesk que permite a las empresas gestionar tickets de
                soporte, clasificar incidentes y brindar atención al cliente de forma eficiente.
            </p>

            <div class="btn-group-lg" role="group">
                <a href="{{ route('register') }}" class="btn btn-primary mr-2 mb-2">
                    <i class="fas fa-arrow-right mr-1"></i> Registrar Empresa
                </a>

                <a href="{{ route('login') }}" class="btn btn-outline-secondary mr-2 mb-2">
                    <i class="fas fa-sign-in-alt mr-1"></i> Iniciar Sesión
                </a>

                <a href="{{ route('register') }}" class="btn btn-dark mb-2">
                    <i class="fas fa-user-plus mr-1"></i> Registrar Usuario
                </a>
            </div>
        </div>
    </div>

    {{-- Features Section --}}
    <div class="features-section py-5 bg-white">
        <div class="container">
            <div class="row mb-5">
                <div class="col-12 text-center">
                    <h2 class="font-weight-bold mb-2">
                        Todo lo que necesitas para gestionar soporte
                    </h2>
                    <p class="text-muted mb-0" style="max-width: 600px; margin-left: auto; margin-right: auto;">
                        Herramientas profesionales diseñadas para empresas que buscan excelencia en atención al cliente
                    </p>
                </div>
            </div>

            {{-- Feature Cards --}}
            <div class="row">
                {{-- Feature 1: Gestión Segura --}}
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card card-hover border-top-primary h-100">
                        <div class="card-body text-center">
                            <div class="feature-icon bg-primary-light mx-auto">
                                <i class="fas fa-shield-alt text-primary"></i>
                            </div>
                            <h5 class="font-weight-bold card-title">Gestión Segura</h5>
                            <p class="card-text text-muted">
                                Sistema seguro para múltiples empresas con datos protegidos y acceso controlado
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Feature 2: Respuesta Rápida --}}
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card card-hover border-top-success h-100">
                        <div class="card-body text-center">
                            <div class="feature-icon bg-success-light mx-auto">
                                <i class="fas fa-bolt text-success"></i>
                            </div>
                            <h5 class="font-weight-bold card-title">Respuesta Rápida</h5>
                            <p class="card-text text-muted">
                                Clasificación automática por categorías y prioridades para resolver incidentes eficientemente
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Feature 3: Multi-empresa --}}
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card card-hover border-top-purple h-100">
                        <div class="card-body text-center">
                            <div class="feature-icon bg-purple-light mx-auto">
                                <i class="fas fa-users text-purple"></i>
                            </div>
                            <h5 class="font-weight-bold card-title">Multi-empresa</h5>
                            <p class="card-text text-muted">
                                Diseñado para ofrecer servicios de helpdesk a múltiples empresas desde una sola plataforma
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Optimization Section --}}
    <div class="optimization-section py-5 bg-light">
        <div class="container">
            <div class="row align-items-center">
                {{-- Left Column - Benefits List --}}
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="mb-3">
                        <span class="badge badge-primary mb-2" style="font-size: 0.875rem; padding: 8px 16px;">
                            <i class="fas fa-check-circle mr-1"></i> Características Principales
                        </span>
                    </div>
                    <h2 class="font-weight-bold mb-4">
                        Optimiza la atención al cliente de tu empresa
                    </h2>

                    <div class="mb-4">
                        <div class="d-flex align-items-start mb-3">
                            <div class="check-circle mr-3">
                                <i class="fas fa-check"></i>
                            </div>
                            <div>
                                <h5 class="font-weight-bold">Tickets Organizados</h5>
                                <p class="text-muted mb-0">Clasifica y prioriza todos los incidentes automáticamente</p>
                            </div>
                        </div>

                        <div class="d-flex align-items-start mb-3">
                            <div class="check-circle mr-3">
                                <i class="fas fa-check"></i>
                            </div>
                            <div>
                                <h5 class="font-weight-bold">Seguimiento Completo</h5>
                                <p class="text-muted mb-0">Historial detallado de todos los tickets y resoluciones</p>
                            </div>
                        </div>

                        <div class="d-flex align-items-start">
                            <div class="check-circle mr-3">
                                <i class="fas fa-check"></i>
                            </div>
                            <div>
                                <h5 class="font-weight-bold">Escalabilidad</h5>
                                <p class="text-muted mb-0">Crece con tu empresa, desde startups hasta grandes corporaciones</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right Column - CTA Card --}}
                <div class="col-lg-6">
                    <div class="card border-0 shadow">
                        <div class="card-body py-5 text-center">
                            <div class="mb-4">
                                <div class="d-inline-flex align-items-center justify-content-center"
                                     style="width: 80px; height: 80px; background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); border-radius: 12px; color: white;">
                                    <i class="fas fa-headset fa-2x"></i>
                                </div>
                            </div>

                            <h3 class="font-weight-bold mb-2">¿Listo para comenzar?</h3>
                            <p class="text-muted mb-4">
                                Registra tu empresa o inicia sesión para gestionar tickets profesionalmente
                            </p>

                            <a href="{{ route('register') }}" class="btn btn-primary btn-lg btn-block mb-3">
                                <i class="fas fa-building mr-2"></i> Registrar Mi Empresa
                            </a>

                            <a href="{{ route('login') }}" class="btn btn-outline-secondary btn-block">
                                <i class="fas fa-sign-in-alt mr-2"></i> Ya tengo cuenta - Iniciar Sesión
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        console.log('HELPDESK - Sistema de Gestión de Incidentes');

        // Smooth scroll para anclas
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
@endsection
