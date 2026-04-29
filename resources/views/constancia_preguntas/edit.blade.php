@extends('adminlte::page')

@section('title', 'Editar Pregunta')

@section('content_header')
    <h1>Editar Pregunta de Examen</h1>
@stop

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Banco de preguntas por tipo de licencia</h3>
            </div>

            <div class="card-body">
                <form action="{{ route('constancias_manejo.preguntas.update', $pregunta) }}" method="POST">
                    @include('constancia_preguntas._form')
                </form>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
    .content-wrapper {
        background: linear-gradient(180deg, #111827 0%, #0f172a 100%) !important;
    }

    .content-header h1 {
        color: #f8fafc;
        font-weight: 800;
    }

    .card {
        background: #0f172a;
        border: 1px solid #334155;
        border-radius: 18px;
        box-shadow: 0 18px 40px rgba(0, 0, 0, 0.6);
        overflow: visible;
    }

    .card-header {
        background: #1e293b;
        border-bottom: 1px solid #334155;
        border-radius: 18px 18px 0 0;
        padding: 18px 22px;
    }

    .card-title {
        color: #f8fafc;
        font-weight: 700;
        font-size: 20px;
    }

    .card-body {
        padding: 26px;
        overflow: visible;
    }

    .form-group {
        margin-bottom: 22px;
    }

    .form-group label {
        display: block;
        font-weight: 700;
        color: #e2e8f0;
        margin-bottom: 8px;
    }

    .form-control {
        height: 46px;
        border-radius: 12px;
        background-color: #020617 !important;
        border: 1px solid #3b82f6 !important;
        color: #ffffff !important;
        font-weight: 600;
        box-shadow: none !important;
    }

    textarea.form-control {
        height: auto;
        min-height: 110px;
    }

    .form-control:focus {
        background-color: #020617 !important;
        color: #ffffff !important;
        border-color: #60a5fa !important;
        box-shadow: 0 0 0 0.15rem rgba(96, 165, 250, 0.3) !important;
    }

    select.form-control {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background-image: linear-gradient(45deg, transparent 50%, #ffffff 50%), linear-gradient(135deg, #ffffff 50%, transparent 50%);
        background-position: calc(100% - 18px) 20px, calc(100% - 12px) 20px;
        background-size: 6px 6px, 6px 6px;
        background-repeat: no-repeat;
        padding-right: 42px;
    }

    select.form-control option {
        background-color: #020617 !important;
        color: #ffffff !important;
        font-weight: 600;
    }

    select.form-control option:checked,
    select.form-control option:hover {
        background-color: #2563eb !important;
        color: #ffffff !important;
    }

    .answer-row .input-group-text {
        min-width: 44px;
        justify-content: center;
        background-color: #1e293b;
        border: 1px solid #3b82f6;
        color: #ffffff;
        font-weight: 700;
        border-radius: 12px 0 0 12px;
    }

    .input-group .form-control {
        border-radius: 0 12px 12px 0;
    }

    .btn {
        border-radius: 12px;
        font-weight: 700;
        padding: 10px 18px;
    }
</style>
@stop

@section('js')
@if (session('success'))
    <script>
        Swal.fire({
            icon: 'success',
            title: '{{ session('success') }}',
            timer: 2500,
            showConfirmButton: false
        });
    </script>
@endif

@if ($errors->any())
    <script>
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
    </script>
@endif
@stop

