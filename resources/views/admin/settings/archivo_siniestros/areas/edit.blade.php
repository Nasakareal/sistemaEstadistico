@extends('adminlte::page')

@section('title', 'Editar área')

@section('content_header')
    <h1>Editar área del archivo</h1>
@stop

@section('content')
    @include('admin.settings.archivo_siniestros._nav')
    @include('admin.settings.archivo_siniestros._alerts')
    <div class="card card-outline card-primary"><form method="POST" action="{{ route('archivo_siniestros.areas.update', $area) }}">@csrf @method('PUT')
    <div class="card-body">@include('admin.settings.archivo_siniestros.areas._form')</div>
    <div class="card-footer d-flex justify-content-between"><a href="{{ route('archivo_siniestros.areas.index') }}" class="btn btn-secondary">Volver</a><button class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Guardar cambios</button></div>
</form></div>
@stop
