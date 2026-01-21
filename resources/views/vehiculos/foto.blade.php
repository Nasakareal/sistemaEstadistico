@extends('adminlte::page')

@section('title', 'Foto del Vehículo')

@section('content_header')
    <h1>Foto del Vehículo (Hecho: {{ $hecho->folio_c5i }})</h1>
@stop

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-primary">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">
                    Vehículo: {{ $vehiculo->marca }} {{ $vehiculo->linea }} | Placas: {{ $vehiculo->placas }}
                </h3>
                <div class="card-tools">
                    <a href="{{ route('vehiculos.index', $hecho->id) }}" class="btn btn-secondary btn-sm">
                        <i class="fa-solid fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>

            <div class="card-body">
                @if ($vehiculo->fotos)
                    <div class="mb-3 text-center">
                        <img src="{{ asset('storage/'.$vehiculo->fotos) }}" alt="Foto del vehículo" class="img-fluid rounded" style="max-height: 360px;">
                    </div>

                    <div class="text-center mb-4">
                        <form action="{{ route('vehiculos.foto.destroy', ['hecho' => $hecho->id, 'vehiculo' => $vehiculo->id]) }}"
                              method="POST"
                              style="display:inline-block;"
                              onsubmit="return confirm('¿Eliminar la foto actual?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">
                                <i class="fa-solid fa-trash"></i> Eliminar Foto
                            </button>
                        </form>
                    </div>
                @else
                    <div class="alert alert-info">
                        Este vehículo no tiene foto todavía.
                    </div>
                @endif

                <hr>

                <form action="{{ route('vehiculos.foto.update', ['hecho' => $hecho->id, 'vehiculo' => $vehiculo->id]) }}"
                      method="POST"
                      enctype="multipart/form-data">
                    @csrf

                    <div class="form-group">
                        <label>Subir / Reemplazar foto</label>
                        <input type="file"
                               name="foto"
                               class="form-control @error('foto') is-invalid @enderror"
                               accept="image/jpeg,image/png,image/webp">

                        <small class="text-muted">
                            Formatos: JPG/PNG/WEBP. Máx: 2MB.
                        </small>

                        @error('foto')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button class="btn btn-primary">
                        <i class="fa-solid fa-upload"></i> Guardar Foto
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
    @if (session('success'))
        Swal.fire({
            position: 'center',
            icon: 'success',
            title: '{{ session('success') }}',
            showConfirmButton: false,
            timer: 2500
        });
    @endif
</script>
@stop
