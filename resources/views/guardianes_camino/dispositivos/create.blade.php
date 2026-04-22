@extends('adminlte::page')

@section('title', 'Crear Dispositivo Guardianes del Camino')

@section('content_header')
    <h1>Captura de Dispositivo - Guardianes del Camino</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Registro diario de actividades</h3>
                </div>

                <div class="card-body">
                    <form
                        id="form_dispositivo"
                        action="{{ route('guardianes_camino.dispositivos.store') }}"
                        method="POST"
                        enctype="multipart/form-data"
                    >
                        @csrf

                        @include('guardianes_camino.dispositivos.partials._form_base')
                        @include('guardianes_camino.dispositivos.partials._campos_dinamicos')
                        @include('guardianes_camino.dispositivos.partials._resultados_extra')
                        @include('guardianes_camino.dispositivos.partials._vehiculos_personas')

                        @if (view()->exists('guardianes_camino.dispositivos.partials._georreferencia'))
                            @include('guardianes_camino.dispositivos.partials._georreferencia')
                        @endif

                        @if (view()->exists('guardianes_camino.dispositivos.partials._narrativa'))
                            @include('guardianes_camino.dispositivos.partials._narrativa')
                        @endif

                        @if (view()->exists('guardianes_camino.dispositivos.partials._apoyo_usuario'))
                            @include('guardianes_camino.dispositivos.partials._apoyo_usuario')
                        @endif

                        @if (view()->exists('guardianes_camino.dispositivos.partials._responsable'))
                            @include('guardianes_camino.dispositivos.partials._responsable')
                        @endif

                        @if (view()->exists('guardianes_camino.dispositivos.partials._fotos'))
                            @include('guardianes_camino.dispositivos.partials._fotos')
                        @endif

                        @include('guardianes_camino.dispositivos.partials._observaciones')

                        <hr>

                        <div class="row">
                            <div class="col-md-12 text-center">
                                <div class="form-group mb-0">
                                    <button id="btn_submit" type="submit" class="btn btn-primary">
                                        <i class="fa-solid fa-check"></i> Registrar
                                    </button>

                                    <a href="{{ route('guardianes_camino.index') }}" class="btn btn-secondary">
                                        <i class="fa-solid fa-ban"></i> Cancelar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>

                    @include('guardianes_camino.dispositivos.partials._scripts_config')
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <style>
        @include('actividades.partials.vehiculos_styles')

        .form-group label {
            font-weight: bold;
            color: #eaf0ff;
        }

        .form-control,
        select.form-control,
        textarea.form-control {
            color: #eaf0ff;
            background-color: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 12px;
        }

        .form-control::placeholder,
        textarea.form-control::placeholder {
            color: rgba(234,240,255,.55);
        }

        select option {
            color: #111 !important;
            background-color: #ffffff !important;
        }

        select option:checked {
            background-color: #dbeafe !important;
            color: #0b1220 !important;
        }

        .form-control:focus,
        select:focus,
        textarea:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(45,168,255,.35);
            border-color: rgba(45,168,255,.55);
        }

        .flatpickr-calendar {
            border-radius: 14px;
            overflow: hidden;
        }

        .flatpickr-time input,
        .flatpickr-time .flatpickr-am-pm {
            font-size: 16px;
        }

        .alert-info,
        .alert-secondary {
            border-radius: 14px;
        }

        .campo-oculto {
            display: none !important;
        }
    </style>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="{{ asset('js/guardianes_camino_dispositivos_form.js') }}?v={{ filemtime(public_path('js/guardianes_camino_dispositivos_form.js')) }}"></script>

    @if ($errors->any())
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Errores en el formulario',
                html: `
                    <ul style="text-align: left;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                `,
                confirmButtonText: 'Aceptar'
            });
        </script>
    @endif
@stop
