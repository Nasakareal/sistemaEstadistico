@extends('adminlte::page')

@section('title', 'Gestionar Permisos')

@section('content_header')
    <h1>Gestionar Permisos para el Rol: {{ $role->name }}</h1>
@stop

@section('content')
    @php
        $actionsOrder = ['ver', 'crear', 'editar', 'borrar'];
        $labels = ['ver' => 'Ver', 'crear' => 'Crear', 'editar' => 'Editar', 'borrar' => 'Borrar'];

        $synonyms = [
            'ver' => ['ver', 'listar', 'consulta', 'consultar'],
            'crear' => ['crear', 'registrar', 'agregar', 'alta', 'nuevo', 'nueva'],
            'editar' => ['editar', 'actualizar', 'modificar', 'cambiar'],
            'borrar' => ['borrar', 'eliminar', 'quitar'],
        ];

        $rows = [];

        foreach ($permissions as $perm) {
            $name = trim((string) $perm->name);
            $lower = mb_strtolower($name);

            $action = null;
            $match = null;

            foreach ($synonyms as $canonical => $words) {
                foreach ($words as $w) {
                    $prefix1 = $w . ' ';
                    $prefix2 = $w . '_';
                    $prefix3 = $w . '-';

                    if (mb_strpos($lower, $prefix1) === 0 || mb_strpos($lower, $prefix2) === 0 || mb_strpos($lower, $prefix3) === 0) {
                        $action = $canonical;
                        $match = $w;
                        break 2;
                    }

                    if ($lower === $w) {
                        $action = $canonical;
                        $match = $w;
                        break 2;
                    }
                }
            }

            if ($action) {
                $module = trim(mb_substr($name, mb_strlen($match)));
                $module = ltrim($module, " _-");
                $module = trim($module);
            } else {
                $module = $name;
            }

            if ($module === '') $module = $name;

            if (!isset($rows[$module])) {
                $rows[$module] = [
                    'module' => $module,
                    'perms' => [
                        'ver' => null,
                        'crear' => null,
                        'editar' => null,
                        'borrar' => null,
                    ],
                    'otros' => [],
                ];
            }

            if ($action && array_key_exists($action, $rows[$module]['perms']) && !$rows[$module]['perms'][$action]) {
                $rows[$module]['perms'][$action] = $perm;
            } else {
                $rows[$module]['otros'][] = $perm;
            }
        }

        ksort($rows);
    @endphp

    <div class="row">
        <div class="col-12">
            <form action="{{ route('roles.assignPermissions', $role->id) }}" method="POST">
                @csrf

                <div class="mb-3">
                    <div class="input-group">
                        <input type="text" id="permSearch" class="form-control" placeholder="Buscar módulo o permiso...">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-secondary" id="btnClearSearch">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mb-3 d-flex flex-wrap" style="gap:10px;">
                    <button type="button" class="btn btn-outline-primary btn-sm" data-mass="ver">Marcar Ver</button>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-mass="crear">Marcar Crear</button>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-mass="editar">Marcar Editar</button>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-mass="borrar">Marcar Borrar</button>

                    <button type="button" class="btn btn-outline-secondary btn-sm" data-mass="unver">Desmarcar Ver</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-mass="uncrear">Desmarcar Crear</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-mass="uneditar">Desmarcar Editar</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-mass="unborrar">Desmarcar Borrar</button>

                    <button type="button" class="btn btn-outline-dark btn-sm" id="btnToggleAll">
                        Seleccionar / Deseleccionar todo
                    </button>
                </div>

                <div class="row" id="cardsWrap">
                    @php $i = 0; @endphp
                    @foreach ($rows as $group)
                        @php
                            $i++;
                            $module = $group['module'];
                            $pver = $group['perms']['ver'];
                            $pcrear = $group['perms']['crear'];
                            $peditar = $group['perms']['editar'];
                            $pborrar = $group['perms']['borrar'];
                            $otros = $group['otros'] ?? [];
                            $hayOtros = count($otros) > 0;

                            $checkedCount = 0;
                            foreach (['ver' => $pver, 'crear' => $pcrear, 'editar' => $peditar, 'borrar' => $pborrar] as $k => $p) {
                                if ($p && in_array($p->id, $rolePermissions)) $checkedCount++;
                            }
                            foreach ($otros as $p) {
                                if (in_array($p->id, $rolePermissions)) $checkedCount++;
                            }

                            $searchText = $module;
                            foreach (['ver' => $pver, 'crear' => $pcrear, 'editar' => $peditar, 'borrar' => $pborrar] as $p) {
                                if ($p) $searchText .= ' ' . $p->name;
                            }
                            foreach ($otros as $p) $searchText .= ' ' . $p->name;
                        @endphp

                        <div class="col-md-6 col-lg-4 perm-card" data-search="{{ mb_strtolower($searchText) }}">
                            <div class="card card-outline card-warning h-100">
                                <div class="card-header">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <h3 class="card-title text-truncate" title="{{ $module }}">
                                            {{ $module }}
                                        </h3>
                                        <span class="badge badge-info">
                                            {{ $checkedCount }} seleccionados
                                        </span>
                                    </div>
                                </div>

                                <div class="card-body">

                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead>
                                                <tr>
                                                    <th style="width:70%;">Acción</th>
                                                    <th style="width:30%; text-align:center;">Permitir</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($actionsOrder as $act)
                                                    @php $p = $group['perms'][$act]; @endphp
                                                    <tr>
                                                        <td>{{ $labels[$act] }}</td>
                                                        <td style="text-align:center;">
                                                            @if ($p)
                                                                <input
                                                                    type="checkbox"
                                                                    class="perm-check perm-{{ $act }}"
                                                                    name="permissions[]"
                                                                    value="{{ $p->id }}"
                                                                    {{ in_array($p->id, $rolePermissions) ? 'checked' : '' }}
                                                                >
                                                            @else
                                                                <span class="text-muted">—</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    @if ($hayOtros)
                                        <hr>
                                        <div class="small text-muted mb-2">Otros permisos</div>

                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Permiso</th>
                                                        <th style="width:30%; text-align:center;">Permitir</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($otros as $p)
                                                        <tr>
                                                            <td>{{ $p->name }}</td>
                                                            <td style="text-align:center;">
                                                                <input
                                                                    type="checkbox"
                                                                    class="perm-check perm-otros"
                                                                    name="permissions[]"
                                                                    value="{{ $p->id }}"
                                                                    {{ in_array($p->id, $rolePermissions) ? 'checked' : '' }}
                                                                >
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif

                                </div>

                                <div class="card-footer d-flex flex-wrap" style="gap:8px;">
                                    <button type="button" class="btn btn-outline-success btn-sm btnCardAll">Todo</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm btnCardNone">Nada</button>
                                    <button type="button" class="btn btn-outline-primary btn-sm btnCardCrud">Ver/Crear/Editar/Borrar</button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="text-center mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-check"></i> Guardar Permisos
                    </button>
                    <a href="{{ route('roles.index') }}" class="btn btn-secondary">
                        <i class="fa-solid fa-ban"></i> Cancelar
                    </a>
                </div>

            </form>
        </div>
    </div>
