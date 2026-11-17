@extends('adminlte::page')

@section('title', 'Editar libro')

@section('content_header')
    <h1>Editar datos del libro</h1>
@stop

@section('content')
    @include('admin.settings.archivo_siniestros._nav')
    @include('admin.settings.archivo_siniestros._alerts')
    <div class="card card-outline card-primary"><form method="POST" action="{{ route('archivo_siniestros.libros.update', $libro) }}">@csrf @method('PUT')
    <div class="card-body">@include('admin.settings.archivo_siniestros.libros._form')<div class="alert alert-info mb-0">Para cambiar la ubicación utiliza las acciones de la ficha del libro; no se modifica desde este formulario.</div></div>
    <div class="card-footer d-flex justify-content-between"><a href="{{ route('archivo_siniestros.libros.show', $libro) }}" class="btn btn-secondary">Volver</a><button class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Guardar cambios</button></div>
</form></div>
@stop
