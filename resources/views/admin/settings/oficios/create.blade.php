@extends('adminlte::page')

@section('title', 'Registrar oficio')

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h1 class="mb-1">Registrar oficio</h1>
            <p class="text-muted mb-0">Captura de documentos recibidos y enviados por unidad.</p>
        </div>
        <a href="{{ route('oficios.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Volver
        </a>
    </div>
@stop

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fa-solid fa-file-signature"></i> Datos del documento
            </h3>
        </div>
        <div class="card-body">
            <form action="{{ route('oficios.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
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
