@extends('adminlte::page')

@section('title', 'Nuevo contacto de apoyo')

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h1 class="mb-1">Nuevo contacto de apoyo</h1>
            <p class="text-muted mb-0">Captura encargados por región y nivel de gobierno.</p>
        </div>
        <a href="{{ route('directorio_red_apoyo.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Volver
        </a>
    </div>
@stop

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fa-solid fa-address-book"></i> Datos del contacto
            </h3>
        </div>
        <div class="card-body">
            <form action="{{ route('directorio_red_apoyo.store') }}" method="POST">
                @csrf
                @include('admin.settings.directorio_red_apoyo._form')
            </form>
        </div>
    </div>
@stop

@section('css')
    @include('admin.settings.directorio_red_apoyo._styles')
@stop
