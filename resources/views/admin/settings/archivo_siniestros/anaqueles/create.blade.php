@extends('adminlte::page')

@section('title', 'Nuevo anaquel')

@section('content_header')
    <h1>Registrar anaquel</h1>
@stop

@section('content')
    @include('admin.settings.archivo_siniestros._nav')
    @include('admin.settings.archivo_siniestros._alerts')
    <div class="card card-outline card-primary"><form action="{{ route('archivo_siniestros.anaqueles.store') }}" method="POST">@csrf 
    <div class="card-body">@include('admin.settings.archivo_siniestros.anaqueles._form')</div>
    <div class="card-footer d-flex justify-content-between"><a class="btn btn-secondary" href="{{ route('archivo_siniestros.anaqueles.index') }}">Volver</a><button class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Guardar anaquel</button></div>
</form></div>
@stop
