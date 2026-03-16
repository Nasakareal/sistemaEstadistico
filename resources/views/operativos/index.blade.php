@extends('adminlte::page')

@section('title', 'Operativos')

@section('content_header')
    <div class="d-flex align-items-center justify-content-between flex-wrap">
        <h1 class="mb-0">Operativos</h1>

        @can('crear operativos')
        <a href="{{ route('operativos.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nuevo operativo
        </a>
        @endcan
    </div>
@stop

@section('content')

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif

@hasanyrole('Superadmin|Administrador|Coordinador')
<div class="card card-outline card-success mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between">
            <div class="mb-2 mb-md-0">
                <h5 class="mb-1">
                    <i class="fab fa-whatsapp text-success mr-1"></i>
                    Compartir consolidado diario
                </h5>
                <small class="text-muted">
                    Genera el texto consolidado del día {{ \Carbon\Carbon::parse($fechaSeleccionada)->format('d/m/Y') }} con todos los destacamentos visibles.
                </small>
            </div>

            <div class="d-flex flex-wrap">
                <a href="{{ $whatsappUrl }}"
                   target="_blank"
                   class="btn btn-success mr-2 mb-2">
                    <i class="fab fa-whatsapp"></i> Enviar por WhatsApp
                </a>

                <button type="button"
                        class="btn btn-outline-secondary mb-2"
                        onclick="copiarTextoWhatsapp()">
                    <i class="far fa-copy"></i> Copiar texto
                </button>
            </div>
        </div>
    </div>
</div>
@endhasanyrole

<div class="card">
    <div class="card-header">
        <form method="GET" action="{{ route('operativos.index') }}" class="form-inline d-flex flex-wrap">
            <label class="mr-2 mb-2 mb-md-0">Fecha</label>

            <input
                type="date"
                name="fecha"
                value="{{ request('fecha', $fechaSeleccionada) }}"
                class="form-control form-control-sm mr-3 mb-2 mb-md-0"
                onchange="this.form.submit()"
            >

            <label class="mr-2 mb-2 mb-md-0">Tipo</label>

            <select
                name="operativo_catalogo_id"
                class="form-control form-control-sm mr-3 mb-2 mb-md-0"
                onchange="this.form.submit()"
            >
                <option value="">Todos</option>

                @foreach($catalogos as $c)
                    <option
                        value="{{ $c->id }}"
                        {{ request('operativo_catalogo_id') == $c->id ? 'selected' : '' }}
                    >
                        {{ $c->nombre }}
                    </option>
                @endforeach
            </select>

            <input
                type="text"
                name="q"
                value="{{ request('q') }}"
                placeholder="Buscar..."
                class="form-control form-control-sm mr-2 mb-2 mb-md-0"
            >

            <button class="btn btn-sm btn-secondary mb-2 mb-md-0" type="submit">
                <i class="fas fa-search"></i>
            </button>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="thead-light">
                    <tr>
                        <th style="width:120px;">Fecha</th>
                        <th style="width:100px;">Hora</th>
                        <th>Descripción</th>
                        <th style="width:220px;">Lugar</th>
                        <th style="width:180px;">Unidad</th>
                        <th style="width:180px;">Delegación</th>
                        <th style="width:140px;">Operativos</th>
                        <th style="width:240px;" class="text-right">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($operativos as $o)
                        <tr>
                            <td>
                                {{ !empty($o->fecha) ? \Carbon\Carbon::parse($o->fecha)->format('d/m/Y') : '-' }}
                            </td>

                            <td>
                                {{ !empty($o->hora) ? \Carbon\Carbon::parse($o->hora)->format('H:i') : '-' }}
                            </td>

                            <td>
                                {{ \Illuminate\Support\Str::limit($o->descripcion, 80) }}
                            </td>

                            <td>
                                {{ $o->lugar ?? '-' }}
                            </td>

                            <td>
                                {{ $o->unidad->nombre ?? 'SIN UNIDAD' }}
                            </td>

                            <td>
                                {{ $o->delegacion->nombre ?? 'SIN DELEGACIÓN' }}
                            </td>

                            <td>
                                <span class="badge badge-info">
                                    {{ $o->total_operativos ?? 0 }}
                                </span>
                            </td>

                            <td class="text-right">
                                <a href="{{ route('operativos.show', $o->captura_uuid) }}"
                                   class="btn btn-sm btn-info"
                                   title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>

                                @can('editar operativos')
                                <a href="{{ route('operativos.edit', $o->captura_uuid) }}"
                                   class="btn btn-sm btn-success"
                                   title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @endcan

                                @can('eliminar operativos')
                                <form action="{{ route('operativos.destroy', $o->captura_uuid) }}"
                                      method="POST"
                                      class="d-inline"
                                      onsubmit="return confirm('¿Eliminar este consolidado de operativos?');">
                                    @csrf
                                    @method('DELETE')

                                    <button class="btn btn-sm btn-danger" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                No hay operativos registrados para la fecha seleccionada.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@stop

@section('js')
<script>
    function copiarTextoWhatsapp() {
        const texto = @json($whatsappTexto);

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(texto)
                .then(function () {
                    alert('Texto copiado correctamente.');
                })
                .catch(function () {
                    copiarTextoWhatsappFallback(texto);
                });
        } else {
            copiarTextoWhatsappFallback(texto);
        }
    }

    function copiarTextoWhatsappFallback(texto) {
        const textarea = document.createElement('textarea');
        textarea.value = texto;
        textarea.style.position = 'fixed';
        textarea.style.left = '-999999px';
        textarea.style.top = '-999999px';
        document.body.appendChild(textarea);
        textarea.focus();
        textarea.select();

        try {
            document.execCommand('copy');
            alert('Texto copiado correctamente.');
        } catch (e) {
            alert('No se pudo copiar el texto.');
        }

        document.body.removeChild(textarea);
    }
</script>
@stop
