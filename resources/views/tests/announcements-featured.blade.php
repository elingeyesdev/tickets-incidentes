@extends('adminlte::page')

@section('title', 'Anuncios - Destacados')

@section('content_header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Centro de Novedades</h1>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="container-fluid">
        
        <!-- Hero Carousel for Featured Items -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Destacados</h3>
                    </div>
                    <div class="card-body p-0">
                        <div id="carouselExampleIndicators" class="carousel slide" data-ride="carousel">
                            <ol class="carousel-indicators">
                                <li data-target="#carouselExampleIndicators" data-slide-to="0" class="active"></li>
                                <li data-target="#carouselExampleIndicators" data-slide-to="1"></li>
                                <li data-target="#carouselExampleIndicators" data-slide-to="2"></li>
                            </ol>
                            <div class="carousel-inner">
                                <div class="carousel-item active">
                                    <div style="height: 300px; background-color: #343a40; color: white; display: flex; align-items: center; justify-content: center; flex-direction: column; text-align: center; padding: 20px;">
                                        <i class="fas fa-rocket fa-4x mb-3 text-primary"></i>
                                        <h2>Nueva Versión 2.0 Disponible</h2>
                                        <p class="lead">Descubre las nuevas herramientas que hemos preparado para ti.</p>
                                        <a href="#" class="btn btn-primary mt-2">Ver Novedades</a>
                                    </div>
                                </div>
                                <div class="carousel-item">
                                    <div style="height: 300px; background-color: #dc3545; color: white; display: flex; align-items: center; justify-content: center; flex-direction: column; text-align: center; padding: 20px;">
                                        <i class="fas fa-exclamation-circle fa-4x mb-3"></i>
                                        <h2>Alerta de Seguridad</h2>
                                        <p class="lead">Importante actualización de seguridad requerida para todos los usuarios.</p>
                                        <a href="#" class="btn btn-outline-light mt-2">Leer Más</a>
                                    </div>
                                </div>
                                <div class="carousel-item">
                                    <div style="height: 300px; background-color: #17a2b8; color: white; display: flex; align-items: center; justify-content: center; flex-direction: column; text-align: center; padding: 20px;">
                                        <i class="fas fa-calendar-alt fa-4x mb-3"></i>
                                        <h2>Webinar Mensual</h2>
                                        <p class="lead">Únete a nosotros este viernes para aprender tips avanzados.</p>
                                        <a href="#" class="btn btn-outline-light mt-2">Registrarse</a>
                                    </div>
                                </div>
                            </div>
                            <a class="carousel-control-prev" href="#carouselExampleIndicators" role="button" data-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="sr-only">Previous</span>
                            </a>
                            <a class="carousel-control-next" href="#carouselExampleIndicators" role="button" data-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="sr-only">Next</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabbed List for All Announcements -->
        <div class="row">
            <div class="col-12">
                <div class="card card-primary card-tabs">
                    <div class="card-header p-0 pt-1">
                        <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="custom-tabs-one-home-tab" data-toggle="pill" href="#custom-tabs-one-home" role="tab" aria-controls="custom-tabs-one-home" aria-selected="true">Todos</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="custom-tabs-one-alerts-tab" data-toggle="pill" href="#custom-tabs-one-alerts" role="tab" aria-controls="custom-tabs-one-alerts" aria-selected="false">Alertas <span class="badge badge-danger right">1</span></a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="custom-tabs-one-news-tab" data-toggle="pill" href="#custom-tabs-one-news" role="tab" aria-controls="custom-tabs-one-news" aria-selected="false">Noticias</a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="custom-tabs-one-tabContent">
                            <div class="tab-pane fade show active" id="custom-tabs-one-home" role="tabpanel" aria-labelledby="custom-tabs-one-home-tab">
                                <table class="table table-hover text-nowrap">
                                    <thead>
                                        <tr>
                                            <th>Empresa</th>
                                            <th>Título</th>
                                            <th>Tipo</th>
                                            <th>Fecha</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Acme Corp</td>
                                            <td>Interrupción de Servicio</td>
                                            <td><span class="badge badge-danger">Incidente</span></td>
                                            <td>22-11-2025</td>
                                            <td><a href="#" class="text-muted"><i class="fas fa-search"></i></a></td>
                                        </tr>
                                        <tr>
                                            <td>Tech Solutions</td>
                                            <td>Nueva Funcionalidad</td>
                                            <td><span class="badge badge-info">Noticia</span></td>
                                            <td>22-11-2025</td>
                                            <td><a href="#" class="text-muted"><i class="fas fa-search"></i></a></td>
                                        </tr>
                                        <tr>
                                            <td>Acme Corp</td>
                                            <td>Mantenimiento Programado</td>
                                            <td><span class="badge badge-warning">Mantenimiento</span></td>
                                            <td>21-11-2025</td>
                                            <td><a href="#" class="text-muted"><i class="fas fa-search"></i></a></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="tab-pane fade" id="custom-tabs-one-alerts" role="tabpanel" aria-labelledby="custom-tabs-one-alerts-tab">
                                <!-- Alerts Content -->
                                <div class="callout callout-danger">
                                    <h5>Interrupción de Servicio - Acme Corp</h5>
                                    <p>Estamos experimentando una interrupción parcial en nuestro servicio de procesamiento de pagos.</p>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="custom-tabs-one-news" role="tabpanel" aria-labelledby="custom-tabs-one-news-tab">
                                <!-- News Content -->
                                <div class="post">
                                    <div class="user-block">
                                        <img class="img-circle img-bordered-sm" src="https://ui-avatars.com/api/?name=Tech+Solutions&background=17a2b8&color=fff" alt="user image">
                                        <span class="username"><a href="#">Tech Solutions</a></span>
                                        <span class="description">Shared publicly - 7:30 PM today</span>
                                    </div>
                                    <p>Nueva Funcionalidad disponible...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /.card -->
                </div>
            </div>
        </div>
    </div>
@stop
