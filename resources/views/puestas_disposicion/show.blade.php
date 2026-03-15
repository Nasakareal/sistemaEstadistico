@extends('adminlte::page')

@section('title', 'Ver Puesta a Disposición')

@section('content_header')
    <h1>Detalle de Puesta a Disposición</h1>
@stop

@section('content')

<div class="row">
<div class="col-md-12">

<div class="card card-outline card-primary">
<div class="card-header">
<h3 class="card-title">
Puesta No. {{ $puestaDisposicion->numero_puesta }}/{{ $puestaDisposicion->anio }}
</h3>
</div>

<div class="card-body">

<div class="row">

<div class="col-md-3">
<label>Tipo de Puesta</label>
<input type="text" class="form-control" value="{{ $puestaDisposicion->tipo_puesta }}" readonly>
</div>

<div class="col-md-3">
<label>Motivo</label>
<input type="text" class="form-control" value="{{ $puestaDisposicion->motivo }}" readonly>
</div>

<div class="col-md-3">
<label>Estatus</label>
<input type="text" class="form-control" value="{{ $puestaDisposicion->estatus }}" readonly>
</div>

</div>

<hr>

<div class="row">

<div class="col-md-4">
<label>Fecha de Puesta</label>
<input type="text" class="form-control"
value="{{ \Carbon\Carbon::parse($puestaDisposicion->fecha_puesta)->format('d/m/Y') }}"
readonly>
</div>

<div class="col-md-4">
<label>Hora de Puesta</label>
<input type="text" class="form-control"
value="{{ $puestaDisposicion->hora_puesta }}"
readonly>
</div>

<div class="col-md-4">
<label>Lugar de Puesta</label>
<input type="text" class="form-control"
value="{{ $puestaDisposicion->lugar_puesta }}"
readonly>
</div>

</div>

<hr>

<div class="row">

<div class="col-md-4">
<label>Nombre del Policía</label>
<input type="text" class="form-control"
value="{{ $puestaDisposicion->nombre_policia }}"
readonly>
</div>

<div class="col-md-4">
<label>Nombre del MP</label>
<input type="text" class="form-control"
value="{{ $puestaDisposicion->nombre_mp }}"
readonly>
</div>

<div class="col-md-4">
<label>Autoridad Receptora</label>
<input type="text" class="form-control"
value="{{ $puestaDisposicion->autoridad_receptora }}"
readonly>
</div>

</div>

<div class="row mt-3">

<div class="col-md-4">
<label>Área</label>
<input type="text" class="form-control"
value="{{ $puestaDisposicion->area }}"
readonly>
</div>

<div class="col-md-4">
<label>Carpeta de Investigación</label>
<input type="text" class="form-control"
value="{{ $puestaDisposicion->carpeta_investigacion }}"
readonly>
</div>

<div class="col-md-4">
<label>Oficio</label>
<input type="text" class="form-control"
value="{{ $puestaDisposicion->oficio }}"
readonly>
</div>

</div>

@if($puestaDisposicion->archivo_puesta)
<hr>
<div class="row">
<div class="col-md-12">
<a href="{{ asset('storage/'.$puestaDisposicion->archivo_puesta) }}"
class="btn btn-outline-primary"
target="_blank">
<i class="fa-solid fa-file-pdf"></i> Ver Archivo PDF
</a>
</div>
</div>
@endif

<hr>

<div class="row">
<div class="col-md-12">
<label>Narrativa</label>
<textarea class="form-control" rows="4" readonly>{{ $puestaDisposicion->narrativa }}</textarea>
</div>
</div>

<div class="row mt-3">
<div class="col-md-12">
<label>Observaciones</label>
<textarea class="form-control" rows="3" readonly>{{ $puestaDisposicion->observaciones }}</textarea>
</div>
</div>

</div>
</div>


{{-- PERSONAS --}}
<div class="card card-outline card-info">

<div class="card-header">
<h3 class="card-title">Personas</h3>
</div>

<div class="card-body">

@if($puestaDisposicion->personas->count())

@foreach($puestaDisposicion->personas as $persona)

<div class="bloque-dinamico mb-3">

<div class="row">

<div class="col-md-4">
<label>Nombre Completo</label>
<input type="text" class="form-control" value="{{ $persona->nombre_completo }}" readonly>
</div>

<div class="col-md-4">
<label>Alias</label>
<input type="text" class="form-control" value="{{ $persona->alias }}" readonly>
</div>

<div class="col-md-2">
<label>Edad</label>
<input type="text" class="form-control" value="{{ $persona->edad }}" readonly>
</div>

<div class="col-md-2">
<label>Sexo</label>
<input type="text" class="form-control" value="{{ $persona->sexo }}" readonly>
</div>

</div>

<div class="row mt-2">

<div class="col-md-3">
<label>Fecha Nacimiento</label>
<input type="text" class="form-control"
value="{{ $persona->fecha_nacimiento }}"
readonly>
</div>

<div class="col-md-3">
<label>CURP</label>
<input type="text" class="form-control" value="{{ $persona->curp }}" readonly>
</div>

<div class="col-md-3">
<label>RFC</label>
<input type="text" class="form-control" value="{{ $persona->rfc }}" readonly>
</div>

