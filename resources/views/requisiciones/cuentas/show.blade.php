@extends('adminlte::page')

@section('title', 'Detalles de la Requisición')

@section('content_header')
    <h1>Detalles de la Requisición</h1>
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
                        {{-- Número de Requisición y Fecha --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="numero_requisicion">Número de Requisición</label>
                                <p class="form-control-static">{{ $requisicion->numero_requisicion }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fecha_requisicion">Fecha de Requisición</label>
                                <p class="form-control-static">{{ $requisicion->fecha_requisicion->format('d/m/Y') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        {{-- UR y Departamento --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="ur">Unidad Responsable (UR)</label>
                                <p class="form-control-static">{{ $requisicion->ur }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="departamento">Departamento</label>
                                <p class="form-control-static">{{ $requisicion->departamento }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        {{-- Partida y Producto/Material --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="partida">Partida</label>
                                <p class="form-control-static">{{ $requisicion->partida }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="producto_material">Producto o Material</label>
                                <p class="form-control-static">{{ $requisicion->producto_material }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        {{-- Justificación y Proveedor --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="justificacion">Justificación</label>
                                <p class="form-control-static">{{ $requisicion->justificacion }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="proveedor">Proveedor</label>
                                <p class="form-control-static">{{ $requisicion->proveedor }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        {{-- Monto y Estado --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="monto">Monto</label>
                                <p class="form-control-static">${{ number_format($requisicion->monto, 2) }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status_requisicion">Estado</label>
                                <p class="form-control-static">{{ $requisicion->status_requisicion }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        {{-- Oficio de Pago y Número de Factura --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="oficio_pago">Oficio de Pago</label>
                                <p class="form-control-static">{{ $requisicion->oficio_pago ?? 'No especificado' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="numero_factura">Número de Factura</label>
                                <p class="form-control-static">{{ $requisicion->numero_factura ?? 'No especificado' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        {{-- Fecha de Entrega a Recursos Financieros y Fecha de Pago --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fecha_entrega_rf">Fecha de Entrega a Recursos Financieros</label>
                                <p class="form-control-static">{{ $requisicion->fecha_entrega_rf ? $requisicion->fecha_entrega_rf->format('d/m/Y') : 'No especificada' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fecha_pago">Fecha de Pago</label>
                                <p class="form-control-static">{{ $requisicion->fecha_pago ? $requisicion->fecha_pago->format('d/m/Y') : 'No especificada' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        {{-- Banco y Referencia --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="banco">Banco</label>
                                <p class="form-control-static">{{ $requisicion->banco ?? 'No especificado' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="referencia">Referencia</label>
                                <p class="form-control-static">{{ $requisicion->referencia ?? 'No especificada' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        {{-- Observaciones --}}
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="observaciones">Observaciones</label>
                                <p class="form-control-static">{{ $requisicion->observaciones ?? 'Sin observaciones' }}</p>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <div class="row">
                        {{-- Botón de regreso --}}
                        <div class="col-md-12 text-center">
                            <a href="{{ route('requisiciones.cuenta.index', $requisicion->cuenta_bancaria_id) }}" class="btn btn-secondary">
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
            margin-top: 0.5rem;
        }
    </style>
@stop

@section('js')
    <script> console.log("Vista de detalles de la requisición cargada correctamente."); </script>
@stop
