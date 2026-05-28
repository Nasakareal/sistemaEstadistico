@extends('adminlte::page')

@section('title', 'Servicios Personal Fomento')

@section('content_header')
    <div class="sv-hero">
        <div class="sv-hero__inner">
            <div class="sv-hero__badge">
                <span class="sv-dot"></span>
                <span>Fomento · Servicios · {{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}</span>
            </div>

            <div class="sv-hero__title">
                Servicios por personal
            </div>

            <div class="sv-hero__subtitle">
                Ranking de incidencias tipo servicio registradas para personal de Fomento
            </div>
        </div>
    </div>
@stop

@section('content')
    @if($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="row">
        <div class="col-md-3 col-sm-6 col-12">
            <div class="sv-kpi">
                <div class="sv-kpi__icon bg-info">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div>
                    <div class="sv-kpi__label">Personal listado</div>
                    <div class="sv-kpi__value">{{ number_format($totales['personal']) }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 col-12">
            <div class="sv-kpi">
                <div class="sv-kpi__icon bg-success">
                    <i class="fa-solid fa-clipboard-check"></i>
                </div>
                <div>
                    <div class="sv-kpi__label">Servicios</div>
                    <div class="sv-kpi__value">{{ number_format($totales['servicios']) }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 col-12">
            <div class="sv-kpi">
                <div class="sv-kpi__icon bg-primary">
                    <i class="fa-solid fa-calendar-day"></i>
                </div>
                <div>
                    <div class="sv-kpi__label">Días servicio</div>
                    <div class="sv-kpi__value">{{ number_format($totales['dias_servicio']) }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 col-12">
            <div class="sv-kpi">
                <div class="sv-kpi__icon bg-warning">
                    <i class="fa-solid fa-calendar-week"></i>
                </div>
                <div>
                    <div class="sv-kpi__label">Días fin de semana</div>
                    <div class="sv-kpi__value">{{ number_format($totales['dias_fin_semana']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="sv-panel">
        <div class="sv-panel__body sv-panel__body--header">
            <form method="GET" action="{{ route('settings.estadisticas_fomento.servicios_personal') }}" class="sv-inline-form">
                <div class="sv-inline-field">
                    <label for="fecha_inicio">Desde</label>
                    <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" value="{{ $fechaInicio }}">
                </div>

                <div class="sv-inline-field">
                    <label for="fecha_fin">Hasta</label>
                    <input type="date" name="fecha_fin" id="fecha_fin" class="form-control" value="{{ $fechaFin }}">
                </div>

                <button type="submit" class="btn sv-btn">
                    <i class="fa-solid fa-filter"></i> Consultar
                </button>
            </form>

            <div class="sv-toolbar">
                <a href="{{ route('settings.estadisticas_fomento.index') }}" class="btn sv-btn">
                    <i class="fas fa-arrow-left"></i> Regresar
                </a>
            </div>
        </div>
    </div>

    <div class="sv-panel">
        <div class="sv-panel__title">
            <i class="fa-solid fa-ranking-star"></i>
            <span>Listado de personal</span>
        </div>

        <div class="table-responsive sv-table-wrap">
            <table id="fomentoServiciosPersonal" class="table table-sm table-hover mb-0">
                <thead>
                    <tr>
                        <th>Pos.</th>
                        <th>No. empleado</th>
                        <th>Nombre</th>
                        <th>Puesto</th>
                        <th>Turno</th>
                        <th>Estatus</th>
                        <th>Servicios</th>
                        <th>Días servicio</th>
                        <th>Días fin de semana</th>
                        <th>Último servicio</th>
                        <th>Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $index => $row)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $row['numero_empleado'] ?? 'N/A' }}</td>
                            <td class="text-left">{{ $row['nombre_completo'] ?: 'N/A' }}</td>
                            <td>{{ $row['puesto'] ?? 'N/A' }}</td>
                            <td>
                                {{ $row['turno'] ?? 'Sin turno' }}
                                @if(!empty($row['turno_tipo_rol']))
                                    <br><small class="text-muted">{{ $row['turno_tipo_rol'] }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-{{ strtoupper((string) $row['estatus']) === 'ACTIVO' ? 'success' : 'secondary' }}">
                                    {{ $row['estatus'] ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="font-weight-bold">{{ number_format($row['total_servicios']) }}</td>
                            <td>{{ number_format($row['dias_servicio']) }}</td>
                            <td>{{ number_format($row['dias_fin_semana']) }}</td>
                            <td>{{ $row['ultimo_servicio'] ? \Carbon\Carbon::parse($row['ultimo_servicio'])->format('d/m/Y') : 'N/A' }}</td>
                            <td>
                                @can('ver personal')
                                    <a href="{{ route('personal.show', $row['id']) }}" class="btn sv-btn sv-btn--mini" title="Ver expediente">
                                        <i class="fa-regular fa-eye"></i>
                                    </a>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center py-4">No hay personal de Fomento registrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop

@section('css')
<link rel="stylesheet" href="{{ asset('css/sv-dashboard.css') }}">
<style>
    .sv-panel__body--header{
        display:flex;
        justify-content:space-between;
        align-items:end;
        gap:12px;
        flex-wrap:wrap;
    }

    .sv-inline-form,
    .sv-toolbar{
        display:flex;
        align-items:end;
        gap:10px;
        flex-wrap:wrap;
    }

    .sv-inline-field label{
        display:block;
        margin-bottom:5px;
        font-weight:900;
        font-size:12px;
        color:var(--sv-muted);
    }

    .sv-inline-field .form-control{
        min-width:150px;
        color:var(--sv-text);
        background:rgba(12,16,28,.65);
        border:1px solid rgba(255,255,255,.16);
        border-radius:12px;
    }

    .sv-table-wrap{
        padding:0 12px 12px;
    }

    #fomentoServiciosPersonal th,
    #fomentoServiciosPersonal td{
        vertical-align:middle;
        text-align:center;
    }

    #fomentoServiciosPersonal th{
        white-space:nowrap;
    }

    .sv-btn--mini{
        width:36px;
        height:34px;
        padding:0;
    }

    @media (max-width: 768px){
        .sv-panel__body--header{
            align-items:stretch;
        }

        .sv-inline-form,
        .sv-toolbar,
        .sv-inline-field,
        .sv-inline-field .form-control,
        .sv-inline-form .btn,
        .sv-toolbar .btn{
            width:100%;
        }
    }
</style>
@stop

@section('js')
<script>
    $(function () {
        $('#fomentoServiciosPersonal').DataTable({
            pageLength: 25,
            order: [[6, 'desc'], [7, 'desc'], [2, 'asc']],
            language: {
                emptyTable: 'No hay información',
                info: 'Mostrando _START_ a _END_ de _TOTAL_ elementos',
                infoEmpty: 'Mostrando 0 a 0 de 0 elementos',
                infoFiltered: '(Filtrado de _MAX_ total elementos)',
                lengthMenu: 'Mostrar _MENU_ elementos',
                loadingRecords: 'Cargando...',
                processing: 'Procesando...',
                search: 'Buscador:',
                zeroRecords: 'Sin resultados encontrados',
                paginate: {
                    first: 'Primero',
                    last: 'Último',
                    next: 'Siguiente',
                    previous: 'Anterior'
                }
            },
            responsive: true,
            lengthChange: true,
            autoWidth: false,
            buttons: [
                {
                    extend: 'collection',
                    text: 'Opciones',
                    buttons: [
                        { extend: 'copy', text: 'Copiar' },
                        { extend: 'csv', text: 'CSV' },
                        { extend: 'excel', text: 'Excel' },
                        { extend: 'pdf', text: 'PDF' },
                        { extend: 'print', text: 'Imprimir' }
                    ]
                },
                { extend: 'colvis', text: 'Columnas' }
            ]
        }).buttons().container().appendTo('#fomentoServiciosPersonal_wrapper .col-md-6:eq(0)');
    });
</script>
@stop
