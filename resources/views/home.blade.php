@extends('adminlte::page')

@section('title')

@section('content_header')
    <center><h1>Sistema Estadistico</h1></center>
@stop

@section('content')
    <div class="row">

        <div class="col-md-3 col-sm-6 col-12">
            <div class="info-box">
                <span class="info-box-icon bg-navy"><i class="fa-solid fa-car-side"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text"><b>Añadir nuevo Accidente</b></span>
                    <a href="{{ url('hechos/create') }}" class="btn btn-primary btn-sm">Acceder</a>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 col-12">
            <div class="info-box">
                <span class="info-box-icon bg-teal"><i class="fas fa-user-check"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text"><b>Añadir pase de lista</b></span>
                    <a href="{{ url('listas/create') }}" class="btn btn-primary btn-sm">Acceder</a>
                </div>
            </div>
        </div>

         <div class="col-md-3 col-sm-6 col-12">
            <div class="info-box">
                <span class="info-box-icon bg-lime"><i class="fas fa-tasks"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text"><b>Actividades (En desarrollo)</b></span>
                    <a href="{{ url('#') }}" class="btn btn-primary btn-sm">Acceder</a>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 col-12">
            <div class="info-box">
                <span class="info-box-icon bg-maroon"><i class="fa-solid fa-truck-moving"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text"><b>Grúas</b></span>
                    <a href="{{ url('gruas') }}" class="btn btn-primary btn-sm">Acceder</a>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 col-12">
            <div class="info-box">
                <span class="info-box-icon bg-info"><i class="fas fa-file-alt"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text"><b>Formatos</b></span>
                    <a href="{{ url('formatos') }}" class="btn btn-primary btn-sm">Acceder</a>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 col-12">
            <div class="info-box">
                <span class="info-box-icon bg-warning"><i class="fas fa-gavel"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text"><b>Solicitar número Dictamen</b></span>
                    <a href="{{ url('dictamenes/create') }}" class="btn btn-primary btn-sm">Acceder</a>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 col-12">
            <div class="info-box">
                <span class="info-box-icon bg-fuchsia"><i class="fas fa-envelope-open-text"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text"><b>Oficios</b></span>
                    <a href="{{ url('oficios') }}" class="btn btn-primary btn-sm">Acceder</a>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 col-12">
            <div class="info-box">
                <span class="info-box-icon bg-success"><i class="fa-solid fa-table-cells"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text"><b>Estadisticas (En Desarrollo)</b></span>
                    <a href="{{ url('#') }}" class="btn btn-primary btn-sm">Acceder</a>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 col-12">
            <div class="info-box">
                <span class="info-box-icon bg-indigo"><i class="fas fa-search"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text"><b>Búsqueda</b></span>
                    <a href="{{ url('busqueda') }}" class="btn btn-primary btn-sm">Acceder</a>
                </div>
            </div>
        </div>

    </div>
@stop

@section('css')
    {{-- Add here extra stylesheets --}}
    {{-- <link rel="stylesheet" href="/css/admin_custom.css"> --}}
@stop

@section('js')
    <script> console.log("¿Alguien Lee esto?"); </script>
@stop
