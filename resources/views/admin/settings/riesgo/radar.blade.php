@extends('adminlte::page')

@section('title', 'Radar de Aceleración de Riesgo')

@section('content_header')
    <h1>Radar de Aceleración de Riesgo (Últimos 14 días)</h1>
@stop

@section('content')

<div class="card">
    <div class="card-body">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Colonia</th>
                    <th>Últimos 14</th>
                    <th>Previos 14</th>
                    <th>Crecimiento %</th>
                    <th>Score</th>
                    <th>Semáforo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $row)
                <tr>
                    <td>{{ $row->colonia }}</td>
                    <td>{{ $row->hechos_ultimos14 }}</td>
                    <td>{{ $row->hechos_previos14 }}</td>
                    <td>{{ $row->crecimiento }}%</td>
                    <td>{{ $row->score }}</td>
                    <td>
                        @if($row->semaforo == 'ROJO')
                            <span class="badge badge-danger">ROJO</span>
                        @elseif($row->semaforo == 'AMARILLO')
                            <span class="badge badge-warning">AMARILLO</span>
                        @else
                            <span class="badge badge-success">VERDE</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@stop
