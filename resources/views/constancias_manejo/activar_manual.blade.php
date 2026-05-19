@extends('adminlte::page')

@section('title', 'Activar constancia manual')

@section('content_header')
    <h1>Activar constancia manual</h1>
@stop

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title">Captura manual</h3>
            </div>

            <form method="POST" action="{{ route('constancias_manejo.activar_manual.store') }}">
                @csrf

                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Folio</label>
                                <input
                                    type="text"
                                    name="folio"
                                    id="folio"
                                    class="form-control text-uppercase"
                                    value="{{ old('folio', $folio) }}"
                                    maxlength="50"
                                    list="folios-lote"
                                    required
                                    autofocus
                                >
                                <datalist id="folios-lote">
                                    @foreach($constanciasLote as $constanciaLote)
                                        <option value="{{ $constanciaLote->folio }}">
                                    @endforeach
                                </datalist>
                            </div>
                        </div>

                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Nombre del solicitante</label>
                                <input
                                    type="text"
                                    name="nombre_solicitante"
                                    class="form-control text-uppercase"
                                    value="{{ old('nombre_solicitante') }}"
                                    maxlength="255"
                                    required
                                >
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Sexo</label>
                                <select name="sexo" class="form-control" required>
                                    <option value="">Seleccionar</option>
                                    <option value="HOMBRE" {{ old('sexo') === 'HOMBRE' ? 'selected' : '' }}>Hombre</option>
                                    <option value="MUJER" {{ old('sexo') === 'MUJER' ? 'selected' : '' }}>Mujer</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Tipo de licencia</label>
                                <select name="tipo_licencia" class="form-control" required>
                                    <option value="">Seleccionar</option>
                                    @foreach($tiposLicencia as $value => $label)
                                        <option value="{{ $value }}" {{ old('tipo_licencia') === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>CURP</label>
                                <input
                                    type="text"
                                    name="curp"
                                    class="form-control text-uppercase"
                                    value="{{ old('curp') }}"
                                    maxlength="18"
                                >
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group mb-md-0">
                                <label>Telefono</label>
                                <input
                                    type="text"
                                    name="telefono"
                                    class="form-control"
                                    value="{{ old('telefono') }}"
                                    maxlength="20"
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-between">
                    <a href="{{ route('constancias_manejo.index') }}" class="btn btn-secondary">
                        <i class="fa-solid fa-arrow-left"></i> Volver
                    </a>
                    <button type="submit" class="btn btn-success btn-activar">
                        <i class="fa-solid fa-check"></i> Activar
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($constanciasLote->isNotEmpty())
        <div class="col-lg-4">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">Folios del lote</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Folio</th>
                                    <th>Modulo</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($constanciasLote as $constanciaLote)
                                    <tr>
                                        <td><strong>{{ $constanciaLote->folio }}</strong></td>
                                        <td>{{ optional($constanciaLote->modulo)->nombre ?? 'N/A' }}</td>
                                        <td class="text-right">
                                            <button
                                                type="button"
                                                class="btn btn-xs btn-outline-primary btn-usar-folio"
                                                data-folio="{{ $constanciaLote->folio }}"
                                            >
                                                Usar
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@stop

@section('js')
<script>
$(document).on('click', '.btn-usar-folio', function () {
    $('#folio').val($(this).data('folio')).trigger('focus');
});

$(document).on('click', '.btn-activar', function (e) {
    e.preventDefault();
    const form = $(this).closest('form');

    Swal.fire({
        title: '¿Activar constancia?',
        text: 'La vigencia empezara en este momento.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Si, activar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
});

@if (session('success'))
Swal.fire({
    icon: 'success',
    title: '{{ session('success') }}',
    timer: 3000,
    showConfirmButton: false
});
@endif

@if (session('error'))
Swal.fire({
    icon: 'error',
    title: '{{ session('error') }}',
    timer: 3500,
    showConfirmButton: false
});
@endif
</script>
@stop
