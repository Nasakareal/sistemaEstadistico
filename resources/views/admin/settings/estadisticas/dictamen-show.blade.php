@extends('adminlte::page')

@section('title', 'Dictamen del Hecho')

@section('content_header')
    <h1>Dictamen del Hecho #{{ $hecho->id }}</h1>
@stop

@section('content')

<div class="mb-3 d-flex gap-2">
    <a href="{{ route('estadisticas.dictamen') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Volver al buscador
    </a>

    <a href="{{ route('estadisticas.dictamen.docx', $hecho->id) }}" class="btn btn-primary">
        <i class="fas fa-file-word"></i> Descargar Dictamen (.docx)
    </a>
</div>


{{-- DATOS GENERALES --}}
<div class="card card-outline card-primary mb-3">
    <div class="card-header">
        <h3 class="card-title">Datos Generales</h3>
    </div>
    <div class="card-body">
        <table class="table table-sm table-bordered">
            <tr><th>Folio C5i</th><td>{{ $hecho->folio_c5i }}</td></tr>
            <tr><th>Fecha</th><td>{{ \Carbon\Carbon::parse($hecho->fecha)->format('d/m/Y') }}</td></tr>
            <tr><th>Hora</th><td>{{ \Carbon\Carbon::parse($hecho->hora)->format('H:i') }}</td></tr>
            <tr><th>Tipo de Hecho</th><td>{{ $hecho->tipo_hecho }}</td></tr>
            <tr><th>Sector</th><td>{{ $hecho->sector }}</td></tr>
            <tr><th>Perito</th><td>{{ $hecho->perito }}</td></tr>
            <tr><th>Situación</th><td>{{ $hecho->situacion }}</td></tr>
            <tr><th>Municipio</th><td>{{ $hecho->municipio }}</td></tr>
        </table>
    </div>
</div>

{{-- UBICACIÓN --}}
<div class="card card-outline card-secondary mb-3">
    <div class="card-header">
        <h3 class="card-title">Ubicación</h3>
    </div>
    <div class="card-body">
        <table class="table table-sm table-bordered">
            <tr><th>Calle</th><td>{{ $hecho->calle }}</td></tr>
            <tr><th>Colonia</th><td>{{ $hecho->colonia }}</td></tr>
            <tr><th>Entre Calles</th><td>{{ $hecho->entre_calles }}</td></tr>
            <tr><th>Superficie</th><td>{{ $hecho->superficie_via }}</td></tr>
            <tr><th>Clima</th><td>{{ $hecho->clima }}</td></tr>
            <tr><th>Tiempo</th><td>{{ $hecho->tiempo }}</td></tr>
            <tr><th>Condiciones</th><td>{{ $hecho->condiciones }}</td></tr>
            <tr><th>Control de Tránsito</th><td>{{ $hecho->control_transito }}</td></tr>
        </table>
    </div>
</div>

{{-- VEHÍCULOS --}}
<div class="card card-outline card-info mb-3">
    <div class="card-header">
        <h3 class="card-title">Vehículos Involucrados</h3>
    </div>
    <div class="card-body">
        @foreach($hecho->vehiculos as $v)
            <div class="border rounded p-2 mb-3">
                <strong>Placas:</strong> {{ $v->placas }} <br>
                <strong>Marca:</strong> {{ $v->marca }} |
                <strong>Línea:</strong> {{ $v->linea }} |
                <strong>Modelo:</strong> {{ $v->modelo }} |
                <strong>Color:</strong> {{ $v->color }} <br>
                <strong>Tipo:</strong> {{ $v->tipo }} |
                <strong>Servicio:</strong> {{ $v->tipo_servicio }} <br>
                <strong>Serie:</strong> {{ $v->serie }} <br>
                <strong>Tarjeta a nombre de:</strong> {{ $v->tarjeta_circulacion_nombre }} <br>
                <strong>Grua:</strong> {{ $v->grua }} |
                <strong>Corralón:</strong> {{ $v->corralon }} <br>
                <strong>Daños:</strong> ${{ number_format($v->monto_danos ?? 0, 2) }} <br>
                <strong>Partes dañadas:</strong> {{ $v->partes_danadas }}

                {{-- CONDUCTORES --}}
                <hr>
                <strong>Conductores</strong>
                <ul>
                    @foreach($v->conductores as $c)
                        <li>
                            {{ $c->nombre }}
                            ({{ $c->edad }} años) |
                            {{ $c->ocupacion }} |
                            Licencia: {{ $c->tipo_licencia ?? 'No presentó' }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
</div>

{{-- LESIONADOS --}}
<div class="card card-outline card-danger mb-3">
    <div class="card-header">
        <h3 class="card-title">Lesionados</h3>
    </div>
    <div class="card-body">
        @forelse($hecho->lesionados as $l)
            <div class="border rounded p-2 mb-2">
                <strong>Nombre:</strong> {{ $l->nombre }} <br>
                <strong>Edad:</strong> {{ $l->edad }} |
                <strong>Sexo:</strong> {{ $l->sexo }} <br>
                <strong>Tipo de lesión:</strong> {{ $l->tipo_lesion }} <br>
                <strong>Hospitalizado:</strong> {{ $l->hospitalizado ? 'Sí' : 'No' }} <br>
                <strong>Hospital:</strong> {{ $l->hospital }} <br>
                <strong>Observaciones:</strong> {{ $l->observaciones }}
            </div>
        @empty
            <p>No hubo lesionados.</p>
        @endforelse
    </div>
</div>

{{-- CAUSAS Y DAÑOS --}}
<div class="card card-outline card-warning mb-3">
    <div class="card-header">
        <h3 class="card-title">Causas y Daños Patrimoniales</h3>
    </div>
    <div class="card-body">
        <p><strong>Causas:</strong> {{ $hecho->causas }}</p>
        <p><strong>Daños patrimoniales:</strong> {{ $hecho->danos_patrimoniales }}</p>
        <p><strong>Monto daños patrimoniales:</strong> ${{ number_format($hecho->monto_danos_patrimoniales ?? 0, 2) }}</p>
        <p><strong>Propiedades afectadas:</strong> {{ $hecho->propiedades_afectadas }}</p>
    </div>
</div>

{{-- BOTÓN VOLVER --}}
<div class="mb-3">
    <a href="{{ route('estadisticas.dictamen') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Volver al buscador
    </a>
</div>

@stop
