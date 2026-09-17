@extends('adminlte::page')

@section('title', 'Bitácora de Servicio')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1>Bitácora de Servicio</h1>
            <small class="text-muted">
                Patrulla {{ $patrulla->numero_economico }}
                @if($patrulla->descripcion_vehiculo)
                    — {{ $patrulla->descripcion_vehiculo }}
                @endif
            </small>
        </div>

        <div>
            @can('editar patrullas')
                <a href="{{ route('patrullas.bitacoras.edit', [
                    'patrulla' => $patrulla->id,
                    'bitacoraServicioPatrulla' => $bitacoraServicioPatrulla->id
                ]) }}"
                   class="btn btn-success">
                    <i class="fa-regular fa-pen-to-square"></i>
                    Editar
                </a>
            @endcan

            <a href="{{ route('patrullas.bitacoras.index', [
                'patrulla' => $patrulla->id
            ]) }}"
               class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i>
                Regresar
            </a>
        </div>
    </div>
@stop

@section('content')

    <div class="row">

        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-primary">
                    <i class="fa-solid fa-car-side"></i>
                </span>

                <div class="info-box-content">
                    <span class="info-box-text">
                        Patrulla
                    </span>

                    <span class="info-box-number">
                        {{ $patrulla->numero_economico }}
                    </span>
                </div>
            </div>
        </div>


        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-info">
                    <i class="fa-solid fa-list-check"></i>
                </span>

                <div class="info-box-content">
                    <span class="info-box-text">
                        Servicios
                    </span>

                    <span class="info-box-number">
                        {{ $totalServicios }}
                    </span>
                </div>
            </div>
        </div>


        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-success">
                    <i class="fa-solid fa-clipboard-check"></i>
                </span>

                <div class="info-box-content">
                    <span class="info-box-text">
                        Actividades
                    </span>

                    <span class="info-box-number">
                        {{ $totalActividades }}
                    </span>
                </div>
            </div>
        </div>


        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-warning">
                    <i class="fa-solid fa-car-burst"></i>
                </span>

                <div class="info-box-content">
                    <span class="info-box-text">
                        Hechos
                    </span>

                    <span class="info-box-number">
                        {{ $totalHechos }}
                    </span>
                </div>
            </div>
        </div>

    </div>


    <div class="row">
        <div class="col-md-12">

            <div class="card card-outline card-primary">

                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fa-solid fa-circle-info"></i>
                        Información del Servicio
                    </h3>
                </div>

                <div class="card-body">

                    <div class="row">

                        <div class="col-md-3">
                            <strong>Unidad</strong>
                            <p class="text-muted">
                                {{ $patrulla->unidad->nombre ?? '—' }}
                            </p>
                        </div>


                        <div class="col-md-3">
                            <strong>Turno</strong>
                            <p class="text-muted">
                                {{ $bitacoraServicioPatrulla->turno->nombre ?? '—' }}
                            </p>
                        </div>


                        <div class="col-md-3">
                            <strong>Fecha</strong>
                            <p class="text-muted">
                                {{ optional($bitacoraServicioPatrulla->fecha)->format('d-m-Y') ?? '—' }}
                            </p>
                        </div>


                        <div class="col-md-3">
                            <strong>Estado</strong>
                            <p>
                                @if($bitacoraServicioPatrulla->estatus === 'abierta')
                                    <span class="badge badge-success">
                                        <i class="fa-solid fa-circle-play"></i>
                                        Abierta
                                    </span>
                                @else
                                    <span class="badge badge-secondary">
                                        <i class="fa-solid fa-circle-check"></i>
                                        Cerrada
                                    </span>
                                @endif
                            </p>
                        </div>

                    </div>


                    <div class="row">

                        <div class="col-md-4">
                            <strong>Responsable</strong>

                            <p class="text-muted mb-1">
                                {{ $bitacoraServicioPatrulla->capturado_por_nombre ?? '—' }}
                            </p>

                            @if($bitacoraServicioPatrulla->capturadoPor)
                                <small class="text-muted">
                                    {{ $bitacoraServicioPatrulla->capturadoPor->email }}
                                </small>
                            @endif
                        </div>


                        <div class="col-md-4">
                            <strong>Inicio del Servicio</strong>

                            <p class="text-muted">
                                {{ $inicio->format('d-m-Y H:i') }}
                            </p>
                        </div>


                        <div class="col-md-4">
                            <strong>Fin del Servicio</strong>

                            <p class="text-muted">
                                @if($bitacoraServicioPatrulla->estatus === 'abierta')
                                    <span class="badge badge-info">
                                        Servicio en curso
                                    </span>
                                @else
                                    {{ $fin->format('d-m-Y H:i') }}
                                @endif
                            </p>
                        </div>

                    </div>

                </div>
            </div>

        </div>
    </div>


    <div class="row">

        <div class="col-md-6">

            <div class="card card-outline card-secondary h-100">

                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fa-solid fa-gauge-high"></i>
                        Kilometraje
                    </h3>
                </div>

                <div class="card-body">

                    <div class="row text-center">

                        <div class="col-md-4">
                            <strong>Inicial</strong>

                            <h5 class="mt-2">
                                @if(!is_null($bitacoraServicioPatrulla->kilometraje_inicio))
                                    {{ number_format($bitacoraServicioPatrulla->kilometraje_inicio) }}
                                    <small>km</small>
                                @else
                                    —
                                @endif
                            </h5>
                        </div>


                        <div class="col-md-4">
                            <strong>Final</strong>

                            <h5 class="mt-2">
                                @if(!is_null($bitacoraServicioPatrulla->kilometraje_fin))
                                    {{ number_format($bitacoraServicioPatrulla->kilometraje_fin) }}
                                    <small>km</small>
                                @else
                                    —
                                @endif
                            </h5>
                        </div>


                        <div class="col-md-4">
                            <strong>Recorrido</strong>

                            <h5 class="mt-2">
                                @if(!is_null($bitacoraServicioPatrulla->kilometros_recorridos))
                                    <strong>
                                        {{ number_format($bitacoraServicioPatrulla->kilometros_recorridos) }}
                                    </strong>
                                    <small>km</small>
                                @else
                                    —
                                @endif
                            </h5>
                        </div>

                    </div>

                </div>
            </div>

        </div>


        <div class="col-md-6">

            <div class="card card-outline card-warning h-100">

                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fa-solid fa-gas-pump"></i>
                        Combustible
                    </h3>
                </div>

                <div class="card-body">

                    <div class="row text-center">

                        <div class="col-md-6">
                            <strong>Inicio</strong>

                            <h5 class="mt-2">
                                @if(!is_null($bitacoraServicioPatrulla->combustible_inicio))
                                    {{ number_format((float) $bitacoraServicioPatrulla->combustible_inicio, 0) }}%
                                @else
                                    —
                                @endif
                            </h5>
                        </div>


                        <div class="col-md-6">
                            <strong>Fin</strong>

                            <h5 class="mt-2">
                                @if(!is_null($bitacoraServicioPatrulla->combustible_fin))
                                    {{ number_format((float) $bitacoraServicioPatrulla->combustible_fin, 0) }}%
                                @else
                                    —
                                @endif
                            </h5>
                        </div>

                    </div>

                </div>
            </div>

        </div>

    </div>


    @php
        $cronologia = collect();

        foreach($actividades as $actividad) {
            try {
                $fechaActividad = $actividad->fecha instanceof \DateTimeInterface
                    ? $actividad->fecha->format('Y-m-d')
                    : \Carbon\Carbon::parse($actividad->fecha)->format('Y-m-d');

                $momento = \Carbon\Carbon::parse(
                    $fechaActividad . ' ' . ($actividad->hora ?: '00:00:00')
                );
            } catch (\Throwable $e) {
                $momento = null;
            }

            $cronologia->push([
                'tipo' => 'actividad',
                'momento' => $momento,
                'registro' => $actividad,
            ]);
        }

        foreach($hechos as $hecho) {
            try {
                $fechaHecho = $hecho->fecha instanceof \DateTimeInterface
                    ? $hecho->fecha->format('Y-m-d')
                    : \Carbon\Carbon::parse($hecho->fecha)->format('Y-m-d');

                $momento = \Carbon\Carbon::parse(
                    $fechaHecho . ' ' . ($hecho->hora ?: '00:00:00')
                );
            } catch (\Throwable $e) {
                $momento = null;
            }

            $cronologia->push([
                'tipo' => 'hecho',
                'momento' => $momento,
                'registro' => $hecho,
            ]);
        }

        $cronologia = $cronologia
            ->sortBy(function($item) {
                return $item['momento']
                    ? $item['momento']->timestamp
                    : PHP_INT_MAX;
            })
            ->values();
    @endphp


    <div class="row mt-3">
        <div class="col-md-12">

            <div class="card card-outline card-dark">

                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        Cronología del Turno
                    </h3>

                    <div class="card-tools">
                        <span class="badge badge-primary">
                            {{ $totalServicios }} servicios
                        </span>
                    </div>
                </div>


                <div class="card-body">

                    @if($cronologia->isEmpty())

                        <div class="text-center text-muted py-4">

                            <i class="fa-solid fa-clipboard-list fa-3x mb-3"></i>

                            <h5>
                                Sin servicios registrados
                            </h5>

                            <p class="mb-0">
                                No se encontraron actividades ni hechos realizados
                                por el responsable durante este periodo.
                            </p>

                        </div>

                    @else

                        <div class="timeline">

                            @php
                                $ultimaFecha = null;
                            @endphp

                            @foreach($cronologia as $item)

                                @php
                                    $registro = $item['registro'];
                                    $momento = $item['momento'];

                                    $fechaActual = $momento
                                        ? $momento->format('Y-m-d')
                                        : null;
                                @endphp


                                @if($fechaActual && $fechaActual !== $ultimaFecha)

                                    <div class="time-label">
                                        <span class="bg-secondary">
                                            {{ $momento->format('d-m-Y') }}
                                        </span>
                                    </div>

                                    @php
                                        $ultimaFecha = $fechaActual;
                                    @endphp

                                @endif


                                @if($item['tipo'] === 'actividad')

                                    <div>

                                        <i class="fas fa-clipboard-check bg-success"></i>

                                        <div class="timeline-item">

                                            <span class="time">
                                                <i class="fas fa-clock"></i>

                                                {{ $momento
                                                    ? $momento->format('H:i')
                                                    : '—' }}
                                            </span>


                                            <h3 class="timeline-header">

                                                <span class="badge badge-success mr-2">
                                                    ACTIVIDAD
                                                </span>

                                                {{ $registro->nombre
                                                    ?: ($registro->categoria->nombre ?? 'Actividad') }}

                                            </h3>


                                            <div class="timeline-body">

                                                <div class="row">

                                                    <div class="col-md-4">
                                                        <strong>Categoría</strong>

                                                        <p class="mb-2">
                                                            {{ $registro->categoria->nombre ?? '—' }}
                                                        </p>
                                                    </div>


                                                    <div class="col-md-4">
                                                        <strong>Subcategoría</strong>

                                                        <p class="mb-2">
                                                            {{ $registro->subcategoria->nombre ?? '—' }}
                                                        </p>
                                                    </div>


                                                    <div class="col-md-4">
                                                        <strong>Cantidad</strong>

                                                        <p class="mb-2">
                                                            {{ $registro->cantidad ?? '—' }}
                                                        </p>
                                                    </div>

                                                </div>


                                                @if($registro->folio_c5i)

                                                    <div class="mb-2">
                                                        <strong>Folio C5i:</strong>
                                                        {{ $registro->folio_c5i }}
                                                    </div>

                                                @endif


                                                @if(
                                                    $registro->lugar
                                                    || $registro->municipio
                                                )

                                                    <div class="mb-2">
                                                        <strong>Ubicación:</strong>

                                                        {{ collect([
                                                            $registro->lugar,
                                                            $registro->municipio
                                                        ])->filter()->implode(', ') }}
                                                    </div>

                                                @endif


                                                @if($registro->motivo)

                                                    <div class="mb-2">
                                                        <strong>Motivo:</strong>
                                                        {{ $registro->motivo }}
                                                    </div>

                                                @endif


                                                @if($registro->acciones_realizadas)

                                                    <div class="mb-2">
                                                        <strong>Acciones realizadas:</strong>

                                                        <div class="texto-servicio">
                                                            {!! nl2br(e($registro->acciones_realizadas)) !!}
                                                        </div>
                                                    </div>

                                                @elseif($registro->narrativa)

                                                    <div class="mb-2">
                                                        <strong>Narrativa:</strong>

                                                        <div class="texto-servicio">
                                                            {!! nl2br(e($registro->narrativa)) !!}
                                                        </div>
                                                    </div>

                                                @endif

                                            </div>

                                        </div>

                                    </div>


                                @else

                                    <div>

                                        <i class="fas fa-car-burst bg-warning"></i>

                                        <div class="timeline-item">

                                            <span class="time">
                                                <i class="fas fa-clock"></i>

                                                {{ $momento
                                                    ? $momento->format('H:i')
                                                    : '—' }}
                                            </span>


                                            <h3 class="timeline-header">

                                                <span class="badge badge-warning mr-2">
                                                    HECHO
                                                </span>

                                                {{ $registro->tipo_hecho ?: 'Hecho de tránsito' }}

                                            </h3>


                                            <div class="timeline-body">

                                                <div class="row">

                                                    <div class="col-md-4">
                                                        <strong>Folio C5i</strong>

                                                        <p class="mb-2">
                                                            {{ $registro->folio_c5i ?? '—' }}
                                                        </p>
                                                    </div>


                                                    <div class="col-md-4">
                                                        <strong>Municipio</strong>

                                                        <p class="mb-2">
                                                            {{ $registro->municipio ?? '—' }}
                                                        </p>
                                                    </div>


                                                    <div class="col-md-4">
                                                        <strong>Sector</strong>

                                                        <p class="mb-2">
                                                            {{ $registro->sector ?? '—' }}
                                                        </p>
                                                    </div>

                                                </div>


                                                @if(
                                                    $registro->calle
                                                    || $registro->colonia
                                                    || $registro->entre_calles
                                                )

                                                    <div class="mb-2">
                                                        <strong>Ubicación:</strong>

                                                        {{ collect([
                                                            $registro->calle,
                                                            $registro->colonia,
                                                            $registro->entre_calles
                                                        ])->filter()->implode(', ') }}
                                                    </div>

                                                @endif


                                                @if($registro->situacion)

                                                    <div class="mb-2">
                                                        <strong>Situación:</strong>

                                                        <div class="texto-servicio">
                                                            {!! nl2br(e($registro->situacion)) !!}
                                                        </div>
                                                    </div>

                                                @endif


                                                @if($registro->causas)

                                                    <div class="mb-2">
                                                        <strong>Causas:</strong>

                                                        <div class="texto-servicio">
                                                            {!! nl2br(e($registro->causas)) !!}
                                                        </div>
                                                    </div>

                                                @endif

                                            </div>

                                        </div>

                                    </div>

                                @endif

                            @endforeach


                            <div>
                                <i class="fas fa-flag-checkered bg-gray"></i>
                            </div>

                        </div>

                    @endif

                </div>
            </div>

        </div>
    </div>


    <div class="row">

        <div class="col-md-6">

            <div class="card card-outline card-success">

                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fa-solid fa-clipboard-check"></i>
                        Actividades
                    </h3>

                    <div class="card-tools">
                        <span class="badge badge-success">
                            {{ $totalActividades }}
                        </span>
                    </div>
                </div>


                <div class="card-body p-0">

                    <div class="table-responsive">

                        <table class="table table-striped table-hover mb-0">

                            <thead>
                                <tr>
                                    <th>Hora</th>
                                    <th>Actividad</th>
                                    <th>Lugar</th>
                                    <th>Cantidad</th>
                                </tr>
                            </thead>

                            <tbody>

                                @forelse($actividades as $actividad)

                                    <tr>

                                        <td>
                                            {{ $actividad->hora
                                                ? substr($actividad->hora, 0, 5)
                                                : '—' }}
                                        </td>

                                        <td>
                                            <strong>
                                                {{ $actividad->nombre
                                                    ?: ($actividad->categoria->nombre ?? 'Actividad') }}
                                            </strong>

                                            @if($actividad->subcategoria)
                                                <br>
                                                <small class="text-muted">
                                                    {{ $actividad->subcategoria->nombre }}
                                                </small>
                                            @endif
                                        </td>

                                        <td>
                                            {{ $actividad->lugar
                                                ?: ($actividad->municipio ?? '—') }}
                                        </td>

                                        <td>
                                            {{ $actividad->cantidad ?? '—' }}
                                        </td>

                                    </tr>

                                @empty

                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-3">
                                            Sin actividades registradas.
                                        </td>
                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>
            </div>

        </div>


        <div class="col-md-6">

            <div class="card card-outline card-warning">

                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fa-solid fa-car-burst"></i>
                        Hechos
                    </h3>

                    <div class="card-tools">
                        <span class="badge badge-warning">
                            {{ $totalHechos }}
                        </span>
                    </div>
                </div>


                <div class="card-body p-0">

                    <div class="table-responsive">

                        <table class="table table-striped table-hover mb-0">

                            <thead>
                                <tr>
                                    <th>Hora</th>
                                    <th>Tipo</th>
                                    <th>Folio C5i</th>
                                    <th>Lugar</th>
                                </tr>
                            </thead>

                            <tbody>

                                @forelse($hechos as $hecho)

                                    <tr>

                                        <td>
                                            {{ $hecho->hora
                                                ? substr($hecho->hora, 0, 5)
                                                : '—' }}
                                        </td>

                                        <td>
                                            <strong>
                                                {{ $hecho->tipo_hecho ?? '—' }}
                                            </strong>
                                        </td>

                                        <td>
                                            {{ $hecho->folio_c5i ?? '—' }}
                                        </td>

                                        <td>
                                            {{ $hecho->calle
                                                ?: ($hecho->municipio ?? '—') }}
                                        </td>

                                    </tr>

                                @empty

                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-3">
                                            Sin hechos registrados.
                                        </td>
                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>
            </div>

        </div>

    </div>


    <div class="row">

        <div class="col-md-12">

            <div class="card card-outline card-secondary">

                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fa-regular fa-note-sticky"></i>
                        Observaciones
                    </h3>
                </div>

                <div class="card-body">

                    @if($bitacoraServicioPatrulla->observaciones)

                        {!! nl2br(e($bitacoraServicioPatrulla->observaciones)) !!}

                    @else

                        <span class="text-muted">
                            Sin observaciones registradas.
                        </span>

                    @endif

                </div>
            </div>

        </div>

    </div>


    <div class="row">

        <div class="col-md-12">

            <div class="card card-outline card-light">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center">

                        <div class="text-muted">
                            Bitácora #{{ $bitacoraServicioPatrulla->id }}
                        </div>

                        <div>

                            @can('editar patrullas')

                                <a href="{{ route('patrullas.bitacoras.edit', [
                                    'patrulla' => $patrulla->id,
                                    'bitacoraServicioPatrulla' => $bitacoraServicioPatrulla->id
                                ]) }}"
                                   class="btn btn-success">

                                    <i class="fa-regular fa-pen-to-square"></i>
                                    Editar Bitácora

                                </a>

                            @endcan


                            <a href="{{ route('patrullas.bitacoras.index', [
                                'patrulla' => $patrulla->id
                            ]) }}"
                               class="btn btn-secondary">

                                <i class="fa-solid fa-arrow-left"></i>
                                Volver al Historial

                            </a>

                        </div>

                    </div>

                </div>
            </div>

        </div>

    </div>

@stop


@section('css')

    <style>

        .info-box-number {
            font-size: 1.15rem;
        }

        .timeline-header {
            font-size: 1rem !important;
        }

        .timeline-body {
            font-size: .95rem;
        }

        .texto-servicio {
            white-space: pre-line;
            color: #495057;
        }

        .table th,
        .table td {
            vertical-align: middle;
        }

        .badge {
            font-size: 90%;
        }

    </style>

@stop


@section('js')

    @if(session('success'))

        <script>

            Swal.fire({
                position: 'center',
                icon: 'success',
                title: @json(session('success')),
                showConfirmButton: false,
                timer: 4000
            });

        </script>

    @endif

@stop
