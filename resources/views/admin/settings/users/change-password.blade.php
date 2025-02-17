@extends('adminlte::page')

@section('title', 'Cambiar Contraseña')

@section('content_header')
    <h1>Cambiar Contraseña</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title">Actualizar Contraseña</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('user.password.update') }}" method="POST">

                        @csrf

                        <div class="row">
                            <!-- Contraseña Actual -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="current_password">Contraseña Actual</label>
                                    <input type="password" name="current_password" id="current_password" class="form-control"
                                           placeholder="Ingrese su contraseña actual" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Nueva Contraseña -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="password">Nueva Contraseña</label>
                                    <input type="password" name="password" id="password" class="form-control"
                                           placeholder="Ingrese la nueva contraseña" required>
                                </div>
                            </div>
                            <!-- Confirmar Nueva Contraseña -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="password_confirmation">Confirmar Nueva Contraseña</label>
                                    <input type="password" name="password_confirmation" id="password_confirmation" class="form-control"
                                           placeholder="Confirme la nueva contraseña" required>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <div class="row">
                            <div class="col-md-12 text-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-solid fa-check"></i> Actualizar Contraseña
                                </button>
                                <a href="{{ route('profile') }}" class="btn btn-secondary">
                                    <i class="fa-solid fa-ban"></i> Cancelar
                                </a>
                            </div>
                        </div>
                    </form>
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
    </style>
@stop

@section('js')
    <script>
        @if (session('success'))
            Swal.fire({
                position: 'center',
                icon: 'success',
                title: '{{ session('success') }}',
                showConfirmButton: false,
                timer: 1500
            });
        @endif

        @if ($errors->any())
            Swal.fire({
                icon: 'error',
                title: 'Error en el formulario',
                html: `
                    <ul style="text-align: left;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                `,
                confirmButtonText: 'Aceptar'
            });
        @endif
    </script>
@stop
