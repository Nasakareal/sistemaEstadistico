@extends('adminlte::page')

@section('title', 'Kilometrajes de Patrulla')

@section('content_header')
    <h1>Kilometrajes - Patrulla {{ $patrulla->numero_economico }}</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Historial de Kilometraje</h3>

                    <div class="card-tools d-flex align-items-center" style="gap:10px;">

                        {{-- SELECT PARA CAMBIAR DE PATRULLA --}}
                        <div class="d-inline-block">
                            <select
                                id="patrulla_select"
                                class="form-control form-control-sm"
                                style="min-width: 220px;"
                                onchange="if(this.value){ window.location.href = this.options[this.selectedIndex].dataset.url; }"
                            >
                                @foreach ($patrullas as $p)
                                    <option
                                        value="{{ $p->id }}"
                                        data-url="{{ route('patrullas.kilometrajes.index', $p->id) }}"
                                        {{ (int)$p->id === (int)$patrulla->id ? 'selected' : '' }}
                                    >
                                        {{ $p->numero_economico }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        @can('crear kilometrajes patrullas')
                            <a href="{{ route('patrullas.kilometrajes.create', $patrulla->id) }}"
                               class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-plus"></i> Nuevo Registro
                            </a>
                        @endcan

                        <a href="{{ route('patrullas.show', $patrulla->id) }}"
                           class="btn btn-secondary btn-sm">
                            <i class="fa-solid fa-arrow-left"></i> Volver
                        </a>

                    </div>
                </div>

                <div class="card-body">
                    <table id="kilometrajes" class="table table-striped table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Fecha</th>
                                <th>Km Reportado</th>
                                <th>Km Recorridos</th>
                                <th>Capturado Por</th>
                                <th>Observaciones</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($kilometrajes as $index => $registro)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ optional($registro->fecha)->format('d-m-Y') }}</td>
                                    <td>{{ number_format($registro->kilometraje_reportado) }}</td>
                                    <td>
                                        @if($registro->kilometros_recorridos !== null)
                                            {{ number_format($registro->kilometros_recorridos) }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $registro->usuario->name ?? '—' }}</td>
                                    <td>{{ $registro->observaciones ?? '—' }}</td>
                                    <td>
                                        <div class="btn-group" role="group">

                                            @can('editar kilometrajes patrullas')
                                                <a href="{{ route('patrullas.kilometrajes.edit', [$patrulla->id, $registro->id]) }}"
                                                   class="btn btn-success btn-sm">
                                                    <i class="fa-regular fa-pen-to-square"></i>
                                                </a>
                                            @endcan

                                            @can('eliminar kilometrajes patrullas')
                                                <form action="{{ route('patrullas.kilometrajes.destroy', [$patrulla->id, $registro->id]) }}"
                                                      method="POST" style="display:inline-block;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="btn btn-danger btn-sm delete-btn">
                                                        <i class="fa-regular fa-trash-can"></i>
                                                    </button>
                                                </form>
                                            @endcan

                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

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
</style>
@stop


@section('js')
<script>
    $(function () {
        $('#kilometrajes').DataTable({
            pageLength: 10,
            language: {
                emptyTable: "No hay registros",
                info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                infoEmpty: "Mostrando 0 a 0 de 0 registros",
                infoFiltered: "(Filtrado de _MAX_ total registros)",
                lengthMenu: "Mostrar _MENU_ registros",
                loadingRecords: "Cargando...",
                processing: "Procesando...",
                search: "Buscar:",
                zeroRecords: "Sin resultados encontrados",
                paginate: {
                    first: "Primero",
                    last: "Último",
                    next: "Siguiente",
                    previous: "Anterior"
                }
            },
            responsive: true,
            lengthChange: true,
            autoWidth: false,
            buttons: [
                {
                    extend: 'collection',
                    text: 'Opciones',
                    buttons: [
                        { extend: 'copy', text: 'Copiar' },
                        { extend: 'pdf', text: 'PDF' },
                        { extend: 'csv', text: 'CSV' },
                        { extend: 'excel', text: 'Excel' },
                        { extend: 'print', text: 'Imprimir' }
                    ]
                },
                { extend: 'colvis', text: 'Visor de columnas' }
            ],
        }).buttons().container().appendTo('#kilometrajes_wrapper .col-md-6:eq(0)');

        $('#patrulla_select').on('change', function () {
            const patrullaId = $(this).val();
            if (!patrullaId) return;

            const tpl = @json(route('patrullas.kilometrajes.index', ['patrulla' => ':id']));
            window.location.href = tpl.replace(':id', patrullaId);
        });
    });

    @if (session('success'))
        Swal.fire({
            position: 'center',
            icon: 'success',
            title: '{{ session('success') }}',
            showConfirmButton: false,
            timer: 4000
        });
    @endif

    $(document).on('click', '.delete-btn', function (e) {
        e.preventDefault();

        let form = $(this).closest('form');

        Swal.fire({
            title: '¿Eliminar registro?',
            text: "Esta acción no se puede revertir.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
</script>
@stop
