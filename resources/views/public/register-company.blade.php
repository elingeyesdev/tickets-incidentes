@extends('layouts.auth')

@section('title', 'Registrar Empresa')

@section('content')
<div class="card">
    <div class="card-header text-center">
        <h4 class="mb-0">
            <i class="fas fa-building me-2"></i> Solicitud de Empresa
        </h4>
    </div>

    <div class="card-body p-4">
        <div id="alerts"></div>

        <p class="text-muted mb-4">
            ¿Tu empresa quiere utilizar nuestro helpdesk? Completa este formulario y nos pondremos en contacto pronto.
        </p>

        <form id="companyRequestForm">
            <div class="mb-3">
                <label for="companyName" class="form-label">Nombre de la Empresa</label>
                <input
                    type="text"
                    class="form-control"
                    id="companyName"
                    name="companyName"
                    placeholder="Mi Empresa S.A."
                    required
                    minlength="3"
                >
            </div>

            <div class="mb-3">
                <label for="contactEmail" class="form-label">Email de Contacto</label>
                <input
                    type="email"
                    class="form-control"
                    id="contactEmail"
                    name="contactEmail"
                    placeholder="contacto@empresa.com"
                    required
                >
            </div>

            <div class="mb-3">
                <label for="contactPhone" class="form-label">Teléfono de Contacto</label>
                <input
                    type="tel"
                    class="form-control"
                    id="contactPhone"
                    name="contactPhone"
                    placeholder="+34 600 123 456"
                >
            </div>

            <div class="mb-3">
                <label for="contactName" class="form-label">Nombre de Contacto</label>
                <input
                    type="text"
                    class="form-control"
                    id="contactName"
                    name="contactName"
                    placeholder="Juan Pérez"
                    required
                >
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Descripción (Opcional)</label>
                <textarea
                    class="form-control"
                    id="description"
                    name="description"
                    rows="4"
                    placeholder="Cuéntanos sobre tu empresa..."
                ></textarea>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-paper-plane me-2"></i> Enviar Solicitud
            </button>
        </form>

        <hr class="my-4">

        <div class="text-center text-muted">
            <p class="mb-0">¿Ya tienes una cuenta? <a href="{{ route('login') }}">Inicia sesión aquí</a></p>
            <p class="mb-0 mt-2">¿Eres usuario individual? <a href="{{ route('register') }}">Regístrate como usuario</a></p>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
document.getElementById('companyRequestForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = {
        companyName: document.getElementById('companyName').value,
        contactEmail: document.getElementById('contactEmail').value,
        contactPhone: document.getElementById('contactPhone').value || null,
        contactName: document.getElementById('contactName').value,
        description: document.getElementById('description').value || null,
    };

    const alertsDiv = document.getElementById('alerts');
    const submitBtn = this.querySelector('button[type="submit"]');

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Enviando...';

    try {
        const response = await apiRequest('/company-requests', 'POST', formData);

        alertsDiv.innerHTML = `
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>
                <strong>¡Solicitud enviada!</strong> Nos pondremos en contacto con los datos que proporcionaste en los próximos días.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        // Limpiar el formulario
        document.getElementById('companyRequestForm').reset();
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i> Enviar Solicitud';

        // Redirigir después de 2 segundos
        setTimeout(() => {
            window.location.href = '{{ route('home') }}';
        }, 2000);

    } catch (error) {
        alertsDiv.innerHTML = `
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i> ${error.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i> Enviar Solicitud';
    }
});
</script>
@endsection
