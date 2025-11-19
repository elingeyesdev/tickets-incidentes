<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laboratorio - Widgets de Estadísticas</title>

    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback" rel="stylesheet">

    <style>
        body {
            background-color: #f4f6f9;
            padding: 20px;
        }
        .option-section {
            margin-bottom: 50px;
            background: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
        }
        .option-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #495057;
        }
        .option-description {
            color: #6c757d;
            margin-bottom: 20px;
        }
        .bg-purple {
            background-color: #6f42c1 !important;
        }
        .text-purple {
            color: #6f42c1 !important;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <h1 class="mb-4">Laboratorio: Diseño de Widgets de Estadísticas</h1>
        <p class="text-muted mb-4">Comparación de 3 opciones para mostrar las estadísticas de anuncios</p>

        <!-- OPCIÓN 1: Small Boxes con Color de Fondo -->
        <div class="option-section">
            <div class="option-title">Opción 1: Small Boxes con Colores de Fondo</div>
            <div class="option-description">
                Más visual y atractivo. Los colores ayudan a identificar rápidamente cada métrica. Las métricas críticas (incidentes) resaltan inmediatamente.
            </div>

            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>11</h3>
                            <p>Total Publicados</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-broadcast-tower"></i>
                        </div>
                        <a href="#" class="small-box-footer">Más info <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3>3</h3>
                            <p>Incidentes Activos</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <a href="#" class="small-box-footer">Más info <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="small-box bg-purple">
                        <div class="inner">
                            <h3>0</h3>
                            <p>Mantenimientos Próximos</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <a href="#" class="small-box-footer">Más info <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3>2</h3>
                            <p>Este Mes</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <a href="#" class="small-box-footer">Más info <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
            </div>
        </div>

        <!-- OPCIÓN 2: Info Boxes (Compactas) -->
        <div class="option-section">
            <div class="option-title">Opción 2: Info Boxes (Compactas)</div>
            <div class="option-description">
                Diseño más compacto. Iconos a la izquierda con color. Mejor uso del espacio horizontal.
            </div>

            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-info"><i class="fas fa-broadcast-tower"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Publicados</span>
                            <span class="info-box-number">11</span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-danger"><i class="fas fa-exclamation-triangle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Incidentes Activos</span>
                            <span class="info-box-number">3</span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-purple"><i class="fas fa-tools"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Mantenimientos Próximos</span>
                            <span class="info-box-number">0</span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-success"><i class="fas fa-calendar-alt"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Este Mes</span>
                            <span class="info-box-number">2</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- OPCIÓN 3: Mejorado con bg-light (Actual) -->
        <div class="option-section">
            <div class="option-title">Opción 3: Light Background Mejorado (Diseño Actual)</div>
            <div class="option-description">
                Mantiene el fondo claro pero con números y iconos más coloridos. Más sutil y limpio.
            </div>

            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-light">
                        <div class="inner">
                            <h3 class="text-info">11</h3>
                            <p>Total Publicados</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-broadcast-tower text-info" style="font-size: 70px;"></i>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="small-box bg-light">
                        <div class="inner">
                            <h3 class="text-danger">3</h3>
                            <p>Incidentes Activos</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-exclamation-triangle text-danger" style="font-size: 70px;"></i>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="small-box bg-light">
                        <div class="inner">
                            <h3 class="text-purple">0</h3>
                            <p>Mantenimientos Próximos</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-tools text-purple" style="font-size: 70px;"></i>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="small-box bg-light">
                        <div class="inner">
                            <h3 class="text-success">2</h3>
                            <p>Este Mes</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-calendar-alt text-success" style="font-size: 70px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- OPCIÓN 4: Cards Simples -->
        <div class="option-section">
            <div class="option-title">Opción 4: Cards Minimalistas</div>
            <div class="option-description">
                Diseño limpio y moderno. Bordes con color como acento. Muy minimalista.
            </div>

            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="card" style="border-top: 3px solid #17a2b8;">
                        <div class="card-body text-center">
                            <i class="fas fa-broadcast-tower fa-3x text-info mb-2"></i>
                            <h3 class="mb-0">11</h3>
                            <p class="text-muted mb-0">Total Publicados</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="card" style="border-top: 3px solid #dc3545;">
                        <div class="card-body text-center">
                            <i class="fas fa-exclamation-triangle fa-3x text-danger mb-2"></i>
                            <h3 class="mb-0">3</h3>
                            <p class="text-muted mb-0">Incidentes Activos</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="card" style="border-top: 3px solid #6f42c1;">
                        <div class="card-body text-center">
                            <i class="fas fa-tools fa-3x text-purple mb-2"></i>
                            <h3 class="mb-0">0</h3>
                            <p class="text-muted mb-0">Mantenimientos Próximos</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="card" style="border-top: 3px solid #28a745;">
                        <div class="card-body text-center">
                            <i class="fas fa-calendar-alt fa-3x text-success mb-2"></i>
                            <h3 class="mb-0">2</h3>
                            <p class="text-muted mb-0">Este Mes</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>