@stop

@section('css')
<style>
    #permSearch{ max-width: 520px; }
    .perm-card .card-title{ max-width: 75%; }
    .perm-card .card{ border-radius: 12px; }
    .perm-card .card-body{ padding: 1rem; }
    .perm-card .table td, .perm-card .table th{ vertical-align: middle; }
</style>
@stop

@section('js')
<script>
(function () {
    function setChecks(container, checked) {
        container.querySelectorAll('input.perm-check').forEach(function (el) {
            el.checked = checked;
        });
    }

    function setChecksByClass(className, checked) {
        document.querySelectorAll('input.' + className).forEach(function (el) {
            if (el.offsetParent !== null) el.checked = checked;
        });
    }

    function toggleAllVisible() {
        const visibles = Array.from(document.querySelectorAll('.perm-card')).filter(c => c.offsetParent !== null);
        const checks = visibles.flatMap(c => Array.from(c.querySelectorAll('input.perm-check')));
        const allChecked = checks.length ? checks.every(x => x.checked) : false;
        checks.forEach(x => x.checked = !allChecked);
    }

    function wireCardButtons(card) {
        const btnAll = card.querySelector('.btnCardAll');
        const btnNone = card.querySelector('.btnCardNone');
        const btnCrud = card.querySelector('.btnCardCrud');

        if (btnAll) btnAll.addEventListener('click', function () { setChecks(card, true); });
        if (btnNone) btnNone.addEventListener('click', function () { setChecks(card, false); });
        if (btnCrud) btnCrud.addEventListener('click', function () {
            ['perm-ver','perm-crear','perm-editar','perm-borrar'].forEach(function (cls) {
                card.querySelectorAll('input.' + cls).forEach(function (el) { el.checked = true; });
            });
        });
    }

    function searchCards(term) {
        term = (term || '').trim().toLowerCase();
        document.querySelectorAll('.perm-card').forEach(function (card) {
            const hay = card.getAttribute('data-search') || '';
            card.style.display = hay.includes(term) ? '' : 'none';
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.perm-card .card').forEach(function (card) {
            wireCardButtons(card.closest('.perm-card'));
        });

        const search = document.getElementById('permSearch');
        const clear = document.getElementById('btnClearSearch');

        if (search) {
            search.addEventListener('input', function () { searchCards(search.value); });
        }
        if (clear) {
            clear.addEventListener('click', function () {
                if (search) search.value = '';
                searchCards('');
            });
        }

        document.querySelectorAll('[data-mass]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const key = btn.getAttribute('data-mass');

                if (key === 'ver') setChecksByClass('perm-ver', true);
                if (key === 'crear') setChecksByClass('perm-crear', true);
                if (key === 'editar') setChecksByClass('perm-editar', true);
                if (key === 'borrar') setChecksByClass('perm-borrar', true);

                if (key === 'unver') setChecksByClass('perm-ver', false);
                if (key === 'uncrear') setChecksByClass('perm-crear', false);
                if (key === 'uneditar') setChecksByClass('perm-editar', false);
                if (key === 'unborrar') setChecksByClass('perm-borrar', false);
            });
        });

        const btnToggleAll = document.getElementById('btnToggleAll');
        if (btnToggleAll) btnToggleAll.addEventListener('click', toggleAllVisible);
    });

    @if (session('success'))
    Swal.fire({
        position: 'center',
        icon: 'success',
        title: '{{ session('success') }}',
        showConfirmButton: false,
        timer: 1500
    });
    @endif

    @if ($errors->any())
    Swal.fire({
        icon: 'error',
        title: 'Error en el formulario',
        html: `
            <ul style="text-align: left;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        `,
        confirmButtonText: 'Aceptar'
    });
    @endif
})();
</script>
@stop
