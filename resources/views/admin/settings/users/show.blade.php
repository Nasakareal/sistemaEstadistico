@extends('adminlte::page')

@section('title', 'Detalles del Usuario')

@section('content_header')
    <h1>Detalles del Usuario</h1>
@stop

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">Datos Registrados</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        {{-- Nombre y Correo --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">Nombre</label>
                                <p class="form-control-static">{{ $user->name }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">Correo Electrónico</label>
                                <p class="form-control-static">{{ $user->email }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        {{-- Área y Estado --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="area">Área</label>
                                <p class="form-control-static">{{ $user->area ?? 'No especificada' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="estado">Estado</label>
                                <p class="form-control-static">{{ $user->estado }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        {{-- Rol y Foto de Perfil --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="role">Rol</label>
                                <p class="form-control-static">{{ $user->roles->pluck('name')->join(', ') }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="foto_perfil">Foto de Perfil</label>
                                @if ($user->foto_perfil)
                                    <div>
                                        <img src="{{ asset('storage/' . $user->foto_perfil) }}" alt="Foto de Perfil" style="max-width: 150px; max-height: 150px;" class="img-thumbnail">
                                    </div>
                                @else
                                    <p class="form-control-static">No tiene foto de perfil.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        {{-- Botón de regreso --}}
                        <div class="col-md-12 text-center">
                            <div class="form-group">
                                <a href="{{ route('users.index') }}" class="btn btn-secondary">
                                    <i class="fa-solid fa-arrow-left"></i> Volver
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .form-group label {
            font-weight: bold;
        }
        .form-control-static {
            display: block;
            font-size: 1rem;
            margin-top: 0.5rem;
        }
        .img-thumbnail {
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 4px;
        }
    </style>
@stop

@section('js')
    <script> console.log("Vista de detalles del usuario cargada correctamente."); </script>
@stop
