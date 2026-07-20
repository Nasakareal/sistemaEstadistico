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
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Apellido paterno</label>
                                <p class="form-control-static">{{ $user->apellido_paterno ?? 'Sin apellido paterno' }}</p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Apellido materno</label>
                                <p class="form-control-static">{{ $user->apellido_materno ?? 'Sin apellido materno' }}</p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Nombre(s)</label>
                                <p class="form-control-static">{{ $user->nombres ?: $user->name }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nombre completo</label>
                                <p class="form-control-static">{{ $user->name }}</p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Correo Electrónico</label>
                                <p class="form-control-static">{{ $user->email }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>WhatsApp autorizado principal</label>
                                <p class="form-control-static">
                                    {{ $user->telefono ?: 'No autorizado' }}
                                </p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>WhatsApp autorizado secundario</label>
                                <p class="form-control-static">
                                    {{ $user->telefono_whatsapp_secundario ?: 'Sin número secundario' }}
                                </p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>WhatsApp operativo para tiempo de reacción</label>
                                <p class="form-control-static">
                                    {{ $user->telefono_whatsapp_operativo ?: 'Sin teléfono operativo' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Estado</label>
                                <p class="form-control-static">{{ $user->estado ?? 'Sin estado' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Rol</label>
                                <p class="form-control-static">
                                    {{ $user->roles->pluck('name')->join(', ') ?: 'Sin rol asignado' }}
                                </p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Unidad principal</label>
                                <p class="form-control-static">
                                    {{ optional($user->unidad)->nombre ?? 'Sin unidad asignada' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Turno</label>
                                <p class="form-control-static">
                                    {{ optional($user->turno)->nombre ?? 'Sin turno asignado' }}
                                </p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Patrulla (número económico)</label>
                                <p class="form-control-static">
                                    {{ optional($user->patrulla)->numero_economico ?? 'Sin patrulla asignada' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Delegación</label>
                                <p class="form-control-static">
                                    @if($user->delegacion)
                                        {{ $user->delegacion->nombre }}
                                        @if(!empty($user->delegacion->clave))
                                            ({{ $user->delegacion->clave }})
                                        @endif
                                    @else
                                        No aplica / Sin delegación asignada
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Destacamento</label>
                                <p class="form-control-static">
                                    @if($user->destacamento)
                                        {{ $user->destacamento->nombre }}
                                        @if(!empty($user->destacamento->clave))
                                            ({{ $user->destacamento->clave }})
                                        @endif
                                    @else
                                        No aplica / Sin destacamento asignado
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Foto de Perfil</label>
                                @if ($user->foto_perfil)
                                    <div>
                                        <img src="{{ asset('storage/' . $user->foto_perfil) }}"
                                             alt="Foto de Perfil"
                                             class="img-thumbnail"
                                             style="max-width: 150px;">
                                    </div>
                                @else
                                    <p class="form-control-static">No tiene foto de perfil.</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-12 text-center">
                            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                                <i class="fa-solid fa-arrow-left"></i> Volver
                            </a>
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
            margin-top: 0.4rem;
        }

        .img-thumbnail {
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 4px;
        }
    </style>
@stop

@section('js')
    <script>
        console.log('Vista show de usuario cargada correctamente');
    </script>
@stop
