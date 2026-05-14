@extends('adminlte::page')

@section('title', 'Editar Subcategoría')

@section('content_header')
    <h1>Editar Subcategoría</h1>
@stop

@section('content')

<div class="row">
    <div class="col-md-12">

        <div class="card card-outline card-primary">

            <div class="card-header">
                <h3 class="card-title">
                    Modifique los Datos
                </h3>
            </div>

            <div class="card-body">

                <form action="{{ route('catalogos_actividades.subcategorias.update', $actividadSubcategoria->id) }}"
                      method="POST">

                    @csrf
                    @method('PUT')

                    <div class="row">

                        <div class="col-md-6">

                            <div class="form-group">

                                <label>
                                    Categoría
                                </label>

                                <input type="text"
                                       class="form-control"
                                       value="{{ $actividadSubcategoria->categoria->nombre ?? 'Sin categoría' }}"
                                       disabled>

                            </div>

                        </div>

                        <div class="col-md-6">

                            <div class="form-group">

                                <label for="nombre">
                                    Nombre de la Subcategoría
                                </label>

                                <input type="text"
                                       name="nombre"
                                       id="nombre"
                                       class="form-control @error('nombre') is-invalid @enderror"
                                       value="{{ old('nombre', $actividadSubcategoria->nombre) }}"
                                       placeholder="Ingrese el nombre"
                                       required>

                                @error('nombre')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror

                            </div>

                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-3">

                            <div class="form-group">

                                <label for="activo">
                                    Estado
                                </label>

                                <div class="custom-control custom-switch mt-2">

                                    <input type="checkbox"
                                           class="custom-control-input"
                                           id="activo"
                                           name="activo"
                                           value="1"
                                           {{ old('activo', $actividadSubcategoria->activo) ? 'checked' : '' }}>

                                    <label class="custom-control-label"
                                           for="activo">
                                        Activa
                                    </label>

                                </div>

                            </div>

                        </div>

                    </div>

                    <hr>

                    <div class="row">

                        <div class="col-md-12">

                            <div class="form-group">

                                <button type="submit"
                                        class="btn btn-primary">
                                    <i class="fa-solid fa-check"></i>
                                    Actualizar
                                </button>

                                <a href="{{ route('catalogos_actividades.index') }}"
                                   class="btn btn-secondary">
                                    <i class="fa-solid fa-ban"></i>
                                    Cancelar
                                </a>

                            </div>

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
        color: #e5e7eb;
    }

    .form-control {
        background: rgba(15, 23, 42, 0.75) !important;
        color: #ffffff !important;
        border: 1px solid rgba(255, 255, 255, 0.14) !important;
        border-radius: 18px !important;
        box-shadow: none !important;
    }

    .form-control:focus {
        background: rgba(15, 23, 42, 0.92) !important;
        color: #ffffff !important;
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25) !important;
    }

    .form-control:disabled {
        opacity: 0.8 !important;
    }

    .form-control::placeholder {
        color: rgba(255, 255, 255, 0.65) !important;
    }

    .card {
        background: linear-gradient(
            135deg,
            rgba(30, 41, 59, 0.95),
            rgba(49, 46, 129, 0.92)
        ) !important;

        border: 1px solid rgba(255, 255, 255, 0.08) !important;
        border-radius: 24px !important;
        overflow: hidden;
    }

    .card-header {
        background: rgba(255, 255, 255, 0.04) !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06) !important;
    }

    .card-title,
    .content-header h1 {
        color: #ffffff !important;
        font-weight: 700;
    }

    .btn-primary,
    .btn-secondary {
        border-radius: 16px !important;
    }

    .custom-control-label {
        color: #ffffff !important;
    }

</style>

@stop

@section('js')

<script>

    @if ($errors->any())

        Swal.fire({
            icon: 'error',
            title: 'Errores en el formulario',
            html: `
                <ul style="text-align:left;">
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
