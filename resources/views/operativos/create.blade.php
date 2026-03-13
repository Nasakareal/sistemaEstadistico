@extends('adminlte::page')

@section('title', 'Consolidado de Operativos')

@section('content_header')
<div class="d-flex align-items-center justify-content-between">
    <h1 class="mb-0">Consolidado de Operativos</h1>
    <a href="{{ route('operativos.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Volver
    </a>
</div>
@stop

@section('content')

@if($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0 pl-3">
        @foreach($errors->all() as $e)
            <li>{{ $e }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="card">
<form action="{{ route('operativos.store') }}" method="POST" enctype="multipart/form-data">
@csrf

<div class="card-body">

<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label>Fecha</label>
            <input type="date" name="fecha" class="form-control" value="{{ old('fecha', now('America/Mexico_City')->toDateString()) }}" required>
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            <label>Hora</label>
            <input type="time" name="hora" class="form-control" value="{{ old('hora', now('America/Mexico_City')->format('H:i')) }}" required>
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label>Descripción general</label>
            <input type="text" name="descripcion_general" class="form-control" value="{{ old('descripcion_general') }}" placeholder="Ej. OPERATIVO GUARDIANES DEL CAMINO" required>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="form-group">
            <label>Tramos / Municipios</label>
            <input type="text" name="tramos" class="form-control" value="{{ old('tramos') }}" placeholder="Ej. Aeropuerto, Zinapécuaro, Queréndaro, Indaparapeo, Charo, Morelia...">
        </div>
    </div>
</div>

<div class="card card-outline card-primary">
<div class="card-header">
    <h3 class="card-title">Dispositivos</h3>
</div>

<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-striped table-hover mb-0">
    <thead class="thead-light">
        <tr>
            <th style="width:260px;">Tipo</th>
            <th style="width:120px;">Realizados</th>
            <th style="width:140px;">Vehículos</th>
            <th style="width:140px;">Personas</th>
            <th style="width:140px;">Veh. Impact.</th>
            <th style="width:140px;">Pers. Impact.</th>
            <th style="width:140px;">Edo. Fuerza</th>
            <th style="width:140px;">KM</th>
            <th style="width:220px;">CRP´s</th>
            <th style="width:220px;">Fotos</th>
        </tr>
    </thead>
    <tbody>
        @foreach($catalogos as $c)
            <tr>
                <td>
                    <strong>{{ $c->nombre }}</strong>
                    <input type="hidden" name="items[{{ $c->id }}][operativo_catalogo_id]" value="{{ $c->id }}">
                </td>

                <td><input type="number" min="0" class="form-control form-control-sm" name="items[{{ $c->id }}][dispositivos_realizados]" value="{{ old("items.$c->id.dispositivos_realizados", 0) }}"></td>
                <td><input type="number" min="0" class="form-control form-control-sm" name="items[{{ $c->id }}][vehiculos_inspeccionados]" value="{{ old("items.$c->id.vehiculos_inspeccionados", 0) }}"></td>
                <td><input type="number" min="0" class="form-control form-control-sm" name="items[{{ $c->id }}][personas_inspeccionadas]" value="{{ old("items.$c->id.personas_inspeccionadas", 0) }}"></td>
                <td><input type="number" min="0" class="form-control form-control-sm" name="items[{{ $c->id }}][vehiculos_impactados]" value="{{ old("items.$c->id.vehiculos_impactados", 0) }}"></td>
                <td><input type="number" min="0" class="form-control form-control-sm" name="items[{{ $c->id }}][personas_impactadas]" value="{{ old("items.$c->id.personas_impactadas", 0) }}"></td>
                <td><input type="number" min="0" class="form-control form-control-sm" name="items[{{ $c->id }}][estado_fuerza_participante]" value="{{ old("items.$c->id.estado_fuerza_participante", 0) }}"></td>
                <td><input type="number" min="0" step="0.01" class="form-control form-control-sm" name="items[{{ $c->id }}][kilometros_recorridos]" value="{{ old("items.$c->id.kilometros_recorridos", 0) }}"></td>
                <td><input type="text" class="form-control form-control-sm" name="items[{{ $c->id }}][crps_participantes]" value="{{ old("items.$c->id.crps_participantes") }}" placeholder="25-XXXX, 22-XXXX"></td>

                <td>
                    <input type="file" class="form-control-file" name="fotos[{{ $c->id }}][]" accept="image/*" multiple>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
</div>
</div>
</div>

<div class="card card-outline card-info">
<div class="card-header">
    <h3 class="card-title">Totales (antecedentes / puestas a disposición)</h3>
</div>
<div class="card-body">
    <div class="row">
        <div class="col-md-3"><div class="form-group"><label>Antecedentes personas</label><input type="number" min="0" name="totales[antecedentes_personas]" class="form-control" value="{{ old('totales.antecedentes_personas', 0) }}"></div></div>
        <div class="col-md-3"><div class="form-group"><label>Antecedentes vehículos</label><input type="number" min="0" name="totales[antecedentes_vehiculos]" class="form-control" value="{{ old('totales.antecedentes_vehiculos', 0) }}"></div></div>
        <div class="col-md-3"><div class="form-group"><label>Antecedentes motos</label><input type="number" min="0" name="totales[antecedentes_motos]" class="form-control" value="{{ old('totales.antecedentes_motos', 0) }}"></div></div>
        <div class="col-md-3"><div class="form-group"><label>Antecedentes camiones</label><input type="number" min="0" name="totales[antecedentes_camiones]" class="form-control" value="{{ old('totales.antecedentes_camiones', 0) }}"></div></div>
    </div>

    <div class="row">
        <div class="col-md-2"><div class="form-group"><label>Puestas a disposición</label><input type="number" min="0" name="totales[puestas_disposicion]" class="form-control" value="{{ old('totales.puestas_disposicion', 0) }}"></div></div>
        <div class="col-md-2"><div class="form-group"><label>Vehículos recuperados</label><input type="number" min="0" name="totales[vehiculos_recuperados]" class="form-control" value="{{ old('totales.vehiculos_recuperados', 0) }}"></div></div>
        <div class="col-md-2"><div class="form-group"><label>Armas aseguradas</label><input type="number" min="0" name="totales[armas_aseguradas]" class="form-control" value="{{ old('totales.armas_aseguradas', 0) }}"></div></div>
        <div class="col-md-3"><div class="form-group"><label>Mercancía recuperada</label><input type="number" min="0" name="totales[mercancia_recuperada]" class="form-control" value="{{ old('totales.mercancia_recuperada', 0) }}"></div></div>
        <div class="col-md-3"><div class="form-group"><label>Decomiso de drogas</label><input type="number" min="0" name="totales[decomiso_drogas]" class="form-control" value="{{ old('totales.decomiso_drogas', 0) }}"></div></div>
    </div>
</div>
</div>

<div class="card card-outline card-secondary">
<div class="card-header">
    <h3 class="card-title">Observaciones</h3>
</div>
<div class="card-body">
    <textarea name="observaciones" class="form-control" rows="3">{{ old('observaciones') }}</textarea>
</div>
</div>

</div>

<div class="card-footer text-right">
    <a href="{{ route('operativos.index') }}" class="btn btn-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary">
        <i class="fas fa-save"></i> Guardar consolidado
    </button>
</div>

</form>
</div>

@stop
