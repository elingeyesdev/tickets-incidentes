<div id="template-no-followers" style="display: none;">
    <div class="card card-outline card-info">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-inbox mr-2"></i>
                No sigues a ninguna empresa
            </h3>
        </div>
        <div class="card-body text-center py-5">
            <i class="fas fa-building fa-3x text-muted mb-3"></i>
            <p class="text-muted">
                Sigue a empresas para recibir sus anuncios, noticias sobre mantenimientos, incidentes y alertas.
            </p>

            <h5 class="mt-4 mb-3">Empresas Populares</h5>
            <!-- Lista de empresas sugeridas con botón "Seguir" -->
            <div id="suggested-companies-list" class="text-left mx-auto" style="max-width: 500px;">
                <!-- Cargado dinámicamente desde API -->
                <div class="text-center">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    <span class="ml-2">Cargando sugerencias...</span>
                </div>
            </div>
            
            <div class="mt-4">
                <a href="/app/user/companies/explore" class="btn btn-default">
                    <i class="fas fa-search mr-1"></i> Explorar todas las empresas
                </a>
            </div>
        </div>
    </div>
</div>
