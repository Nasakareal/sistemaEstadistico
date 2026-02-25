@extends('adminlte::page')

@section('title', 'Demo Índice de Riesgo')

@section('content_header')
    <h1>Índice de Riesgo por Colonia</h1>
@stop

@section('content')

<div class="card">
    <div class="card-body">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Colonia</th>
                    <th>Hechos</th>
                    <th>Vehículos</th>
                    <th>Lesionados</th>
                    <th>Score</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $row)
                <tr>
                    <td>{{ $row->colonia }}</td>
                    <td>{{ $row->hechos }}</td>
                    <td>{{ $row->vehiculos }}</td>
                    <td>{{ $row->lesionados }}</td>
                    <td><strong>{{ number_format($row->score,2) }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@stop
