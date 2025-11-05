@extends('layouts.auth')

@section('title', 'Bienvenido')

@section('styles')
<style>
    .welcome-section {
        text-align: center;
        padding: 3rem 0;
    }
    .welcome-title {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 1rem;
        color: white;
    }
    .welcome-subtitle {
        font-size: 1.2rem;
        color: rgba(255, 255, 255, 0.9);
        margin-bottom: 2rem;
    }
    .features {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 2rem;
        margin-top: 3rem;
    }
    .feature-card {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }
    .feature-icon {
        font-size: 2.5rem;
        color: #667eea;
        margin-bottom: 1rem;
    }
    .auth-buttons {
        margin-top: 2rem;
        display: flex;
        gap: 1rem;
        justify-content: center;
    }
    .auth-buttons .btn {
        padding: 0.75rem 2rem;
        font-weight: 600;
        border-radius: 8px;
    }
    .btn-light {
        background-color: white;
        color: #667eea;
        border: none;
    }
    .btn-light:hover {
        background-color: rgba(255, 255, 255, 0.9);
    }
    .btn-outline-light {
        color: white;
        border: 2px solid white;
    }
    .btn-outline-light:hover {
        background-color: white;
        color: #667eea;
    }
</style>
@endsection

@section('content')
<div style="width: 100%; max-width: 800px;">
    <div class="welcome-section">
        <h1 class="welcome-title">
            <i class="fas fa-headset me-3"></i> Helpdesk
        </h1>
        <p class="welcome-subtitle">
            Sistema integral de gestión de tickets y soporte técnico
        </p>

        <div class="features">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <h5>Gestión de Tickets</h5>
                <p class="text-muted">Crea y gestiona tickets de soporte de forma eficiente</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h5>Equipo Colaborativo</h5>
                <p class="text-muted">Trabaja con tu equipo en tiempo real</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <h5>Reportes Detallados</h5>
                <p class="text-muted">Analiza el desempeño de tu equipo</p>
            </div>
        </div>

        <div class="auth-buttons">
            <a href="{{ route('login') }}" class="btn btn-light">
                <i class="fas fa-sign-in-alt me-2"></i> Iniciar Sesión
            </a>
            <a href="{{ route('register') }}" class="btn btn-outline-light">
                <i class="fas fa-user-plus me-2"></i> Registrarse
            </a>
        </div>
    </div>
</div>
@endsection
