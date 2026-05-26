@extends('adminlte::page')

@section('title', 'Editar tutorial')

@section('content_header')
    <h1>Editar tutorial</h1>
@stop

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-body">
            <form method="POST" action="{{ route('settings.tutoriales.update', $tutorial) }}">
                @csrf
                @method('PUT')

                @include('admin.settings.tutoriales._form', [
                    'tutorial' => $tutorial,
                    'categorias' => $categorias,
                    'plataformas' => $plataformas,
                ])

                <div class="d-flex justify-content-end mt-3">
                    <a href="{{ route('settings.tutoriales.index') }}" class="btn btn-default mr-2">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
@stop
