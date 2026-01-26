@extends('adminlte::page')

@section('title', 'Detalle del Dictamen')

@section('content_header')
    <div class="d-flex align-items-center justify-content-between">
        <h1 class="mb-0">Detalle del Dictamen</h1>

        @php
            $u = auth()->user();
            $puedeEditar = ($u->id === $dictamen->created_by) || $u->hasRole(['Administrador','Superadmin','Administrativo']);
        @endphp

        @if($puedeEditar)
            <a href="{{ route('dictamenes.edit', $dictamen->id) }}" class="btn btn-success">
                <i class="fas fa-edit"></i> Editar
            </a>
        @endif
    </div>
@stop

@section('content')
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card">
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-4">
                <strong>Número de dictamen</strong>
                <div>{{ $dictamen->numero_dictamen }}</div>
            </div>
            <div class="col-md-4">
                <strong>Año</strong>
                <div>{{ $dictamen->anio }}</div>
            </div>
            <div class="col-md-4">
                <strong>Área</strong>
                <div>{{ $dictamen->area }}</div>
            </div>
        </div>

        <hr>

        <div class="row mb-3">
            <div class="col-md-6">
                <strong>Nombre del policía</strong>
                <div>{{ $dictamen->nombre_policia }}</div>
            </div>
            <div class="col-md-6">
                <strong>Nombre del Ministerio Público</strong>
                <div>{{ $dictamen->nombre_mp }}</div>
            </div>
        </div>

        <hr>

        <div class="row mb-3">
            <div class="col-md-6">
                <strong>Fecha de creación</strong>
                <div>{{ $dictamen->created_at->format('d/m/Y H:i') }}</div>
            </div>
            <div class="col-md-6">
                <strong>Última actualización</strong>
                <div>{{ $dictamen->updated_at->format('d/m/Y H:i') }}</div>
            </div>
        </div>

        <hr>

        <div class="row mb-4">
            <div class="col-md-12">
                <strong>Archivo de dictamen</strong>
                <div class="mt-2">
                    @if($dictamen->archivo_dictamen)
                        <a
                            href="{{ asset('storage/'.$dictamen->archivo_dictamen) }}"
                            target="_blank"
                            class="btn btn-outline-danger"
                        >
                            <i class="fas fa-file-pdf"></i> Ver PDF
                        </a>
                    @else
                        <span class="text-muted">No se ha subido ningún archivo.</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between">
            <a href="{{ route('dictamenes.index') }}" class="btn btn-secondary">
                Volver al listado
            </a>

            @if($puedeEditar)
                <a href="{{ route('dictamenes.edit', $dictamen->id) }}" class="btn btn-success">
                    Editar dictamen
                </a>
            @endif
        </div>
    </div>
</div>
@stop
