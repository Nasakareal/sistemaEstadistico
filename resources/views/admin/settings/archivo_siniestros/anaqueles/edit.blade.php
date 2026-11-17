@extends('adminlte::page')

@section('title', 'Editar anaquel')

@section('content_header')
    <h1>Editar anaquel</h1>
@stop

@section('content')
    @include('admin.settings.archivo_siniestros._nav')
    @include('admin.settings.archivo_siniestros._alerts')
    <div class="card card-outline card-primary"><form action="{{ route('archivo_siniestros.anaqueles.update', $anaquel) }}" method="POST">@csrf @method('PUT')
    <div class="card-body">@include('admin.settings.archivo_siniestros.anaqueles._form')</div>
    <div class="card-footer d-flex justify-content-between"><a class="btn btn-secondary" href="{{ route('archivo_siniestros.anaqueles.index') }}">Volver</a><button class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Guardar cambios</button></div>
</form></div>
@stop
