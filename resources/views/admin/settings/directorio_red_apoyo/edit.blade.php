@extends('adminlte::page')

@section('title', 'Editar contacto de apoyo')

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h1 class="mb-1">Editar contacto de apoyo</h1>
            <p class="text-muted mb-0">{{ $redApoyo->institucion }}</p>
        </div>
        <a href="{{ route('directorio_red_apoyo.show', $redApoyo) }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Volver
        </a>
    </div>
@stop

@section('content')
    <div class="card card-outline card-success">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fa-regular fa-pen-to-square"></i> Actualizar contacto
            </h3>
        </div>
        <div class="card-body">
            <form action="{{ route('directorio_red_apoyo.update', $redApoyo) }}" method="POST">
                @csrf
                @method('PUT')
                @include('admin.settings.directorio_red_apoyo._form')
            </form>
        </div>
    </div>
@stop

@section('css')
    @include('admin.settings.directorio_red_apoyo._styles')
@stop
