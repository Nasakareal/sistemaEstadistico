@extends('adminlte::page')

@section('title', 'Hechos pendientes de revisión')

@section('content_header')
    <h1>Hechos pendientes de revisión</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-danger">
                <div class="card-header">
                    <h3 class="card-title">Pendientes de revisión</h3>
                </div>

                <div class="card-body">

                    @if ($hechos->count() === 0)
                        <div class="alert alert-success">
                            No hay hechos pendientes de revisión.
                        </div>
                    @endif

                    <table id="hechos" class="table table-striped table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha y Hora</th>
                                <th>Ubicación</th>
                                <th>Foto</th>
                                <th>Situación</th>
                                <th>Revisado por</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($hechos as $hecho)
                                <tr>
                                    <td>{{ $hecho->id }}</td>

                                    <td>{{ $hecho->fecha }} {{ $hecho->hora }}</td>

                                    <td>{{ $hecho->calle }}, {{ $hecho->colonia }}, {{ $hecho->municipio }}</td>

                                    <td>
                                        @php
                                            $foto = $hecho->foto_lugar;
                                            $urlFoto = $foto ? app(\App\Services\Fotos\HechoFotoStorage::class)->url($foto) : null;
                                        @endphp

                                        @if ($urlFoto)
                                            <a href="{{ $urlFoto }}" target="_blank">
                                                <img src="{{ $urlFoto }}" class="foto-thumb">
                                            </a>
                                        @else
                                            <span class="text-muted">Sin foto</span>
                                        @endif
                                    </td>

                                    <td>
                                        <span class="badge badge-warning">
                                            {{ $hecho->situacion }}
                                        </span>
                                    </td>

                                    <td>
                                        {{ $hecho->revisadoPor ? $hecho->revisadoPor->name : 'SIN REVISIÓN' }}
                                    </td>

                                    <td>

                                        <a href="{{ route('hechos.show', $hecho->id) }}" class="btn btn-info btn-sm">
                                            <i class="fa-regular fa-eye"></i>
                                        </a>

                                        <a href="{{ route('hechos.edit', $hecho->id) }}" class="btn btn-success btn-sm">
                                            <i class="fa-solid fa-pencil"></i>
                                        </a>

                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="mt-3">
                        {{ $hechos->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
<style>
    .table th, .table td {
        text-align: center;
        vertical-align: middle;
    }

    .foto-thumb{
        width: 72px;
        height: 52px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid rgba(0,0,0,.12);
        background: #f8f9fa;
    }
</style>
@stop

@section('js')
<script>
    $(function () {
        $('#hechos').DataTable({
            paging: false,
            info: false,
            order: [[1, "desc"]],
            responsive: true,
            lengthChange: false,
            autoWidth: false,
        });
    });
</script>
@stop
