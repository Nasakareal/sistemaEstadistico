@extends('adminlte::page')

@section('plugins.Select2', true)

@section('title', 'Editar oficio')

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h1 class="mb-1">Editar oficio</h1>
            <p class="text-muted mb-0">{{ $oficio->numero_oficio }}</p>
        </div>
        <a href="{{ route('oficios.show', $oficio) }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Volver
        </a>
    </div>
@stop

@section('content')
    <div class="card card-outline card-success">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fa-regular fa-pen-to-square"></i> Actualizar documento
            </h3>
        </div>
        <div class="card-body">
            <form action="{{ route('oficios.update', $oficio) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('admin.settings.oficios._form')
            </form>
        </div>
    </div>
@stop

@section('css')
    @include('admin.settings.oficios._styles')
@stop

@section('js')
    @include('admin.settings.oficios._alerts')
@stop
