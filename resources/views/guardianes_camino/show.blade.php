@extends('adminlte::page')

@section('title', 'Detalle Operativo')

@section('content_header')
    <h1>Detalle del Operativo Guardianes del Camino</h1>
@stop

@section('content')

<div class="row">

<div class="col-md-12">

<div class="card card-outline card-primary">

<div class="card-header">

<h3 class="card-title">
Información del Operativo
</h3>

<div class="card-tools">

<a href="{{ route('guardianes_camino.index') }}" class="btn btn-secondary btn-sm">
<i class="fa-solid fa-arrow-left"></i> Volver
</a>

@can('editar operativos carreteras')
<a href="{{ route('guardianes_camino.edit',$operativo->id) }}" class="btn btn-success btn-sm">
<i class="fa-solid fa-pencil"></i> Editar
</a>
@endcan

<a href="{{ route('guardianes_camino.resumen',$operativo->id) }}" class="btn btn-warning btn-sm">
<i class="fa-solid fa-chart-column"></i> Resumen
</a>

<a href="{{ route('guardianes_camino.whatsapp',$operativo->id) }}" class="btn btn-success btn-sm">
<i class="fa-brands fa-whatsapp"></i> WhatsApp
</a>

</div>
</div>


<div class="card-body">

<div class="row">

<div class="col-md-3">
<label>Fecha</label>
<input type="text" class="form-control" value="{{ optional($operativo->fecha)->format('d-m-Y') }}" readonly>
</div>

<div class="col-md-3">
<label>Hora</label>
<input type="text" class="form-control" value="{{ $operativo->hora }}" readonly>
</div>

<div class="col-md-3">
<label>Delegación</label>
<input type="text" class="form-control" value="{{ $operativo->delegacion->nombre ?? 'N/D' }}" readonly>
</div>

<div class="col-md-3">
<label>Destacamento</label>
<input type="text" class="form-control" value="{{ $operativo->destacamento->nombre ?? 'N/D' }}" readonly>
</div>

</div>

<br>

<div class="row">

<div class="col-md-12">
<label>Lugar</label>
<input type="text" class="form-control" value="{{ $operativo->lugar }}" readonly>
</div>

</div>

<br>

<div class="row">

<div class="col-md-12">
<label>Descripción</label>
<textarea class="form-control" rows="3" readonly>{{ $operativo->descripcion }}</textarea>
</div>

</div>

<br>

<div class="row">

<div class="col-md-6">
<label>Capturó</label>
<input type="text" class="form-control" value="{{ $operativo->creador->name ?? 'N/D' }}" readonly>
</div>

<div class="col-md-6">
<label>Observaciones</label>
<input type="text" class="form-control" value="{{ $operativo->observaciones }}" readonly>
</div>

</div>

</div>
</div>


</div>
</div>


<div class="row">

<div class="col-md-12">

<div class="card card-outline card-success">

<div class="card-header">

<h3 class="card-title">
Dispositivos Registrados
</h3>

<div class="card-tools">

@can('crear operativos carreteras')
<a href="{{ route('guardianes_camino.dispositivos.create',$operativo->id) }}" class="btn btn-primary btn-sm">
<i class="fa-solid fa-plus"></i> Agregar Dispositivo
</a>
@endcan

</div>
</div>


<div class="card-body">

@if($operativo->dispositivos->count() == 0)

<div class="alert alert-info">
No hay dispositivos registrados para este operativo.
</div>

@endif


<table id="dispositivos" class="table table-striped table-bordered table-hover table-sm">

<thead>

<tr>
<th>ID</th>
<th>Dispositivo</th>
<th>Fecha</th>
<th>Vehículos</th>
<th>Personas</th>
<th>Estado de Fuerza</th>
<th>Kilómetros</th>
<th>Acciones</th>
</tr>

</thead>

<tbody>

@foreach($operativo->dispositivos as $dispositivo)

<tr>

<td>{{ $dispositivo->id }}</td>

<td>
{{ $dispositivo->catalogo->nombre ?? 'N/D' }}
</td>

<td>
{{ optional($dispositivo->fecha)->format('d-m-Y') }} {{ $dispositivo->hora }}
</td>

<td>
{{ $dispositivo->vehiculos_inspeccionados }}
</td>

<td>
{{ $dispositivo->personas_inspeccionadas }}
</td>

<td>
{{ $dispositivo->estado_fuerza_participante }}
</td>

<td>
{{ $dispositivo->kilometros_recorridos }}
</td>

<td style="text-align:center">

<a href="{{ route('guardianes_camino.dispositivos.show',[$operativo->id,$dispositivo->id]) }}"
class="btn btn-info btn-sm">
<i class="fa-regular fa-eye"></i>
</a>

@can('editar operativos carreteras')

<a href="{{ route('guardianes_camino.dispositivos.edit',[$operativo->id,$dispositivo->id]) }}"
class="btn btn-success btn-sm">
<i class="fa-solid fa-pencil"></i>
</a>

@endcan

@can('eliminar operativos carreteras')

<form action="{{ route('guardianes_camino.dispositivos.destroy',[$operativo->id,$dispositivo->id]) }}"
method="POST"
style="display:inline-block">

@csrf
@method('DELETE')

<button
type="submit"
class="btn btn-danger btn-sm"
onclick="return confirm('¿Eliminar este dispositivo?')">

<i class="fa-solid fa-trash"></i>

</button>

</form>

@endcan

</td>

</tr>

@endforeach

</tbody>

</table>

</div>

</div>

</div>

</div>

@stop


@section('css')

<style>

.table th,
.table td {
text-align:center;
vertical-align:middle;
}

#dispositivos.table-hover tbody tr:hover {
background-color: rgba(255,255,255,0.08) !important;
}

</style>

@stop


@section('js')

<script>

$(function () {

$('#dispositivos').DataTable({
paging:false,
info:false,
order:[[0,"desc"]],
language:{
emptyTable:"No hay información disponible",
search:"Buscar:"
},
responsive:true,
lengthChange:false,
autoWidth:false
});

});

</script>

@stop
