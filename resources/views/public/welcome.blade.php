@extends('layouts.auth')

@section('title', 'Bienvenido')

@section('styles')
<style>
    .welcome-page {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        padding: 2rem 1rem;
    }
    .welcome-container {
        width: 100%;
        max-width: 1000px;
        text-align: center;
    }
    .welcome-logo {
        margin-bottom: 2rem;
    }
    .welcome-logo h1 {
        font-size: 3.5rem;
        font-weight: 700;
        color: white;
        margin-bottom: 0.5rem;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
    }
    .welcome-logo i {
        font-size: 3rem;
        margin-right: 1rem;
        vertical-align: middle;
    }
    .welcome-subtitle {
        font-size: 1.3rem;
        color: rgba(255, 255, 255, 0.95);
        margin-bottom: 3rem;
        font-weight: 400;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
    }
    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1.5rem;
        margin-bottom: 3rem;
    }
    .feature-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .feature-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }
    .feature-card .card-body {
        padding: 2rem 1.5rem;
    }
    .feature-icon {
        color: #007bff;
        margin-bottom: 1rem;
    }
    .feature-card h5 {
        font-weight: 600;
        color: #343a40;
        margin-bottom: 0.75rem;
    }
    .feature-card p {
        color: #6c757d;
        font-size: 0.95rem;
        margin-bottom: 0;
        line-height: 1.5;
    }
    .auth-buttons {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }
    .auth-buttons .btn {
        padding: 0.75rem 2.5rem;
        font-size: 1.1rem;
        font-weight: 600;
        border-radius: 50px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }
    .btn-primary {
        background-color: white;
        border-color: white;
        color: #007bff;
    }
    .btn-primary:hover {
        background-color: #f8f9fa;
        border-color: #f8f9fa;
        color: #0056b3;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
    }
    .btn-outline-light {
        color: white;
        border: 2px solid white;
        background-color: transparent;
    }
    .btn-outline-light:hover {
        background-color: white;
        border-color: white;
        color: #007bff;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
    }
    @media (max-width: 768px) {
        .welcome-logo h1 {
            font-size: 2.5rem;
        }
        .welcome-logo i {
            font-size: 2.2rem;
        }
        .welcome-subtitle {
            font-size: 1.1rem;
        }
        .features-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        .auth-buttons .btn {
            padding: 0.65rem 2rem;
            font-size: 1rem;
        }
    }
</style>
@endsection

@section('content')
<div class="welcome-page">
    <div class="welcome-container">
        <!-- Logo y Título Principal -->
        <div class="welcome-logo">
            <h1>
                <i class="fas fa-headset"></i>Helpdesk
            </h1>
            <p class="welcome-subtitle">
                Sistema integral de gestión de tickets y soporte técnico profesional
            </p>
        </div>

        <!-- Tarjetas de Características -->
        <div class="features-grid">
            <!-- Feature 1: Gestión de Tickets -->
            <div class="card feature-card">
                <div class="card-body">
                    <div class="feature-icon">
                        <i class="fas fa-ticket-alt fa-3x"></i>
                    </div>
                    <h5>Gestión de Tickets</h5>
                    <p>Crea, asigna y resuelve tickets de soporte de manera eficiente y organizada</p>
                </div>
            </div>

            <!-- Feature 2: Equipo Colaborativo -->
            <div class="card feature-card">
                <div class="card-body">
                    <div class="feature-icon">
                        <i class="fas fa-users fa-3x"></i>
                    </div>
                    <h5>Equipo Colaborativo</h5>
                    <p>Trabaja en equipo con asignaciones inteligentes y comunicación centralizada</p>
                </div>
            </div>

            <!-- Feature 3: Reportes Detallados -->
            <div class="card feature-card">
                <div class="card-body">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line fa-3x"></i>
                    </div>
                    <h5>Reportes Detallados</h5>
                    <p>Analiza métricas y tendencias para mejorar la calidad del servicio</p>
                </div>
            </div>

            <!-- Feature 4: Soporte 24/7 -->
            <div class="card feature-card">
                <div class="card-body">
                    <div class="feature-icon">
                        <i class="fas fa-clock fa-3x"></i>
                    </div>
                    <h5>Soporte 24/7</h5>
                    <p>Sistema disponible en todo momento para atender necesidades urgentes</p>
                </div>
            </div>
        </div>

        <!-- Botones de Autenticación -->
        <div class="auth-buttons">
            <a href="{{ route('login') }}" class="btn btn-primary btn-lg">
                <i class="fas fa-sign-in-alt mr-2"></i>Iniciar Sesión
            </a>
            <a href="{{ route('register') }}" class="btn btn-outline-light btn-lg">
                <i class="fas fa-user-plus mr-2"></i>Registrarse
            </a>
        </div>
    </div>
</div>
@endsection
