@extends('layouts.authenticated')

@section('title', 'Tickets')

@section('content_header', 'Sistema de Tickets')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Tickets</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-3">
            @if($role === 'USER')
                <a href="#" class="btn btn-primary btn-block mb-3" data-toggle="modal" data-target="#createTicketModal">Crear Nuevo Ticket</a>
            @endif

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Folders</h3>

                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <ul class="nav nav-pills flex-column">
                        @if($role === 'USER')
                            <li class="nav-item active">
                                <a href="#" class="nav-link" data-filter="">
                                    <i class="fas fa-inbox"></i> All Tickets
                                    <span class="badge bg-primary float-right">25</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link" data-filter="last_response_author_type=user">
                                    <i class="far fa-clock"></i> Awaiting Support
                                    <span class="badge bg-warning float-right">8</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link" data-filter="status=resolved">
                                    <i class="far fa-check-circle"></i> Resolved
                                    <span class="badge bg-success float-right">12</span>
                                </a>
                            </li>
                        @elseif($role === 'AGENT')
                            <li class="nav-item active">
                                <a href="#" class="nav-link" data-filter="">
                                    <i class="fas fa-inbox"></i> All Tickets
                                    <span class="badge bg-primary float-right">208</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link" data-filter="last_response_author_type=none">
                                    <i class="fas fa-bell"></i> New Tickets
                                    <span class="badge bg-danger float-right">18</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link" data-filter="owner_agent_id=null">
                                    <i class="fas fa-user-slash"></i> Unassigned
                                    <span class="badge bg-warning float-right">32</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link" data-filter="owner_agent_id=me">
                                    <i class="fas fa-user-check"></i> My Assigned
                                    <span class="badge bg-info float-right">15</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link" data-filter="owner_agent_id=me&last_response_author_type=user">
                                    <i class="far fa-comments"></i> Awaiting My Response
                                    <span class="badge bg-success float-right">7</span>
                                </a>
                            </li>
                        @elseif($role === 'COMPANY_ADMIN')
                            <li class="nav-item active">
                                <a href="#" class="nav-link" data-filter="">
                                    <i class="fas fa-inbox"></i> All Tickets
                                    <span class="badge bg-primary float-right">208</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link" data-filter="owner_agent_id=null">
                                    <i class="fas fa-user-slash"></i> Unassigned
                                    <span class="badge bg-danger float-right">32</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        @if($role === 'USER')
                            My Ticket Status
                        @else
                            Statuses
                        @endif
                    </h3>

                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <ul class="nav nav-pills flex-column">
                        <li class="nav-item">
                            <a href="#" class="nav-link" data-filter="status=open">
                                <i class="far fa-circle text-danger"></i> Open
                                <span class="badge bg-danger float-right">15</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link" data-filter="status=pending">
                                <i class="far fa-circle text-warning"></i> Pending
                                <span class="badge bg-warning float-right">23</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link" data-filter="status=resolved">
                                <i class="far fa-circle text-success"></i> Resolved
                                <span class="badge bg-success float-right">42</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link" data-filter="status=closed">
                                <i class="far fa-circle text-secondary"></i> Closed
                                <span class="badge bg-secondary float-right">96</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

        </div>

        <div class="col-md-9">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        @if($role === 'USER')
                            Mis Tickets
                        @elseif($role === 'AGENT')
                            Todos los Tickets
                        @else
                            Gestión de Tickets
                        @endif
                    </h3>

                    <div class="card-tools">
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" placeholder="Search Ticket">
                            <div class="input-group-append">
                                <div class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="mailbox-controls">
                        @if($role === 'AGENT' || $role === 'COMPANY_ADMIN')
                            <button type="button" class="btn btn-default btn-sm checkbox-toggle"><i class="far fa-square"></i></button>
                        @endif
                        @if($role === 'AGENT')
                            <div class="btn-group">
                                <button type="button" class="btn btn-default btn-sm">
                                    <i class="far fa-trash-alt"></i>
                                </button>
                                <button type="button" class="btn btn-default btn-sm">
                                    <i class="fas fa-reply"></i>
                                </button>
                                <button type="button" class="btn btn-default btn-sm">
                                    <i class="fas fa-share"></i>
                                </button>
                            </div>
                        @endif
                        <button type="button" class="btn btn-default btn-sm">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <div class="float-right">
                            1-50/200
                            <div class="btn-group">
                                <button type="button" class="btn btn-default btn-sm">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <button type="button" class="btn btn-default btn-sm">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive mailbox-messages">
                        <table class="table table-hover table-striped">
                            <tbody>
                            <tr>
                                @if($role === 'AGENT' || $role === 'COMPANY_ADMIN')
                                    <td>
                                        <div class="icheck-primary">
                                            <input type="checkbox" value="" id="check1">
                                            <label for="check1"></label>
                                        </div>
                                    </td>
                                @endif
                                <td class="mailbox-star"><a href="#"><i class="fas fa-star text-warning"></i></a></td>
                                <td class="mailbox-name"><a href="read-mail.html">Juan Pérez</a></td>
                                <td class="mailbox-subject"><span class="badge badge-info">New</span> <b>TKT-2025-00001</b> - Solicitud de acceso al módulo de ventas
                                </td>
                                <td class="mailbox-attachment"></td>
                                <td class="mailbox-date">Hace 2 min</td>
                            </tr>
                            <tr>
                                @if($role === 'AGENT' || $role === 'COMPANY_ADMIN')
                                    <td>
                                        <div class="icheck-primary">
                                            <input type="checkbox" value="" id="check2">
                                            <label for="check2"></label>
                                        </div>
                                    </td>
                                @endif
                                <td class="mailbox-star"><a href="#"><i class="far fa-star"></i></a></td>
                                <td class="mailbox-name"><a href="read-mail.html">María González</a></td>
                                <td class="mailbox-subject"><span class="badge badge-danger">Open</span> <b>TKT-2025-00002</b> - Error crítico en módulo de reportes
                                </td>
                                <td class="mailbox-attachment"><i class="fas fa-paperclip"></i></td>
                                <td class="mailbox-date">Hace 15 min</td>
                            </tr>
                            <tr>
                                @if($role === 'AGENT' || $role === 'COMPANY_ADMIN')
                                    <td>
                                        <div class="icheck-primary">
                                            <input type="checkbox" value="" id="check3">
                                            <label for="check3"></label>
                                        </div>
                                    </td>
                                @endif
                                <td class="mailbox-star"><a href="#"><i class="far fa-star"></i></a></td>
                                <td class="mailbox-name"><a href="read-mail.html">Carlos Rodríguez</a></td>
                                <td class="mailbox-subject"><span class="badge badge-warning">Pending</span> <b>TKT-2025-00003</b> - No puedo acceder al dashboard
                                </td>
                                <td class="mailbox-attachment"></td>
                                <td class="mailbox-date">Hace 1 hora</td>
                            </tr>
                            <tr>
                                @if($role === 'AGENT' || $role === 'COMPANY_ADMIN')
                                    <td>
                                        <div class="icheck-primary">
                                            <input type="checkbox" value="" id="check4">
                                            <label for="check4"></label>
                                        </div>
                                    </td>
                                @endif
                                <td class="mailbox-star"><a href="#"><i class="fas fa-star text-warning"></i></a></td>
                                <td class="mailbox-name"><a href="read-mail.html">Ana Martínez</a></td>
                                <td class="mailbox-subject"><span class="badge badge-success">Resolved</span> <b>TKT-2025-00004</b> - Consulta sobre facturación
                                </td>
                                <td class="mailbox-attachment"><i class="fas fa-paperclip"></i></td>
                                <td class="mailbox-date">Hace 3 horas</td>
                            </tr>
                            <tr>
                                @if($role === 'AGENT' || $role === 'COMPANY_ADMIN')
                                    <td>
                                        <div class="icheck-primary">
                                            <input type="checkbox" value="" id="check5">
                                            <label for="check5"></label>
                                        </div>
                                    </td>
                                @endif
                                <td class="mailbox-star"><a href="#"><i class="fas fa-star text-warning"></i></a></td>
                                <td class="mailbox-name"><a href="read-mail.html">Luis Fernández</a></td>
                                <td class="mailbox-subject"><span class="badge badge-secondary">Closed</span> <b>TKT-2025-00005</b> - Configuración de permisos
                                </td>
                                <td class="mailbox-attachment"></td>
                                <td class="mailbox-date">Ayer</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-footer p-0">
                    <div class="mailbox-controls">
                        @if($role === 'AGENT' || $role === 'COMPANY_ADMIN')
                            <button type="button" class="btn btn-default btn-sm checkbox-toggle">
                                <i class="far fa-square"></i>
                            </button>
                        @endif
                        @if($role === 'AGENT')
                            <div class="btn-group">
                                <button type="button" class="btn btn-default btn-sm">
                                    <i class="far fa-trash-alt"></i>
                                </button>
                                <button type="button" class="btn btn-default btn-sm">
                                    <i class="fas fa-reply"></i>
                                </button>
                                <button type="button" class="btn btn-default btn-sm">
                                    <i class="fas fa-share"></i>
                                </button>
                            </div>
                        @endif
                        <button type="button" class="btn btn-default btn-sm">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <div class="float-right">
                            1-50/200
                            <div class="btn-group">
                                <button type="button" class="btn btn-default btn-sm">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <button type="button" class="btn btn-default btn-sm">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Crear Nuevo Ticket -->
    @if($role === 'USER')
        <div class="modal fade" id="createTicketModal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Crear Nuevo Ticket</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form>
                            <div class="form-group">
                                <label for="ticketTitle">Título</label>
                                <input type="text" class="form-control" id="ticketTitle" placeholder="Ej: Error al exportar reporte">
                            </div>
                            <div class="form-group">
                                <label for="ticketCategory">Categoría</label>
                                <select class="form-control" id="ticketCategory">
                                    <option>Soporte Técnico</option>
                                    <option>Facturación</option>
                                    <option>Consulta General</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="ticketDescription">Descripción</label>
                                <textarea class="form-control" id="ticketDescription" rows="4" placeholder="Describe tu problema..."></textarea>
                            </div>
                            <div class="form-group">
                                <label for="ticketAttachment">Adjuntos</label>
                                <div class="input-group">
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="ticketAttachment">
                                        <label class="custom-file-label" for="ticketAttachment">Seleccionar archivo</label>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary">Crear Ticket</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('js')
<script>
  $(function () {
    //Enable check and uncheck all functionality
    $('.checkbox-toggle').click(function () {
      var clicks = $(this).data('clicks')
      if (clicks) {
        //Uncheck all checkboxes
        $('.mailbox-messages input[type=\'checkbox\']').prop('checked', false)
        $('.checkbox-toggle .far.fa-check-square').removeClass('fa-check-square').addClass('fa-square')
      } else {
        //Check all checkboxes
        $('.mailbox-messages input[type=\'checkbox\']').prop('checked', true)
        $('.checkbox-toggle .far.fa-square').removeClass('fa-square').addClass('fa-check-square')
      }
      $(this).data('clicks', !clicks)
    })

    //Handle starring for mailbox
    $('.mailbox-star').click(function (e) {
      e.preventDefault()
      //detect type
      var $this = $(this).find('a > i')
      var fa    = $this.hasClass('fa')

      //Switch states
      if (fa) {
        $this.toggleClass('fa-star')
        $this.toggleClass('fa-star-o')
      }
    })

    //Initialize Select2 for category filter
    $('#categoryFilter').select2({
      theme: 'bootstrap4',
      placeholder: 'Todas las Categorías',
      allowClear: true
    })
  })
</script>
@endsection
