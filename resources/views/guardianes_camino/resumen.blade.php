@extends('adminlte::page')

@section('title', 'Resumen Operativo')

@section('content_header')
    <h1>Resumen del Operativo Guardianes del Camino</h1>
@stop

@section('content')

<div class="row">

<div class="col-md-12">

<div class="card card-outline card-primary">

<div class="card-header">

<h3 class="card-title">
Resumen del Operativo
</h3>

<div class="card-tools">

<a href="{{ route('guardianes_camino.show',$operativo->id) }}" class="btn btn-secondary btn-sm">
<i class="fa-solid fa-arrow-left"></i> Volver
</a>

<a href="{{ route('guardianes_camino.whatsapp',$operativo->id) }}" class="btn btn-success btn-sm">
<i class="fa-brands fa-whatsapp"></i> Compartir
</a>

</div>

</div>


<div class="card-body">

<div class="row mb-3">

<div class="col-md-3">
<strong>Fecha:</strong><br>
{{ optional($operativo->fecha)->format('d-m-Y') }}
</div>

<div class="col-md-3">
<strong>Hora:</strong><br>
{{ $operativo->hora }}
</div>

<div class="col-md-3">
<strong>Delegación:</strong><br>
{{ $operativo->delegacion->nombre ?? 'N/D' }}
</div>

<div class="col-md-3">
<strong>Destacamento:</strong><br>
{{ $operativo->destacamento->nombre ?? 'N/D' }}
</div>

</div>


<div class="row mb-4">

<div class="col-md-12">

<strong>Lugar:</strong><br>
{{ $operativo->lugar }}

</div>

</div>


@if($resumen->count()==0)

<div class="alert alert-info">
No hay dispositivos registrados para este operativo.
</div>

@endif


@foreach($resumen as $item)

<div class="card card-outline card-success mb-3">

<div class="card-header">

<h3 class="card-title">

{{ $item->catalogo->nombre ?? 'Dispositivo' }}:
{{ $item->total_cantidad }}

</h3>

</div>

<div class="card-body">

<div class="row">

<div class="col-md-3">
<strong>Vehículos inspeccionados:</strong><br>
{{ $item->total_vehiculos_inspeccionados }}
</div>

<div class="col-md-3">
<strong>Personas inspeccionadas:</strong><br>
{{ $item->total_personas_inspeccionadas }}
</div>

<div class="col-md-3">
<strong>Vehículos impactados:</strong><br>
{{ $item->total_vehiculos_impactados }}
</div>

<div class="col-md-3">
<strong>Personas impactadas:</strong><br>
{{ $item->total_personas_impactadas }}
</div>

</div>

<br>

<div class="row">

<div class="col-md-3">
<strong>Estado de fuerza:</strong><br>
{{ $item->total_estado_fuerza_participante }}
</div>

<div class="col-md-3">
<strong>Kilómetros recorridos:</strong><br>
{{ $item->total_kilometros_recorridos }}
</div>

<div class="col-md-3">
<strong>Acompañamientos:</strong><br>
{{ $item->total_acompanamientos }}
</div>

<div class="col-md-3">
<strong>Abanderamientos:</strong><br>
{{ $item->total_abanderamientos }}
</div>

</div>

<br>

<div class="row">

<div class="col-md-3">
<strong>Auxilios viales:</strong><br>
{{ $item->total_auxilios_viales }}
</div>

<div class="col-md-3">
<strong>Proximidad empresas:</strong><br>
{{ $item->total_prox_empresas }}
</div>

<div class="col-md-3">
<strong>Proximidad tiendas:</strong><br>
{{ $item->total_prox_tiendas_conveniencia }}
</div>

<div class="col-md-3">
<strong>Proximidad escuelas:</strong><br>
{{ $item->total_prox_escuelas }}
</div>

</div>

<br>

<div class="row">

<div class="col-md-3">
<strong>Proximidad hospitales:</strong><br>
{{ $item->total_prox_hospitales }}
</div>

<div class="col-md-3">
<strong>Antecedentes personas:</strong><br>
{{ $item->total_antecedentes_personas }}
</div>

<div class="col-md-3">
<strong>Antecedentes vehículos:</strong><br>
{{ $item->total_antecedentes_vehiculos }}
</div>

<div class="col-md-3">
<strong>Antecedentes motos:</strong><br>
{{ $item->total_antecedentes_motos }}
</div>

</div>

<br>

<div class="row">

<div class="col-md-3">
<strong>Antecedentes camiones:</strong><br>
{{ $item->total_antecedentes_camiones }}
</div>

<div class="col-md-3">
<strong>Puestas a disposición:</strong><br>
{{ $item->total_puestas_disposicion }}
</div>

<div class="col-md-3">
<strong>Vehículos recuperados:</strong><br>
{{ $item->total_vehiculos_recuperados }}
</div>

<div class="col-md-3">
<strong>Armas aseguradas:</strong><br>
{{ $item->total_armas_aseguradas }}
</div>

</div>

<br>

<div class="row">

<div class="col-md-6">
<strong>Mercancía recuperada:</strong><br>
{{ $item->total_mercancia_recuperada }}
</div>

<div class="col-md-6">
<strong>Decomiso drogas:</strong><br>
{{ $item->total_decomiso_drogas }}
</div>

</div>

</div>

</div>

@endforeach

</div>

</div>

</div>

</div>

@stop


@section('css')

<style>

.card-title{
font-weight:600;
}

</style>

@stop
