{{-- resources/views/actividades/show.blade.php --}}

@extends('adminlte::page')

@section('title', 'Detalle de Actividad')

@section('content_header')
    <div class="d-flex align-items-center justify-content-between">
        <h1 class="mb-0">Detalle de Actividad</h1>

        <div class="btn-group">
            @can('editar actividades')
                <a href="{{ route('actividades.edit', $actividad->id) }}" class="btn btn-warning">
                    <i class="fa-solid fa-pen-to-square"></i> Editar
                </a>
            @endcan

            <a href="{{ route('actividades.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Volver
            </a>

            @can('eliminar actividades')
                <form action="{{ route('actividades.destroy', $actividad->id) }}" method="POST" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger"
                        onclick="return confirm('¿Seguro que deseas eliminar esta actividad?');">
                        <i class="fa-solid fa-trash"></i> Eliminar
                    </button>
                </form>
            @endcan
        </div>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Información</h3>
                </div>

                <div class="card-body">
                    <div class="row" style="row-gap:18px;">
                        <div class="col-md-4">
                            <label class="help-muted d-block">Nombre</label>
                            <div class="form-control-like">
                                {{ $actividad->nombre ?? '—' }}
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="help-muted d-block">Categoría</label>
                            <div class="form-control-like">
                                {{ optional($actividad->categoria)->nombre ?? '—' }}
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="help-muted d-block">Subcategoría</label>
                            <div class="form-control-like">
                                {{ optional($actividad->subcategoria)->nombre ?? 'Sin subcategoría' }}
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="help-muted d-block">Cantidad</label>
                            <div class="form-control-like">
                                {{ (int)($actividad->cantidad ?? 1) }}
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="help-muted d-block">Fecha de registro</label>
                            <div class="form-control-like">
                                {{ optional($actividad->created_at)->format('Y-m-d H:i') ?? '—' }}
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="help-muted d-block">Última actualización</label>
                            <div class="form-control-like">
                                {{ optional($actividad->updated_at)->format('Y-m-d H:i') ?? '—' }}
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-12">
                            <label class="help-muted d-block mb-2">Foto</label>

                            @php
                                $fotoPath = $actividad->foto_path ?? null;
                                $urlFoto = $fotoPath ? asset('storage/' . ltrim($fotoPath, '/')) : null;
                            @endphp

                            @if ($urlFoto)
                                <a href="{{ $urlFoto }}" target="_blank" rel="noopener">
                                    <img src="{{ $urlFoto }}" alt="foto" class="foto-big">
                                </a>

                                @if (!empty($actividad->foto_nombre_original))
                                    <div class="mt-2">
                                        <small class="help-muted">Archivo: {{ $actividad->foto_nombre_original }}</small>
                                    </div>
                                @endif
                            @else
                                <div class="alert alert-warning mb-0">
                                    No hay foto registrada para esta actividad.
                                </div>
                            @endif
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .help-muted { color: rgba(234,240,255,.65); }

        .form-control-like{
            color: #eaf0ff;
            background-color: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 12px;
            padding: .6rem .75rem;
            min-height: 38px;
            display:flex;
            align-items:center;
        }

        .foto-big{
            width: 100%;
            max-width: 820px;
            height: auto;
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,.16);
            background: rgba(255,255,255,.06);
        }
    </style>
@stop

@section('js')
    <script>
        // Confirmación con SweetAlert si está disponible (si no, se queda el confirm del botón)
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('form[action*="actividades"][method="POST"] button.btn-danger')?.closest('form');
            if (!form || typeof Swal === 'undefined') return;

            const btn = form.querySelector('button[type="submit"]');
            if (!btn) return;

            btn.onclick = function (e) {
                e.preventDefault();

                Swal.fire({
                    icon: 'warning',
                    title: '¿Eliminar actividad?',
                    text: 'Esta acción no se puede deshacer.',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) form.submit();
                });
            };
        });
    </script>
@stop
