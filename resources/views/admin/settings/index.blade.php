@extends('adminlte::page')

@section('title')

@section('content_header')
    <h1>Configuraciones del Sistema</h1>
@stop

@section('content')
    <div class="row">

        <div class="col-md-3 col-sm-6 col-12">
            <div class="info-box">
                <span class="info-box-icon bg-orange"><i class="fa-solid fa-user"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text"><b>Usuarios</b></span>
                    <a href="{{ url('/admin/settings/users') }}" class="btn btn-primary btn-sm">Acceder</a>
                </div>
            </div>
        </div>

         <div class="col-md-3 col-sm-6 col-12">
            <div class="info-box">
                <span class="info-box-icon bg-navy"><i class="fa-regular fa-flag"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text"><b>Roles</b></span>
                    <a href="{{ url('/admin/settings/roles') }}" class="btn btn-primary btn-sm">Acceder</a>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 col-12">
            <div class="info-box">
                <span class="info-box-icon bg-success"><i class="fa-solid fa-chart-pie"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text"><b>Estadisticas</b></span>
                    <a href="{{ url('#') }}" class="btn btn-primary btn-sm">Acceder</a>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 col-12">
            <div class="info-box">
                <span class="info-box-icon bg-danger"><i class="fa-solid fa-dumpster"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text"><b>Vaciar Base de Datos</b></span>
                    <a href="{{ url('/admin/vaciados') }}" class="btn btn-primary btn-sm">Acceder</a>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .info-box-content {
            text-align: center;
        }
        .info-box-text {
            font-weight: bold;
        }
    </style>
@stop

@section('js')
    <script> console.log("Configuraciones del Sistema cargadas con AdminLTE."); </script>
@stop
