@extends('adminlte::page')

@section('title', 'Crear Usuario')

@section('content_header')
    <h1>Creación de un Nuevo Usuario</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Llene los Datos</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('users.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="name">Nombre del Usuario</label>
                                    <input type="text" name="name" id="name"
                                           class="form-control @error('name') is-invalid @enderror"
                                           value="{{ old('name') }}" placeholder="Ingrese el nombre" required>
                                    @error('name')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" name="email" id="email"
                                           class="form-control @error('email') is-invalid @enderror"
                                           value="{{ old('email') }}" placeholder="Ingrese el correo electrónico" required>
                                    @error('email')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            @role('Superadmin')
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="telefono">Teléfono WhatsApp</label>
                                    <input type="text" name="telefono" id="telefono"
                                           class="form-control @error('telefono') is-invalid @enderror"
                                           value="{{ old('telefono') }}" placeholder="Ejemplo: 4434765057">
                                    @error('telefono')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                            @endrole
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="role_id">Rol</label>
                                    <select name="role_id" id="role_id"
                                            class="form-control @error('role_id') is-invalid @enderror" required>
                                        <option value="" disabled {{ old('role_id') ? '' : 'selected' }}>Seleccione un rol</option>
                                        @foreach ($roles as $role)
                                            @if(!(auth()->user()->hasRole('Administrador') && !auth()->user()->hasRole('Superadmin') && $role->name === 'Administrador'))
                                                @php
                                                    $unidadRolId = $role->unidadIdEfectiva();
                                                    $unidadRolNombre = $role->unidadEfectivaNombre();
                                                @endphp
                                                <option value="{{ $role->id }}"
                                                        data-unidad-id="{{ $unidadRolId }}"
                                                        {{ (string) old('role_id') === (string) $role->id ? 'selected' : '' }}>
                                                    {{ $role->name }}{{ !is_null($unidadRolId) ? ' - ' . ($unidadRolNombre ?? 'SIN UNIDAD') : ' - GLOBAL' }}
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                    @error('role_id')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                    <small class="text-muted">Solo aparecen los roles que puedes asignar.</small>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group password-group">
                                    <label for="password">Contraseña</label>
                                    <div class="input-group password-wrapper">
                                        <input type="password" name="password" id="password"
                                               class="form-control @error('password') is-invalid @enderror"
                                               placeholder="Ingrese la contraseña" required>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-password-toggle" data-target="password">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>
                                        </div>
                                        @error('password')
                                            <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group password-group">
                                    <label for="password_confirmation">Repetir Contraseña</label>
                                    <div class="input-group password-wrapper">
                                        <input type="password" name="password_confirmation" id="password_confirmation"
                                               class="form-control"
                                               placeholder="Confirme la contraseña" required>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-password-toggle" data-target="password_confirmation">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="unidad_id">Unidad (principal)</label>
                                    <select name="unidad_id" id="unidad_id"
                                            class="form-control @error('unidad_id') is-invalid @enderror">
                                        <option value="">Sin unidad</option>
                                        @foreach ($unidades as $u)
                                            <option value="{{ $u->id }}" {{ old('unidad_id', $unidadIdDefault) == $u->id ? 'selected' : '' }}>
                                                {{ $u->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('unidad_id')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                    <small class="text-muted">La unidad principal debe coincidir con el rol si el rol pertenece a una unidad específica.</small>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="turno_id">Turno</label>
                                    <select name="turno_id" id="turno_id"
                                            class="form-control @error('turno_id') is-invalid @enderror">
                                        <option value="">Sin turno</option>
                                        @foreach ($turnos as $t)
                                            <option value="{{ $t->id }}" {{ old('turno_id') == $t->id ? 'selected' : '' }}>
                                                {{ $t->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('turno_id')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                    <small class="text-muted">Para Encargados o jefes de turno esto es importante.</small>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="patrulla_id">Patrulla (número económico)</label>
                                    <select name="patrulla_id" id="patrulla_id"
                                            class="form-control @error('patrulla_id') is-invalid @enderror">
                                        <option value="">Sin patrulla</option>
                                        @foreach ($patrullas as $p)
                                            <option value="{{ $p->id }}" {{ old('patrulla_id') == $p->id ? 'selected' : '' }}>
                                                {{ $p->numero_economico }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('patrulla_id')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                    <small class="text-muted">Esto es del usuario, no del hecho.</small>
                                </div>
                            </div>
                        </div>

                        <div class="row" id="box_delegacion" style="display:none;">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="delegacion_id">Delegación</label>
                                    <select name="delegacion_id" id="delegacion_id"
                                            class="form-control @error('delegacion_id') is-invalid @enderror">
                                        <option value="">Seleccione una delegación</option>
                                        @foreach ($delegaciones as $d)
                                            <option value="{{ $d->id }}" {{ old('delegacion_id') == $d->id ? 'selected' : '' }}>
                                                {{ $d->nombre }}@if(!empty($d->clave)) ({{ $d->clave }}) @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('delegacion_id')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                    <small class="text-muted">Solo aplica si la unidad principal es DELEGACIONES.</small>
                                </div>
                            </div>
                        </div>

                        <div class="row" id="box_destacamento" style="display:none;">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="destacamento_id">Destacamento</label>
                                    <select name="destacamento_id" id="destacamento_id"
                                            class="form-control @error('destacamento_id') is-invalid @enderror">
                                        <option value="">Seleccione un destacamento</option>
                                        @foreach ($destacamentos as $destacamento)
                                            <option value="{{ $destacamento->id }}" {{ old('destacamento_id') == $destacamento->id ? 'selected' : '' }}>
                                                {{ $destacamento->nombre }}@if(!empty($destacamento->clave)) ({{ $destacamento->clave }}) @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('destacamento_id')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                    <small class="text-muted">Solo aplica si la unidad principal es CARRETERAS.</small>
                                </div>
                            </div>
                        </div>

                        <div class="row" id="box_unidades_extra" style="display:none;">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="unidades_ids">Unidades adicionales</label>
                                    <select name="unidades_ids[]" id="unidades_ids"
                                            class="form-control @error('unidades_ids') is-invalid @enderror" multiple>
                                        @foreach ($unidades as $u)
                                            <option value="{{ $u->id }}" {{ collect(old('unidades_ids', []))->contains($u->id) ? 'selected' : '' }}>
                                                {{ $u->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('unidades_ids')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                    @error('unidades_ids.*')
                                        <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                    <small class="text-muted">Solo aplica para roles globales como Coordinador, si así lo manejas.</small>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa-solid fa-check"></i> Registrar
                                    </button>
                                    <a href="{{ route('users.index') }}" class="btn btn-secondary">
                                        <i class="fa-solid fa-ban"></i> Cancelar
                                    </a>
                                </div>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
<style>
    .form-group label {
        font-weight: bold;
        color: #e5e7eb;
    }

    .form-control,
    .custom-select,
    select.form-control {
        background: rgba(15, 23, 42, 0.75) !important;
        color: #ffffff !important;
        border: 1px solid rgba(255, 255, 255, 0.14) !important;
        border-radius: 18px !important;
        box-shadow: none !important;
    }

    .form-control:focus,
    .custom-select:focus,
    select.form-control:focus {
        background: rgba(15, 23, 42, 0.92) !important;
        color: #ffffff !important;
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25) !important;
    }

    select.form-control option,
    .custom-select option,
    select option {
        background-color: #0f172a !important;
        color: #ffffff !important;
    }

    .form-control::placeholder {
        color: rgba(255, 255, 255, 0.65) !important;
    }

    .text-muted,
    small.text-muted {
        color: rgba(255, 255, 255, 0.72) !important;
    }

    .card {
        background: linear-gradient(135deg, rgba(30, 41, 59, 0.95), rgba(49, 46, 129, 0.92)) !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
        border-radius: 24px !important;
        overflow: hidden;
    }

    .card-header {
        background: rgba(255, 255, 255, 0.04) !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06) !important;
    }

    .card-title,
    .content-header h1 {
        color: #ffffff !important;
        font-weight: 700;
    }

    .btn-primary,
    .btn-secondary {
        border-radius: 16px !important;
    }

    .password-wrapper .form-control {
        border-top-right-radius: 0 !important;
        border-bottom-right-radius: 0 !important;
    }

    .btn-password-toggle {
        height: 100%;
        min-width: 48px;
        border-top-left-radius: 0 !important;
        border-bottom-left-radius: 0 !important;
        border-top-right-radius: 18px !important;
        border-bottom-right-radius: 18px !important;
        border: 1px solid rgba(255, 255, 255, 0.14) !important;
        border-left: 0 !important;
        background: rgba(15, 23, 42, 0.95) !important;
        color: #ffffff !important;
        box-shadow: none !important;
    }

    .btn-password-toggle:hover,
    .btn-password-toggle:focus {
        background: rgba(30, 41, 59, 1) !important;
        color: #ffffff !important;
        outline: none !important;
        box-shadow: none !important;
    }

    .password-wrapper .invalid-feedback {
        width: 100%;
    }
</style>
@stop

@section('js')
    <script>
        (function () {
            const UNIDAD_DELEGACIONES_ID = @json($unidadDelegacionesId);
            const UNIDAD_CARRETERAS_ID = @json($unidadCarreterasId);

            function getSelectedRoleOption() {
                const roleSelect = document.getElementById('role_id');
                if (!roleSelect) return null;
                return roleSelect.options[roleSelect.selectedIndex] || null;
            }

            function limpiarTelefonoInput() {
                const telefonoInput = document.getElementById('telefono');
                if (!telefonoInput) return;
                telefonoInput.value = telefonoInput.value.replace(/\D+/g, '');
            }

            function togglePassword(button) {
                const targetId = button.getAttribute('data-target');
                const input = document.getElementById(targetId);
                const icon = button.querySelector('i');

                if (!input || !icon) return;

                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            }

            function toggleUnidadesExtra() {
                const roleSelect = document.getElementById('role_id');
                const roleOption = roleSelect ? roleSelect.options[roleSelect.selectedIndex] : null;
                const roleName = roleOption ? roleOption.text.split(' - ')[0].trim() : '';
                const box = document.getElementById('box_unidades_extra');

                if (!box) return;

                box.style.display = (roleName === 'Coordinador') ? '' : 'none';
            }

            function syncUnidadConRol() {
                const roleOption = getSelectedRoleOption();
                const unidadSelect = document.getElementById('unidad_id');

                if (!roleOption || !unidadSelect) return;

                const unidadRol = roleOption.getAttribute('data-unidad-id');

                if (unidadRol && unidadRol !== 'null' && unidadRol !== '') {
                    unidadSelect.value = unidadRol;
                    unidadSelect.setAttribute('disabled', 'disabled');
                } else {
                    unidadSelect.removeAttribute('disabled');
                }
            }

            function beforeSubmitEnableUnidad() {
                const unidadSelect = document.getElementById('unidad_id');
                if (unidadSelect) {
                    unidadSelect.removeAttribute('disabled');
                }
            }

            function toggleUbicacionEspecial() {
                const unidadSel = document.getElementById('unidad_id');
                const boxDelegacion = document.getElementById('box_delegacion');
                const boxDestacamento = document.getElementById('box_destacamento');
                const delegSel = document.getElementById('delegacion_id');
                const destacSel = document.getElementById('destacamento_id');

                if (!unidadSel || !boxDelegacion || !boxDestacamento || !delegSel || !destacSel) return;

                const unidadId = unidadSel.value ? parseInt(unidadSel.value, 10) : null;
                const showDelegacion = UNIDAD_DELEGACIONES_ID !== null && unidadId === parseInt(UNIDAD_DELEGACIONES_ID, 10);
                const showDestacamento = UNIDAD_CARRETERAS_ID !== null && unidadId === parseInt(UNIDAD_CARRETERAS_ID, 10);

                boxDelegacion.style.display = showDelegacion ? '' : 'none';
                boxDestacamento.style.display = showDestacamento ? '' : 'none';

                if (!showDelegacion) {
                    delegSel.value = '';
                }

                if (!showDestacamento) {
                    destacSel.value = '';
                }
            }

            document.addEventListener('DOMContentLoaded', function () {
                const roleSel = document.getElementById('role_id');
                const unidadSel = document.getElementById('unidad_id');
                const telefonoInput = document.getElementById('telefono');
                const form = document.querySelector('form');
                const passwordButtons = document.querySelectorAll('.btn-password-toggle');

                if (roleSel) {
                    roleSel.addEventListener('change', function () {
                        syncUnidadConRol();
                        toggleUnidadesExtra();
                        toggleUbicacionEspecial();
                    });
                }

                if (unidadSel) {
                    unidadSel.addEventListener('change', toggleUbicacionEspecial);
                }

                if (telefonoInput) {
                    telefonoInput.addEventListener('input', limpiarTelefonoInput);
                    telefonoInput.addEventListener('blur', limpiarTelefonoInput);
                }

                passwordButtons.forEach(function (button) {
                    button.addEventListener('click', function () {
                        togglePassword(button);
                    });
                });

                if (form) {
                    form.addEventListener('submit', function () {
                        limpiarTelefonoInput();
                        beforeSubmitEnableUnidad();
                    });
                }

                syncUnidadConRol();
                toggleUnidadesExtra();
                toggleUbicacionEspecial();
                limpiarTelefonoInput();
            });
        })();

        @if ($errors->any())
            Swal.fire({
                icon: 'error',
                title: 'Errores en el formulario',
                html: `
                    <ul style="text-align:left;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                `,
                confirmButtonText: 'Aceptar'
            });
        @endif
    </script>
@stop
