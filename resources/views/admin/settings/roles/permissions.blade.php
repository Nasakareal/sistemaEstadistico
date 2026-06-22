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

        $hiddenPermissions = [
            'editar puntos licencias',
        ];

        $permissionOverrides = [
            'ver puntos licencias' => [
                'module' => 'Puntos de licencia',
                'action' => 'ver',
                'label' => 'Ver cuentas, saldos e historial',
            ],
            'crear puntos licencias' => [
                'module' => 'Puntos de licencia',
                'action' => null,
                'label' => 'Alta inicial de cuenta (compatibilidad)',
            ],
            'registrar infracciones puntos licencias' => [
                'module' => 'Puntos de licencia',
                'action' => null,
                'label' => 'Quitar puntos por penalización',
            ],
            'acreditar capacitacion puntos licencias' => [
                'module' => 'Puntos de licencia',
                'action' => null,
                'label' => 'Gestionar cursos y acreditar capacitacion',
            ],
            'ver catalogo infracciones puntos licencias' => [
                'module' => 'Catálogo de penalizaciones para puntos',
                'action' => 'ver',
                'label' => 'Ver catálogo de penalizaciones',
            ],
            'crear catalogo infracciones puntos licencias' => [
                'module' => 'Catálogo de penalizaciones para puntos',
                'action' => 'crear',
                'label' => 'Crear penalizaciones',
            ],
            'editar catalogo infracciones puntos licencias' => [
                'module' => 'Catálogo de penalizaciones para puntos',
                'action' => 'editar',
                'label' => 'Editar puntos o fundamento legal',
            ],
        ];

        $rows = [];

        foreach ($permissions as $perm) {
            $name = trim((string) $perm->name);
            $lower = mb_strtolower($name);

            if (in_array($lower, $hiddenPermissions, true)) {
                continue;
            }

            $action = null;
            $match = null;
            $customLabel = null;

            if (isset($permissionOverrides[$lower])) {
                $override = $permissionOverrides[$lower];
                $module = $override['module'];
                $action = $override['action'];
                $customLabel = $override['label'];
            } else {
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
                    'labels' => [],
                ];
            }

            if ($customLabel) {
                $rows[$module]['labels'][$perm->id] = $customLabel;
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

                <div class="mb-3 d-flex flex-wrap roles-permissions-toolbar">
                    <button type="button" class="btn btn-outline-success btn-sm" id="btnSelectVisible">Marcar visibles</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnClearVisible">Limpiar visibles</button>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-mass="ver">Marcar Ver</button>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-mass="crear">Marcar Crear</button>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-mass="editar">Marcar Editar</button>
                    <button type="button" class="btn btn-outline-danger btn-sm" data-mass="borrar">Marcar Borrar</button>
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
                            $crudPerms = [];

                            foreach ($actionsOrder as $act) {
                                if ($group['perms'][$act]) {
                                    $crudPerms[$act] = $group['perms'][$act];
                                }
                            }

                            $hayCrud = count($crudPerms) > 0;
                            $totalAvailable = count($crudPerms) + count($otros);

                            $checkedCount = 0;
                            foreach (['ver' => $pver, 'crear' => $pcrear, 'editar' => $peditar, 'borrar' => $pborrar] as $k => $p) {
                                if ($p && in_array($p->id, $rolePermissions)) $checkedCount++;
                            }
                            foreach ($otros as $p) {
                                if (in_array($p->id, $rolePermissions)) $checkedCount++;
                            }

                            $searchText = $module;
                            foreach (['ver' => $pver, 'crear' => $pcrear, 'editar' => $peditar, 'borrar' => $pborrar] as $p) {
                                if ($p) $searchText .= ' ' . $p->name . ' ' . ($group['labels'][$p->id] ?? '');
                            }
                            foreach ($otros as $p) $searchText .= ' ' . $p->name . ' ' . ($group['labels'][$p->id] ?? '');
                        @endphp

                        <div class="col-md-6 col-lg-4 perm-card" data-search="{{ mb_strtolower($searchText) }}">
                            <div class="card card-outline card-warning h-100">
                                <div class="card-header">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <h3 class="card-title text-truncate" title="{{ $module }}">
                                            {{ $module }}
                                        </h3>
                                        <span class="badge badge-info perm-count">
                                            {{ $checkedCount }} de {{ $totalAvailable }}
                                        </span>
                                    </div>
                                </div>

                                <div class="card-body">

                                    @if ($hayCrud)
                                        <div class="perm-section-label">Acciones disponibles</div>
                                        <div class="perm-pill-grid">
                                            @foreach ($actionsOrder as $act)
                                                @php $p = $group['perms'][$act]; @endphp
                                                @continue(!$p)
                                                <label class="perm-pill">
                                                    <input
                                                        type="checkbox"
                                                        class="perm-check perm-{{ $act }}"
                                                        name="permissions[]"
                                                        value="{{ $p->id }}"
                                                        {{ in_array($p->id, $rolePermissions) ? 'checked' : '' }}
                                                    >
                                                    <span>{{ $group['labels'][$p->id] ?? $labels[$act] }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if ($hayOtros)
                                        <div class="{{ $hayCrud ? 'mt-3' : '' }}">
                                            @if ($hayCrud || count($otros) > 1)
                                                <div class="perm-section-label">Permisos especiales</div>
                                            @endif

                                            <div class="perm-pill-grid">
                                                @foreach ($otros as $p)
                                                    @php
                                                        $otroLabel = (!$hayCrud && count($otros) === 1 && mb_strtolower($p->name) === mb_strtolower($module))
                                                            ? 'Permitir'
                                                            : ($group['labels'][$p->id] ?? $p->name);
                                                    @endphp
                                                    <label class="perm-pill perm-pill--wide" title="{{ $p->name }}">
                                                        <span>{{ $otroLabel }}</span>
                                                        <input
                                                            type="checkbox"
                                                            class="perm-check perm-otros"
                                                            name="permissions[]"
                                                            value="{{ $p->id }}"
                                                            {{ in_array($p->id, $rolePermissions) ? 'checked' : '' }}
                                                        >
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    @if (!$hayCrud && !$hayOtros)
                                        <span class="text-muted">Sin permisos configurables.</span>
                                    @endif

                                </div>

                                @if ($totalAvailable > 1)
                                    <div class="card-footer d-flex flex-wrap" style="gap:8px;">
                                        <button type="button" class="btn btn-outline-success btn-sm btnCardAll">Todo</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm btnCardNone">Nada</button>
                                        @if ($hayCrud)
                                            <button type="button" class="btn btn-outline-primary btn-sm btnCardCrud">Acciones</button>
                                        @endif
                                    </div>
                                @endif
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
    .roles-permissions-toolbar{ gap: 10px; }
    .perm-card .card-title{ max-width: 75%; }
    .perm-card .card{ border-radius: 12px; }
    .perm-card .card-body{ padding: 1rem; }
    .perm-card .table td, .perm-card .table th{ vertical-align: middle; }
    .perm-section-label{
        color: rgba(234,240,255,.72);
        font-size: 12px;
        font-weight: 800;
        letter-spacing: .02em;
        margin-bottom: 8px;
        text-transform: uppercase;
    }
    .perm-pill-grid{
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
        gap: 8px;
    }
    .perm-pill{
        align-items: center;
        background: rgba(255,255,255,.05);
        border: 1px solid rgba(255,255,255,.12);
        border-radius: 10px;
        color: rgba(234,240,255,.94);
        cursor: pointer;
        display: flex;
        gap: 8px;
        justify-content: center;
        margin: 0;
        min-height: 38px;
        padding: 8px 10px;
    }
    .perm-pill:hover{
        background: rgba(45,168,255,.10);
        border-color: rgba(45,168,255,.28);
    }
    .perm-pill input{
        flex: 0 0 auto;
        margin: 0;
    }
    .perm-pill span{
        font-weight: 800;
        overflow-wrap: anywhere;
    }
    .perm-pill--wide{
        grid-column: 1 / -1;
        justify-content: space-between;
        text-align: left;
    }
</style>
@stop

@section('js')
<script>
(function () {
    function setChecks(container, checked) {
        container.querySelectorAll('input.perm-check').forEach(function (el) {
            el.checked = checked;
        });
        refreshCard(container.closest('.perm-card') || container);
    }

    function setChecksByClass(className, checked) {
        document.querySelectorAll('input.' + className).forEach(function (el) {
            if (el.offsetParent !== null) el.checked = checked;
        });
        refreshAllCards();
    }

    function setAllVisible(checked) {
        const visibles = Array.from(document.querySelectorAll('.perm-card')).filter(c => c.offsetParent !== null);
        visibles.forEach(function (card) {
            setChecks(card, checked);
        });
    }

    function refreshCard(card) {
        if (!card) return;

        const checks = Array.from(card.querySelectorAll('input.perm-check'));
        const badge = card.querySelector('.perm-count');

        if (!badge) return;

        const checked = checks.filter(function (el) { return el.checked; }).length;
        badge.textContent = checked + ' de ' + checks.length;
    }

    function refreshAllCards() {
        document.querySelectorAll('.perm-card').forEach(refreshCard);
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
            refreshCard(card);
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
            });
        });

        document.querySelectorAll('input.perm-check').forEach(function (check) {
            check.addEventListener('change', function () {
                refreshCard(check.closest('.perm-card'));
            });
        });

        const btnSelectVisible = document.getElementById('btnSelectVisible');
        if (btnSelectVisible) btnSelectVisible.addEventListener('click', function () { setAllVisible(true); });

        const btnClearVisible = document.getElementById('btnClearVisible');
        if (btnClearVisible) btnClearVisible.addEventListener('click', function () { setAllVisible(false); });

        refreshAllCards();
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
