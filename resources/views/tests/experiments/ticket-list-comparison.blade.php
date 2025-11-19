<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laboratorio - Diseños de Lista de Tickets</title>

    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback" rel="stylesheet">
    <!-- iCheck Bootstrap -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/icheck-bootstrap/3.0.1/icheck-bootstrap.min.css">

    <style>
        body {
            background-color: #f4f6f9;
            padding: 20px;
        }
        .example-section {
            margin-bottom: 60px;
            background: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
        }
        .example-title {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #495057;
        }
        .example-description {
            color: #6c757d;
            margin-bottom: 30px;
            font-size: 16px;
        }
        .comparison-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 20px;
        }
        .role-section {
            border: 2px solid #dee2e6;
            border-radius: 5px;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .role-label {
            font-size: 18px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 15px;
            text-align: center;
            padding: 10px;
            background: white;
            border-radius: 5px;
        }
        .mailbox-messages {
            background: white;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <h1 class="mb-4">Laboratorio: Comparación de Listas de Tickets (USER vs AGENT/COMPANY_ADMIN)</h1>
        <p class="text-muted mb-5">3 propuestas siguiendo estándares de AdminLTE v3 (mailbox template)</p>

        <!-- OPCIÓN 1: Tabla Estilo Mailbox Clásico -->
        <div class="example-section">
            <div class="example-title">Opción 1: Estilo Mailbox Clásico (Lista Compacta)</div>
            <div class="example-description">
                Basado 100% en el template mailbox.html de AdminLTE v3. USER ve ticket code + título. AGENT/COMPANY_ADMIN ven además el nombre del creador.
            </div>

            <div class="comparison-container">
                <!-- USER View -->
                <div class="role-section">
                    <div class="role-label"><i class="fas fa-user"></i> Vista USER</div>
                    <div class="table-responsive mailbox-messages">
                        <table class="table table-hover table-striped">
                            <tbody>
                            <tr>
                                <td>
                                    <div class="icheck-primary">
                                        <input type="checkbox" value="" id="check1">
                                        <label for="check1"></label>
                                    </div>
                                </td>
                                <td class="mailbox-star"><a href="#"><i class="fas fa-star text-warning"></i></a></td>
                                <td class="mailbox-subject">
                                    <span class="badge badge-danger">Open</span>
                                    <b>TKT-2025-00001</b> - Error al exportar reporte mensual
                                </td>
                                <td class="mailbox-attachment"><i class="fas fa-paperclip"></i></td>
                                <td class="mailbox-date">Hace 2 min</td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="icheck-primary">
                                        <input type="checkbox" value="" id="check2">
                                        <label for="check2"></label>
                                    </div>
                                </td>
                                <td class="mailbox-star"><a href="#"><i class="far fa-star"></i></a></td>
                                <td class="mailbox-subject">
                                    <span class="badge badge-warning">Pending</span>
                                    <b>TKT-2025-00002</b> - No puedo acceder al dashboard
                                </td>
                                <td class="mailbox-attachment"></td>
                                <td class="mailbox-date">Hace 1 hora</td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="icheck-primary">
                                        <input type="checkbox" value="" id="check3">
                                        <label for="check3"></label>
                                    </div>
                                </td>
                                <td class="mailbox-star"><a href="#"><i class="far fa-star"></i></a></td>
                                <td class="mailbox-subject">
                                    <span class="badge badge-success">Resolved</span>
                                    <b>TKT-2025-00003</b> - Consulta sobre facturación
                                </td>
                                <td class="mailbox-attachment"><i class="fas fa-paperclip"></i></td>
                                <td class="mailbox-date">Ayer</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- AGENT/COMPANY_ADMIN View -->
                <div class="role-section">
                    <div class="role-label"><i class="fas fa-user-tie"></i> Vista AGENT/COMPANY_ADMIN</div>
                    <div class="table-responsive mailbox-messages">
                        <table class="table table-hover table-striped">
                            <tbody>
                            <tr>
                                <td>
                                    <div class="icheck-primary">
                                        <input type="checkbox" value="" id="check4">
                                        <label for="check4"></label>
                                    </div>
                                </td>
                                <td class="mailbox-star"><a href="#"><i class="fas fa-star text-warning"></i></a></td>
                                <td class="mailbox-name"><a href="#">Juan Pérez</a></td>
                                <td class="mailbox-subject">
                                    <span class="badge badge-danger">Open</span>
                                    <b>TKT-2025-00001</b> - Error al exportar reporte mensual
                                </td>
                                <td class="mailbox-attachment"><i class="fas fa-paperclip"></i></td>
                                <td class="mailbox-date">Hace 2 min</td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="icheck-primary">
                                        <input type="checkbox" value="" id="check5">
                                        <label for="check5"></label>
                                    </div>
                                </td>
                                <td class="mailbox-star"><a href="#"><i class="far fa-star"></i></a></td>
                                <td class="mailbox-name"><a href="#">María González</a></td>
                                <td class="mailbox-subject">
                                    <span class="badge badge-warning">Pending</span>
                                    <b>TKT-2025-00002</b> - No puedo acceder al dashboard
                                </td>
                                <td class="mailbox-attachment"></td>
                                <td class="mailbox-date">Hace 1 hora</td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="icheck-primary">
                                        <input type="checkbox" value="" id="check6">
                                        <label for="check6"></label>
                                    </div>
                                </td>
                                <td class="mailbox-star"><a href="#"><i class="far fa-star"></i></a></td>
                                <td class="mailbox-name"><a href="#">Carlos Rodríguez</a></td>
                                <td class="mailbox-subject">
                                    <span class="badge badge-success">Resolved</span>
                                    <b>TKT-2025-00003</b> - Consulta sobre facturación
                                </td>
                                <td class="mailbox-attachment"><i class="fas fa-paperclip"></i></td>
                                <td class="mailbox-date">Ayer</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="mt-3 p-3" style="background-color: #e3f2fd; border-radius: 5px;">
                <strong><i class="fas fa-info-circle"></i> Características:</strong>
                <ul class="mb-0 mt-2">
                    <li><strong>USER:</strong> Solo checkbox, estrella, código+título, adjunto, fecha</li>
                    <li><strong>AGENT/ADMIN:</strong> Agrega columna "mailbox-name" con nombre del creador</li>
                    <li>100% compatible con mailbox.html de AdminLTE</li>
                    <li>Compacto y eficiente en espacio</li>
                </ul>
            </div>
        </div>

        <!-- OPCIÓN 2: Con Avatar y Metadata Expandida -->
        <div class="example-section">
            <div class="example-title">Opción 2: Con Avatar y Metadata Expandida</div>
            <div class="example-description">
                Agrega avatares y más información (categoría, respuestas). USER ve layout simplificado, AGENT/ADMIN ven creador + agente asignado.
            </div>

            <div class="comparison-container">
                <!-- USER View -->
                <div class="role-section">
                    <div class="role-label"><i class="fas fa-user"></i> Vista USER</div>
                    <div class="table-responsive mailbox-messages">
                        <table class="table table-hover">
                            <tbody>
                            <tr>
                                <td style="width: 40px;">
                                    <div class="icheck-primary">
                                        <input type="checkbox" value="" id="u1">
                                        <label for="u1"></label>
                                    </div>
                                </td>
                                <td style="width: 40px;">
                                    <a href="#"><i class="fas fa-star text-warning"></i></a>
                                </td>
                                <td>
                                    <div>
                                        <span class="badge badge-danger mr-2">Open</span>
                                        <strong>TKT-2025-00001</strong> - Error al exportar reporte mensual
                                    </div>
                                    <small class="text-muted">
                                        <i class="fas fa-tag"></i> Soporte Técnico
                                        <i class="fas fa-comments ml-3"></i> 3 respuestas
                                        <i class="fas fa-paperclip ml-2"></i> 1 adjunto
                                    </small>
                                </td>
                                <td style="width: 100px; text-align: right;">
                                    <small class="text-muted">Hace 2 min</small>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="icheck-primary">
                                        <input type="checkbox" value="" id="u2">
                                        <label for="u2"></label>
                                    </div>
                                </td>
                                <td>
                                    <a href="#"><i class="far fa-star"></i></a>
                                </td>
                                <td>
                                    <div>
                                        <span class="badge badge-warning mr-2">Pending</span>
                                        <strong>TKT-2025-00002</strong> - No puedo acceder al dashboard
                                    </div>
                                    <small class="text-muted">
                                        <i class="fas fa-tag"></i> Accesos
                                        <i class="fas fa-comments ml-3"></i> 1 respuesta
                                    </small>
                                </td>
                                <td style="text-align: right;">
                                    <small class="text-muted">Hace 1 hora</small>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- AGENT/COMPANY_ADMIN View -->
                <div class="role-section">
                    <div class="role-label"><i class="fas fa-user-tie"></i> Vista AGENT/COMPANY_ADMIN</div>
                    <div class="table-responsive mailbox-messages">
                        <table class="table table-hover">
                            <tbody>
                            <tr>
                                <td style="width: 40px;">
                                    <div class="icheck-primary">
                                        <input type="checkbox" value="" id="a1">
                                        <label for="a1"></label>
                                    </div>
                                </td>
                                <td style="width: 40px;">
                                    <a href="#"><i class="fas fa-star text-warning"></i></a>
                                </td>
                                <td style="width: 50px;">
                                    <img src="https://ui-avatars.com/api/?name=Juan+Perez&size=40&background=007bff&color=fff" class="img-circle" alt="User Image">
                                </td>
                                <td>
                                    <div>
                                        <span class="badge badge-danger mr-2">Open</span>
                                        <strong>TKT-2025-00001</strong> - Error al exportar reporte mensual
                                    </div>
                                    <small class="text-muted">
                                        <strong>Juan Pérez</strong>
                                        <i class="fas fa-tag ml-3"></i> Soporte Técnico
                                        <i class="fas fa-user-check ml-3"></i> Asignado: María G.
                                        <i class="fas fa-comments ml-3"></i> 3
                                        <i class="fas fa-paperclip ml-2"></i> 1
                                    </small>
                                </td>
                                <td style="width: 100px; text-align: right;">
                                    <small class="text-muted">Hace 2 min</small>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="icheck-primary">
                                        <input type="checkbox" value="" id="a2">
                                        <label for="a2"></label>
                                    </div>
                                </td>
                                <td>
                                    <a href="#"><i class="far fa-star"></i></a>
                                </td>
                                <td>
                                    <img src="https://ui-avatars.com/api/?name=Maria+Gonzalez&size=40&background=28a745&color=fff" class="img-circle" alt="User Image">
                                </td>
                                <td>
                                    <div>
                                        <span class="badge badge-warning mr-2">Pending</span>
                                        <strong>TKT-2025-00002</strong> - No puedo acceder al dashboard
                                    </div>
                                    <small class="text-muted">
                                        <strong>María González</strong>
                                        <i class="fas fa-tag ml-3"></i> Accesos
                                        <i class="fas fa-user-slash ml-3 text-danger"></i> Sin asignar
                                        <i class="fas fa-comments ml-3"></i> 1
                                    </small>
                                </td>
                                <td style="text-align: right;">
                                    <small class="text-muted">Hace 1 hora</small>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="mt-3 p-3" style="background-color: #e3f2fd; border-radius: 5px;">
                <strong><i class="fas fa-info-circle"></i> Características:</strong>
                <ul class="mb-0 mt-2">
                    <li><strong>USER:</strong> Solo muestra ticket code, título, categoría, contadores</li>
                    <li><strong>AGENT/ADMIN:</strong> Agrega avatar del creador, nombre en negrita, agente asignado</li>
                    <li>Más información visible sin clicks adicionales</li>
                    <li>Avatares generados automáticamente</li>
                </ul>
            </div>
        </div>

        <!-- OPCIÓN 3: Estilo Cards/Timeline (Más Visual) -->
        <div class="example-section">
            <div class="example-title">Opción 3: Estilo Cards con Timeline Visual</div>
            <div class="example-description">
                Diseño más visual usando cards en lugar de tabla. USER ve cards simples, AGENT/ADMIN ven cards con header mostrando creador.
            </div>

            <div class="comparison-container">
                <!-- USER View -->
                <div class="role-section">
                    <div class="role-label"><i class="fas fa-user"></i> Vista USER</div>

                    <div class="card card-widget">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="badge badge-danger">Open</span>
                                    <strong class="ml-2">TKT-2025-00001</strong>
                                </div>
                                <small class="text-muted">Hace 2 min</small>
                            </div>
                            <h5 class="mt-2 mb-2">Error al exportar reporte mensual</h5>
                            <div class="text-muted">
                                <small>
                                    <i class="fas fa-tag"></i> Soporte Técnico
                                    <i class="fas fa-comments ml-3"></i> 3 respuestas
                                    <i class="fas fa-paperclip ml-3"></i> 1 adjunto
                                </small>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="#" class="btn btn-sm btn-primary"><i class="fas fa-eye"></i> Ver detalles</a>
                        </div>
                    </div>

                    <div class="card card-widget">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="badge badge-warning">Pending</span>
                                    <strong class="ml-2">TKT-2025-00002</strong>
                                </div>
                                <small class="text-muted">Hace 1 hora</small>
                            </div>
                            <h5 class="mt-2 mb-2">No puedo acceder al dashboard</h5>
                            <div class="text-muted">
                                <small>
                                    <i class="fas fa-tag"></i> Accesos
                                    <i class="fas fa-comments ml-3"></i> 1 respuesta
                                </small>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="#" class="btn btn-sm btn-primary"><i class="fas fa-eye"></i> Ver detalles</a>
                        </div>
                    </div>
                </div>

                <!-- AGENT/COMPANY_ADMIN View -->
                <div class="role-section">
                    <div class="role-label"><i class="fas fa-user-tie"></i> Vista AGENT/COMPANY_ADMIN</div>

                    <div class="card card-widget">
                        <div class="card-header">
                            <div class="user-block">
                                <img class="img-circle" src="https://ui-avatars.com/api/?name=Juan+Perez&size=40&background=007bff&color=fff" alt="User Image">
                                <span class="username"><a href="#">Juan Pérez</a></span>
                                <span class="description">Hace 2 min - <i class="fas fa-user-check text-success"></i> Asignado a María G.</span>
                            </div>
                            <div class="card-tools">
                                <span class="badge badge-danger">Open</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <strong>TKT-2025-00001</strong>
                            </div>
                            <h5 class="mb-2">Error al exportar reporte mensual</h5>
                            <div class="text-muted">
                                <small>
                                    <i class="fas fa-tag"></i> Soporte Técnico
                                    <i class="fas fa-comments ml-3"></i> 3 respuestas
                                    <i class="fas fa-paperclip ml-3"></i> 1 adjunto
                                </small>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="#" class="btn btn-sm btn-primary"><i class="fas fa-eye"></i> Ver detalles</a>
                            <button class="btn btn-sm btn-default"><i class="fas fa-user-plus"></i> Reasignar</button>
                        </div>
                    </div>

                    <div class="card card-widget">
                        <div class="card-header">
                            <div class="user-block">
                                <img class="img-circle" src="https://ui-avatars.com/api/?name=Maria+Gonzalez&size=40&background=28a745&color=fff" alt="User Image">
                                <span class="username"><a href="#">María González</a></span>
                                <span class="description">Hace 1 hora - <i class="fas fa-user-slash text-danger"></i> Sin asignar</span>
                            </div>
                            <div class="card-tools">
                                <span class="badge badge-warning">Pending</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <strong>TKT-2025-00002</strong>
                            </div>
                            <h5 class="mb-2">No puedo acceder al dashboard</h5>
                            <div class="text-muted">
                                <small>
                                    <i class="fas fa-tag"></i> Accesos
                                    <i class="fas fa-comments ml-3"></i> 1 respuesta
                                </small>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="#" class="btn btn-sm btn-primary"><i class="fas fa-eye"></i> Ver detalles</a>
                            <button class="btn btn-sm btn-success"><i class="fas fa-user-plus"></i> Asignarme</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-3 p-3" style="background-color: #e3f2fd; border-radius: 5px;">
                <strong><i class="fas fa-info-circle"></i> Características:</strong>
                <ul class="mb-0 mt-2">
                    <li><strong>USER:</strong> Cards simples con código, título, metadata básica</li>
                    <li><strong>AGENT/ADMIN:</strong> Card header con "user-block" (avatar + nombre creador + asignación)</li>
                    <li>Más espacioso, mejor para visualizar información de un vistazo</li>
                    <li>Usa componente "card-widget" de AdminLTE</li>
                    <li>Permite acciones rápidas en footer (ver, asignar)</li>
                </ul>
            </div>
        </div>

        <!-- Resumen Recomendación -->
        <div class="example-section" style="background-color: #d4edda;">
            <div class="example-title" style="color: #155724;">
                <i class="fas fa-star"></i> Recomendación Final
            </div>
            <div class="row">
                <div class="col-md-12">
                    <h4>Opción 1: Mailbox Clásico (Recomendada)</h4>
                    <p class="mb-3"><strong>Razones:</strong></p>
                    <ul>
                        <li><strong>Estándar AdminLTE v3:</strong> Sigue 100% el template mailbox.html sin inventar</li>
                        <li><strong>Compacto:</strong> Muestra más tickets en pantalla, ideal para listas largas</li>
                        <li><strong>Familiar:</strong> Los usuarios están acostumbrados a este diseño (Gmail, Outlook)</li>
                        <li><strong>Diferenciación clara:</strong> USER no ve columna de nombre, AGENT/ADMIN sí la ven</li>
                        <li><strong>Performance:</strong> Tabla HTML simple, rápido de renderizar</li>
                    </ul>

                    <div class="alert alert-info mt-3">
                        <strong><i class="fas fa-info-circle"></i> Columnas finales:</strong><br>
                        <strong>USER:</strong> Checkbox | Estrella | [Badge Status] Código - Título | Adjunto | Fecha<br>
                        <strong>AGENT/ADMIN:</strong> Checkbox | Estrella | Nombre Creador | [Badge Status] Código - Título | Adjunto | Fecha
                    </div>

                    <div class="alert alert-warning mt-3">
                        <strong>Opción 2</strong> es buena si necesitas mostrar más metadata (categoría, agente asignado) sin clicks.<br>
                        <strong>Opción 3</strong> es visual pero ocupa mucho espacio, mejor para dashboards que listas extensas.
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap -->
    <script src="https://cdn.jsdelivr.net.com/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>