<div class="col-md-3">
<label>Calidad</label>
<input type="text" class="form-control" value="{{ $persona->calidad }}" readonly>
</div>

</div>

<div class="row mt-2">

<div class="col-md-8">
<label>Domicilio</label>
<input type="text" class="form-control" value="{{ $persona->domicilio }}" readonly>
</div>

<div class="col-md-4">
<label>Delito o Motivo</label>
<input type="text" class="form-control" value="{{ $persona->delito_o_motivo }}" readonly>
</div>

</div>

<div class="row mt-2">

<div class="col-md-4">
<label>Mandamiento Judicial</label>
<input type="text" class="form-control" value="{{ $persona->mandamiento_judicial }}" readonly>
</div>

<div class="col-md-2">
<label>Orden Aprehensión</label>
<input type="text" class="form-control"
value="{{ $persona->orden_aprehension ? 'SI' : 'NO' }}"
readonly>
</div>

<div class="col-md-6">
<label>Observaciones</label>
<input type="text" class="form-control" value="{{ $persona->observaciones }}" readonly>
</div>

</div>

</div>

@endforeach

@else
<p class="text-muted">No hay personas registradas.</p>
@endif

</div>
</div>


{{-- VEHICULOS --}}
<div class="card card-outline card-warning">

<div class="card-header">
<h3 class="card-title">Vehículos</h3>
</div>

<div class="card-body">

@if($puestaDisposicion->vehiculos->count())

@foreach($puestaDisposicion->vehiculos as $vehiculo)

<div class="bloque-dinamico mb-3">

<div class="row">

<div class="col-md-2">
<label>Tipo</label>
<input type="text" class="form-control" value="{{ $vehiculo->tipo }}" readonly>
</div>

<div class="col-md-2">
<label>Marca</label>
<input type="text" class="form-control" value="{{ $vehiculo->marca }}" readonly>
</div>

<div class="col-md-2">
<label>Submarca</label>
<input type="text" class="form-control" value="{{ $vehiculo->submarca }}" readonly>
</div>

<div class="col-md-2">
<label>Modelo</label>
<input type="text" class="form-control" value="{{ $vehiculo->modelo }}" readonly>
</div>

<div class="col-md-2">
<label>Color</label>
<input type="text" class="form-control" value="{{ $vehiculo->color }}" readonly>
</div>

<div class="col-md-2">
<label>Placas</label>
<input type="text" class="form-control" value="{{ $vehiculo->placas }}" readonly>
</div>

</div>

<div class="row mt-2">

<div class="col-md-3">
<label>Serie</label>
<input type="text" class="form-control" value="{{ $vehiculo->serie }}" readonly>
</div>

<div class="col-md-3">
<label>Calidad</label>
<input type="text" class="form-control" value="{{ $vehiculo->calidad }}" readonly>
</div>

<div class="col-md-3">
<label>Motivo Relación</label>
<input type="text" class="form-control" value="{{ $vehiculo->motivo_relacion }}" readonly>
</div>

<div class="col-md-3">
<label>Reporte Robo</label>
<input type="text" class="form-control"
value="{{ $vehiculo->con_reporte_robo ? 'SI' : 'NO' }}"
readonly>
</div>

</div>

</div>

@endforeach

@else
<p class="text-muted">No hay vehículos registrados.</p>
@endif

</div>
</div>


{{-- OBJETOS --}}
<div class="card card-outline card-secondary">

<div class="card-header">
<h3 class="card-title">Objetos</h3>
</div>

<div class="card-body">

@if($puestaDisposicion->objetos->count())

@foreach($puestaDisposicion->objetos as $objeto)

<div class="bloque-dinamico mb-3">

<div class="row">

<div class="col-md-3">
<label>Tipo</label>
<input type="text" class="form-control" value="{{ $objeto->tipo_objeto }}" readonly>
</div>

<div class="col-md-5">
<label>Descripción</label>
<input type="text" class="form-control" value="{{ $objeto->descripcion }}" readonly>
</div>

<div class="col-md-2">
<label>Cantidad</label>
<input type="text" class="form-control" value="{{ $objeto->cantidad }}" readonly>
</div>

<div class="col-md-2">
<label>Unidad</label>
<input type="text" class="form-control" value="{{ $objeto->unidad_medida }}" readonly>
</div>

</div>

<div class="row mt-2">

<div class="col-md-4">
<label>Cadena Custodia</label>
<input type="text" class="form-control" value="{{ $objeto->cadena_custodia }}" readonly>
</div>

<div class="col-md-8">
<label>Observaciones</label>
<input type="text" class="form-control" value="{{ $objeto->observaciones }}" readonly>
</div>

</div>

</div>

@endforeach

@else
<p class="text-muted">No hay objetos registrados.</p>
@endif

</div>
</div>


<div class="mt-3">
<a href="{{ route('puestas_disposicion.index') }}" class="btn btn-secondary">
<i class="fa-solid fa-arrow-left"></i> Volver
</a>

<a href="{{ route('puestas_disposicion.edit',$puestaDisposicion->id) }}"
class="btn btn-primary">
<i class="fa-solid fa-pen"></i> Editar
</a>
</div>

</div>
</div>

@stop
