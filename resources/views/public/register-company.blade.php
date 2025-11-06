@extends('layouts.auth')

@section('title', 'Registrar Empresa')

@section('content')
<div class="register-page">
    <div class="register-box">
        <div class="card card-outline card-secondary">
            <div class="card-header text-center">
                <h1><b>Solicitud de Empresa</b></h1>
            </div>

            <div class="card-body">
                <p class="login-box-msg">¿Tu empresa quiere utilizar nuestro helpdesk? Completa este formulario y nos pondremos en contacto pronto.</p>

                <div id="alerts"></div>

                <form id="companyRequestForm">
                    <!-- Company Name -->
                    <div class="form-group">
                        <div class="input-group">
                            <input
                                type="text"
                                class="form-control"
                                id="companyName"
                                name="companyName"
                                placeholder="Nombre de la Empresa"
                                required
                                minlength="3"
                            >
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <i class="fas fa-building"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Email -->
                    <div class="form-group">
                        <div class="input-group">
                            <input
                                type="email"
                                class="form-control"
                                id="contactEmail"
                                name="contactEmail"
                                placeholder="Email de Contacto"
                                required
                            >
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <i class="fas fa-envelope"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Phone -->
                    <div class="form-group">
                        <div class="input-group">
                            <input
                                type="tel"
                                class="form-control"
                                id="contactPhone"
                                name="contactPhone"
                                placeholder="Teléfono de Contacto (Opcional)"
                            >
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <i class="fas fa-phone"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Name -->
                    <div class="form-group">
                        <div class="input-group">
                            <input
                                type="text"
                                class="form-control"
                                id="contactName"
                                name="contactName"
                                placeholder="Nombre de Contacto"
                                required
                            >
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <i class="fas fa-user"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="form-group">
                        <div class="input-group">
                            <textarea
                                class="form-control"
                                id="description"
                                name="description"
                                rows="4"
                                placeholder="Descripción (Opcional) - Cuéntanos sobre tu empresa..."
                            ></textarea>
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <i class="fas fa-align-left"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-secondary btn-block">
                                <i class="fas fa-paper-plane"></i> Enviar Solicitud
                            </button>
                        </div>
                    </div>
                </form>

                <hr>

                <p class="mb-1 text-center">
                    <a href="{{ route('login') }}">Ya tengo una cuenta</a>
                </p>
                <p class="mb-0 text-center">
                    <a href="{{ route('register') }}">Registrarme como usuario</a>
                </p>
            </div>
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
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

    try {
        const response = await apiRequest('/company-requests', 'POST', formData);

        alertsDiv.innerHTML = `
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h5><i class="icon fas fa-check"></i> Solicitud enviada!</h5>
                Nos pondremos en contacto con los datos que proporcionaste en los próximos días.
            </div>
        `;

        // Limpiar el formulario
        document.getElementById('companyRequestForm').reset();
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar Solicitud';

        // Redirigir después de 2 segundos
        setTimeout(() => {
            window.location.href = '{{ route('home') }}';
        }, 2000);

    } catch (error) {
        alertsDiv.innerHTML = `
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h5><i class="icon fas fa-ban"></i> Error!</h5>
                ${error.message}
            </div>
        `;
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar Solicitud';
    }
});
</script>
@endsection
