@extends('adminlte::page')

@section('title', 'Registrar libro')

@section('content_header')
    <h1>Registrar libro físico</h1>
@stop

@section('content')
    @include('admin.settings.archivo_siniestros._nav')
    @include('admin.settings.archivo_siniestros._alerts')
    <div class="card card-outline card-primary"><form method="POST" action="{{ route('archivo_siniestros.libros.store') }}">@csrf
    <div class="card-body">@include('admin.settings.archivo_siniestros.libros._form')<div class="alert alert-info mb-0">Registrar un libro no significa que ya esté guardado en el anaquel. La ubicación física se confirma después escaneando el QR del bloque.</div></div>
    <div class="card-footer d-flex justify-content-between"><a href="{{ route('archivo_siniestros.index') }}" class="btn btn-secondary">Volver</a><button class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Registrar libro</button></div>
</form></div>
@stop
