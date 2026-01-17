@extends('adminlte::page')

@section('title', 'Bitácora')

@section('content_header')
    <h1>Bitácora - {{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}</h1>
@stop

@section('content')
    <form method="GET" action="{{ route('estadisticas.bitacora') }}" class="mb-3">
        <div class="form-inline">
            <label for="fecha" class="mr-2">Selecciona la fecha:</label>
            <input type="date" name="fecha" id="fecha" class="form-control mr-2" value="{{ $fecha }}">
            <button type="submit" class="btn btn-primary">Consultar</button>
            <a href="{{ route('estadisticas.bitacora.descargar', ['fecha' => $fecha]) }}" class="btn btn-success ml-2">
                <i class="fas fa-file-word"></i> Descargar Word
            </a>
        </div>
    </form>

    <div class="card">
        <div class="card-header">
            <strong>Hechos registrados entre {{ \Carbon\Carbon::parse($fecha)->subDay()->setTime(18, 0)->format('d/m/Y H:i') }}
            y {{ \Carbon\Carbon::parse($fecha)->setTime(18, 0)->format('d/m/Y H:i') }}</strong>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm w-100">
                    <thead class="thead-light">
                        <tr>
                            <th>N°</th>
                            <th>HORA DE SALIDA</th>
                            <th>UNIDAD</th>
                            <th>PERITO(S) NOMBRE</th>
                            <th>LUGAR DE LOS HECHOS</th>
                            <th>GRUA</th>
                            <th>PERSONAS LESIONADAS</th>
                            <th>TIPO DE HECHO</th>
                            <th>OBSERVACIÓN / ESTATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($hechos as $i => $hecho)
                            @php
                                $hora = '';
                                if (!empty($hecho->hora)) {
                                    $hora = \Carbon\Carbon::parse($hecho->hora)->format('H:i');
                                } elseif (!empty($hecho->created_at)) {
                                    $hora = $hecho->created_at->format('H:i');
                                }

                                $unidad = (string)($hecho->unidad ?? '');

                                $perito = strtoupper((string)($hecho->perito ?? ''));

                                $lugar = trim((string)($hecho->calle ?? ''));
                                if (!empty($hecho->colonia))   $lugar .= ($lugar !== '' ? ', ' : '') . 'COL. ' . $hecho->colonia;
                                if (!empty($hecho->municipio)) $lugar .= ($lugar !== '' ? ', ' : '') . $hecho->municipio;

                                // GRUA (si algún vehículo trae grúa diferente de N/A)
                                $grua = 'NO';
                                if (isset($hecho->vehiculos) && $hecho->vehiculos->count() > 0) {
                                    $vConGrua = $hecho->vehiculos->first(function ($v) {
                                        return $v->grua !== null && trim((string)$v->grua) !== '' && strtolower(trim((string)$v->grua)) !== 'n/a';
                                    });
                                    if ($vConGrua) $grua = strtoupper(trim((string)$vConGrua->grua));
                                }

                                // Lesionados
                                $lesionadosTxt = (isset($hecho->lesionados) && $hecho->lesionados->count() > 0)
                                    ? ($hecho->lesionados->count() . ' PERSONA(S)')
                                    : 'NO';

                                $tipoHecho = strtoupper((string)($hecho->tipo_hecho ?? ''));

                                $estatus = strtoupper((string)($hecho->situacion ?? ''));
                                $obsEstatus = trim($estatus . ' ' . $hecho->id);
                            @endphp

                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $hora !== '' ? $hora : '-' }}</td>
                                <td>{{ $unidad !== '' ? $unidad : '-' }}</td>
                                <td>{{ $perito !== '' ? $perito : '-' }}</td>
                                <td class="text-left">{{ $lugar !== '' ? $lugar : '-' }}</td>
                                <td>{{ $grua }}</td>
                                <td>{{ $lesionadosTxt }}</td>
                                <td>{{ $tipoHecho !== '' ? $tipoHecho : '-' }}</td>
                                <td>{{ $obsEstatus !== '' ? $obsEstatus : '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center">No se registraron hechos en el rango indicado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .table th, .table td { text-align: center; vertical-align: middle; }
        .table td.text-left { text-align: left !important; }
    </style>
@stop
